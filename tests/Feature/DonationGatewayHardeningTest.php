<?php

namespace Tests\Feature;

use App\Enums\DonationStatus;
use App\Models\Donation;
use App\Services\Donation\MidtransService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Regression tests for audit issues #72 (fraud_status), #84 (timeouts, reconciliation)
 * and #85 (configurable webhook IP allowlist).
 */
class DonationGatewayHardeningTest extends TestCase
{
    use RefreshDatabase;

    private const SERVER_KEY = 'SB-Mid-server-TESTKEY123';

    protected function setUp(): void
    {
        parent::setUp();

        config(['midtrans.server_key' => self::SERVER_KEY, 'midtrans.is_production' => false]);
    }

    private function donation(array $attributes = []): Donation
    {
        return Donation::create(array_merge([
            'order_id' => 'WALHI-DON-'.uniqid(),
            'donor_name' => 'Donatur',
            'donor_email' => 'donatur@example.org',
            'donor_phone' => '0812000000',
            'amount' => 50000,
            'status' => 'pending',
        ], $attributes));
    }

    private function webhook(Donation $donation, array $overrides = [], string $statusCode = '200')
    {
        $gross = number_format($donation->amount, 2, '.', '');

        return $this->postJson('/donasi/webhook', array_merge([
            'order_id' => $donation->order_id,
            'status_code' => $statusCode,
            'gross_amount' => $gross,
            'signature_key' => hash('sha512', $donation->order_id.$statusCode.$gross.self::SERVER_KEY),
            'transaction_status' => 'settlement',
            'payment_type' => 'qris',
        ], $overrides));
    }

    private function fakeStatus(array $body, int $httpStatus = 200): void
    {
        Http::fake(['api.sandbox.midtrans.com/v2/*' => Http::response($body, $httpStatus)]);
    }

    // ── #72 fraud_status ──────────────────────────────────────────────

    public function test_capture_maps_according_to_fraud_status(): void
    {
        $this->assertSame(DonationStatus::Success, DonationStatus::fromMidtrans('capture', 'accept'));
        $this->assertSame(DonationStatus::Pending, DonationStatus::fromMidtrans('capture', 'challenge'));
        $this->assertSame(DonationStatus::Failed, DonationStatus::fromMidtrans('capture', 'deny'));
        $this->assertSame(DonationStatus::Failed, DonationStatus::fromMidtrans('settlement', 'deny'));
        $this->assertSame(DonationStatus::Success, DonationStatus::fromMidtrans('settlement', 'accept'));
        $this->assertSame(DonationStatus::Expired, DonationStatus::fromMidtrans('expire'));
    }

    public function test_challenged_card_payment_stays_pending(): void
    {
        $donation = $this->donation();
        $this->fakeStatus(['status_code' => '201', 'transaction_status' => 'capture', 'fraud_status' => 'challenge', 'gross_amount' => '50000.00', 'payment_type' => 'credit_card']);

        $this->webhook($donation, ['transaction_status' => 'capture', 'fraud_status' => 'challenge'], '201')->assertOk();

        $this->assertSame('pending', $donation->fresh()->status);
    }

    // ── Webhook status confirmation ───────────────────────────────────

    public function test_webhook_uses_status_api_instead_of_notification_body(): void
    {
        $donation = $this->donation();
        // Notification claims settlement, but Midtrans says the payment is still pending.
        $this->fakeStatus(['status_code' => '201', 'transaction_status' => 'pending', 'gross_amount' => '50000.00']);

        $this->webhook($donation)->assertOk();

        $this->assertSame('pending', $donation->fresh()->status);
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/v2/'.$donation->order_id.'/status')
            && $request->hasHeader('Authorization'));
    }

    public function test_webhook_returns_503_when_status_api_is_down_so_midtrans_retries(): void
    {
        $donation = $this->donation();
        Http::fake(fn () => throw new ConnectionException('timeout'));

        $this->webhook($donation)->assertStatus(503);

        $this->assertSame('pending', $donation->fresh()->status);
    }

    public function test_webhook_for_order_unknown_to_midtrans_is_rejected(): void
    {
        $donation = $this->donation();
        $this->fakeStatus(['status_code' => '404', 'status_message' => "Transaction doesn't exist."]);

        $this->webhook($donation)->assertNotFound();

        $this->assertSame('pending', $donation->fresh()->status);
    }

    public function test_status_amount_mismatch_is_not_applied(): void
    {
        $donation = $this->donation();
        $this->fakeStatus(['status_code' => '200', 'transaction_status' => 'settlement', 'gross_amount' => '10000.00']);

        $this->webhook($donation)->assertStatus(503);

        $this->assertSame('pending', $donation->fresh()->status);
    }

    // ── #85 IP allowlist ──────────────────────────────────────────────

    public function test_ip_allowlist_is_off_by_default_and_configurable(): void
    {
        $this->app['env'] = 'production';
        $donation = $this->donation();
        $this->fakeStatus(['status_code' => '200', 'transaction_status' => 'settlement', 'gross_amount' => '50000.00']);

        $this->webhook($donation)->assertOk();
        $this->assertSame('success', $donation->fresh()->status);

        config(['midtrans.ip_whitelist_enabled' => true, 'midtrans.ip_whitelist' => ['203.0.113.0/24']]);
        $other = $this->donation();

        $this->webhook($other)->assertForbidden();
        $this->assertSame('pending', $other->fresh()->status);
    }

    // ── #84 Timeouts & reconciliation ─────────────────────────────────

    public function test_snap_connection_errors_are_retried_then_reported(): void
    {
        config(['midtrans.timeout' => 7, 'midtrans.connect_timeout' => 3]);
        $attempts = 0;
        Http::fake(function () use (&$attempts) {
            $attempts++;
            throw new ConnectionException('timeout');
        });

        try {
            app(MidtransService::class)->createTransaction('WALHI-DON-X', [
                'donor_name' => 'Wira', 'donor_email' => 'wira@example.org', 'donor_phone' => '0812',
            ], 50000);
            $this->fail('Expected a RuntimeException');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('unreachable', $e->getMessage());
        }

        // One initial attempt plus two retries.
        $this->assertSame(3, $attempts);
    }

    public function test_snap_http_errors_are_not_retried(): void
    {
        Http::fake(['app.sandbox.midtrans.com/*' => Http::response(['error_messages' => ['bad']], 400)]);

        $this->expectException(\RuntimeException::class);

        try {
            app(MidtransService::class)->createTransaction('WALHI-DON-Y', [
                'donor_name' => 'Wira', 'donor_email' => 'wira@example.org', 'donor_phone' => '0812',
            ], 50000);
        } finally {
            Http::assertSentCount(1);
        }
    }

    public function test_reconcile_updates_missed_payments_and_expires_stale_donations(): void
    {
        $paid = $this->donation(['order_id' => 'WALHI-DON-PAID']);
        $stale = $this->donation(['order_id' => 'WALHI-DON-STALE']);
        $fresh = $this->donation(['order_id' => 'WALHI-DON-FRESH']);
        $paid->forceFill(['created_at' => now()->subHour()])->save();
        $stale->forceFill(['created_at' => now()->subDays(2)])->save();

        Http::fake([
            'api.sandbox.midtrans.com/v2/WALHI-DON-PAID/status' => Http::response(['status_code' => '200', 'transaction_status' => 'settlement', 'gross_amount' => '50000.00', 'payment_type' => 'gopay']),
            'api.sandbox.midtrans.com/v2/WALHI-DON-STALE/status' => Http::response(['status_code' => '404', 'status_message' => "Transaction doesn't exist."]),
        ]);

        $this->artisan('walhi:donations-reconcile')->assertSuccessful();

        $this->assertSame('success', $paid->fresh()->status);
        $this->assertSame('gopay', $paid->fresh()->payment_type);
        $this->assertSame('expired', $stale->fresh()->status);
        $this->assertSame('pending', $fresh->fresh()->status, 'Recent donations are left alone');
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'WALHI-DON-FRESH'));
    }

    public function test_status_lookup_is_not_retried_so_webhook_answers_quickly(): void
    {
        $donation = $this->donation();
        $attempts = 0;
        Http::fake(function () use (&$attempts) {
            $attempts++;
            throw new ConnectionException('timeout');
        });

        $this->webhook($donation)->assertStatus(503);

        $this->assertSame(1, $attempts);
    }

    public function test_reconcile_amount_mismatch_is_reported_without_failing_the_run(): void
    {
        $donation = $this->donation(['order_id' => 'WALHI-DON-MISMATCH']);
        $donation->forceFill(['created_at' => now()->subHour()])->save();
        $this->fakeStatus(['status_code' => '200', 'transaction_status' => 'settlement', 'gross_amount' => '1000.00']);

        $this->artisan('walhi:donations-reconcile')
            ->expectsOutputToContain('1 nominal tidak cocok')
            ->assertSuccessful();

        $this->assertSame('pending', $donation->fresh()->status);
    }

    #[DataProvider('unconfiguredGatewayCases')]
    public function test_reconcile_without_gateway_preserves_pending_donations(string $environment, ?string $serverKey): void
    {
        $this->app['env'] = $environment;
        config(['midtrans.server_key' => $serverKey]);
        Http::fake();
        $stale = $this->donation();
        $recent = $this->donation();
        $mock = $this->donation(['snap_token' => 'MOCK-SNAP-TEST']);
        $stale->forceFill(['created_at' => now()->subDays(2)])->save();
        $recent->forceFill(['created_at' => now()->subHour()])->save();
        $mock->forceFill(['created_at' => now()->subDays(2)])->save();
        $before = Donation::orderBy('id')->get()->toArray();

        $this->artisan('walhi:donations-reconcile')
            ->expectsOutputToContain('Periksa MIDTRANS_SERVER_KEY')
            ->assertFailed();

        $this->assertSame($before, Donation::orderBy('id')->get()->toArray());
        Http::assertNothingSent();
    }

    public static function unconfiguredGatewayCases(): array
    {
        return [
            'production missing key' => ['production', null],
            'production empty key' => ['production', ''],
            'staging missing key' => ['staging', null],
            'local mock mode' => ['local', null],
            'testing mock mode' => ['testing', null],
        ];
    }

    public function test_reconcile_settles_old_pending_donation_after_gateway_configuration_is_restored(): void
    {
        $this->app['env'] = 'production';
        config(['midtrans.server_key' => null]);
        $this->fakeStatus(['status_code' => '200', 'transaction_status' => 'settlement', 'gross_amount' => '50000.00', 'payment_type' => 'qris']);
        $donation = $this->donation();
        $donation->forceFill(['created_at' => now()->subDays(2)])->save();

        $this->artisan('walhi:donations-reconcile')->assertFailed();

        $this->assertSame('pending', $donation->fresh()->status);
        Http::assertNothingSent();

        config(['midtrans.server_key' => self::SERVER_KEY]);

        $this->artisan('walhi:donations-reconcile')->assertSuccessful();

        $this->assertSame('success', $donation->fresh()->status);
        $this->assertSame('qris', $donation->fresh()->payment_type);
        Http::assertSentCount(1);
    }

    public function test_reconcile_connection_failure_preserves_old_pending_donation(): void
    {
        $this->app['env'] = 'production';
        $donation = $this->donation();
        $donation->forceFill(['created_at' => now()->subDays(2)])->save();
        $attempts = 0;
        Http::fake(function () use (&$attempts) {
            $attempts++;
            throw new ConnectionException('timeout');
        });

        $this->artisan('walhi:donations-reconcile')
            ->expectsOutputToContain('1 gagal')
            ->assertFailed();

        $this->assertSame('pending', $donation->fresh()->status);
        $this->assertSame(1, $attempts);
    }

    #[DataProvider('unavailableStatusCases')]
    public function test_reconcile_api_failure_preserves_old_pending_donation(int $httpStatus, array $body): void
    {
        $this->app['env'] = 'production';
        $donation = $this->donation();
        $donation->forceFill(['created_at' => now()->subDays(2)])->save();
        $this->fakeStatus($body, $httpStatus);

        $this->artisan('walhi:donations-reconcile')
            ->expectsOutputToContain('1 gagal')
            ->assertFailed();

        $this->assertSame('pending', $donation->fresh()->status);
        Http::assertSentCount(1);
    }

    public static function unavailableStatusCases(): array
    {
        return [
            'unauthorized' => [401, ['status_code' => '401']],
            'rate limited' => [429, ['status_code' => '429']],
            'server error' => [500, ['status_code' => '500']],
            'unavailable with misleading not-found body' => [503, ['status_code' => '404']],
            'invalid success body' => [200, []],
        ];
    }

    #[DataProvider('notFoundStatusCases')]
    public function test_reconcile_authoritative_not_found_only_expires_old_pending_donations(int $httpStatus): void
    {
        $this->app['env'] = 'production';
        $stale = $this->donation();
        $recent = $this->donation();
        $stale->forceFill(['created_at' => now()->subDays(2)])->save();
        $recent->forceFill(['created_at' => now()->subHour()])->save();
        $this->fakeStatus(['status_code' => '404', 'status_message' => "Transaction doesn't exist."], $httpStatus);

        $this->artisan('walhi:donations-reconcile')->assertSuccessful();

        $this->assertSame('expired', $stale->fresh()->status);
        $this->assertSame('pending', $recent->fresh()->status);
        Http::assertSentCount(2);
    }

    public static function notFoundStatusCases(): array
    {
        return [
            'HTTP not found' => [404],
            'Midtrans not-found status in successful response' => [200],
        ];
    }

    public function test_reconcile_does_not_expire_old_donation_still_pending_at_gateway(): void
    {
        $this->app['env'] = 'production';
        $donation = $this->donation();
        $donation->forceFill(['created_at' => now()->subDays(2)])->save();
        $this->fakeStatus(['status_code' => '201', 'transaction_status' => 'pending', 'gross_amount' => '50000.00']);

        $this->artisan('walhi:donations-reconcile')->assertSuccessful();

        $this->assertSame('pending', $donation->fresh()->status);
    }
}
