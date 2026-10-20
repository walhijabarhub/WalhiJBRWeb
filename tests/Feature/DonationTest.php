<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\Donation;
use App\Services\Donation\DonationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DonationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test donation payment request validation.
     */
    public function test_donation_payment_request_requires_parameters(): void
    {
        $response = $this->postJson(route('donasi.pay'), []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['donor_name', 'donor_email', 'amount']);
    }

    /**
     * Test successful mock donation token checkout.
     */
    public function test_donation_checkout_returns_mock_token_when_env_key_empty(): void
    {
        $payload = [
            'donor_name' => 'Wira Pratama',
            'donor_email' => 'donatur@example.org',
            'donor_phone' => '08123456789',
            'amount' => 50000,
        ];

        $response = $this->postJson(route('donasi.pay'), $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'is_mock' => true,
            ]);

        $this->assertDatabaseHas('donations', [
            'donor_name' => 'Wira Pratama',
            'donor_email' => 'donatur@example.org',
            'amount' => 50000,
            'status' => 'pending',
        ]);
    }

    /**
     * Test simulated mock payment status update.
     */
    public function test_mock_payment_status_update(): void
    {
        $donation = Donation::create([
            'order_id' => 'WALHI-DON-1234567890',
            'donor_name' => 'Budi Santoso',
            'donor_email' => 'budi@example.com',
            'donor_phone' => '08111222333',
            'amount' => 100000,
            'status' => 'pending',
            'snap_token' => 'MOCK-SNAP-TOKEN-123',
        ]);

        $response = $this->postJson(route('donasi.mock-payment-status'), [
            'order_id' => 'WALHI-DON-1234567890',
            'status' => 'success',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('donations', [
            'order_id' => 'WALHI-DON-1234567890',
            'status' => 'success',
        ]);
    }

    /**
     * Test rendering single content detail page.
     */
    public function test_content_detail_page_renders_successfully(): void
    {
        $content = Content::create([
            'title' => 'Gugatan Izin Lingkungan Cirebon',
            'slug' => 'gugatan-izin-lingkungan-cirebon',
            'category' => 'blog',
            'status' => 'published',
            'body' => 'Isi berita gugatan izin lingkungan di Cirebon.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->get(route('content.show', 'gugatan-izin-lingkungan-cirebon'));

        $response->assertStatus(200)
            ->assertSee('Gugatan Izin Lingkungan Cirebon')
            ->assertSee('Isi berita gugatan izin lingkungan di Cirebon.');
    }

    /**
     * Test strict donation state transition matrix and webhook replay security (SEC-009 & SEC-013).
     */
    public function test_donation_state_transitions_and_webhook_replay_security(): void
    {
        $donationService = app(DonationService::class);

        // 1. Pending -> Success (Allowed)
        $d1 = Donation::create([
            'order_id' => 'WALHI-DON-TEST-1',
            'donor_name' => 'Donatur 1',
            'donor_email' => 'd1@example.com',
            'donor_phone' => '08123456781',
            'amount' => 50000,
            'status' => 'pending',
        ]);
        $res1 = $donationService->processWebhook($d1, 'settlement', 'qris');
        $this->assertTrue($res1);
        $this->assertSame('success', $d1->fresh()->status);

        // 2. Success -> Pending (Rejected)
        $res2 = $donationService->processWebhook($d1, 'pending', 'qris');
        $this->assertFalse($res2);
        $this->assertSame('success', $d1->fresh()->status);

        // 3. Success -> Failed (Rejected)
        $res3 = $donationService->processWebhook($d1, 'deny', 'qris');
        $this->assertFalse($res3);
        $this->assertSame('success', $d1->fresh()->status);

        // 4. Success -> Expired (Rejected)
        $res4 = $donationService->processWebhook($d1, 'expire', 'qris');
        $this->assertFalse($res4);
        $this->assertSame('success', $d1->fresh()->status);

        // 5. Success -> Success (Idempotent replay allowed, no status alteration)
        $res5 = $donationService->processWebhook($d1, 'capture', 'qris');
        $this->assertFalse($res5); // No state mutation
        $this->assertSame('success', $d1->fresh()->status);

        // 6. Pending -> Failed (Allowed)
        $d2 = Donation::create([
            'order_id' => 'WALHI-DON-TEST-2',
            'donor_name' => 'Donatur 2',
            'donor_email' => 'd2@example.com',
            'donor_phone' => '08123456782',
            'amount' => 25000,
            'status' => 'pending',
        ]);
        $res6 = $donationService->processWebhook($d2, 'cancel', 'bank_transfer');
        $this->assertTrue($res6);
        $this->assertSame('failed', $d2->fresh()->status);

        // 7. Failed -> Pending (Rejected)
        $res7 = $donationService->processWebhook($d2, 'pending', 'bank_transfer');
        $this->assertFalse($res7);
        $this->assertSame('failed', $d2->fresh()->status);

        // 8. Failed -> Success (Rejected)
        $res8 = $donationService->processWebhook($d2, 'settlement', 'bank_transfer');
        $this->assertFalse($res8);
        $this->assertSame('failed', $d2->fresh()->status);

        // 9. Failed -> Failed (Idempotent replay allowed)
        $res9 = $donationService->processWebhook($d2, 'deny', 'bank_transfer');
        $this->assertFalse($res9);
        $this->assertSame('failed', $d2->fresh()->status);

        // 10. Pending -> Expired (Allowed)
        $d3 = Donation::create([
            'order_id' => 'WALHI-DON-TEST-3',
            'donor_name' => 'Donatur 3',
            'donor_email' => 'd3@example.com',
            'donor_phone' => '08123456783',
            'amount' => 100000,
            'status' => 'pending',
        ]);
        $res10 = $donationService->processWebhook($d3, 'expire', 'echannel');
        $this->assertTrue($res10);
        $this->assertSame('expired', $d3->fresh()->status);

        // 11. Expired -> Pending (Rejected)
        $res11 = $donationService->processWebhook($d3, 'pending', 'echannel');
        $this->assertFalse($res11);
        $this->assertSame('expired', $d3->fresh()->status);

        // 12. Expired -> Success (Rejected)
        $res12 = $donationService->processWebhook($d3, 'settlement', 'echannel');
        $this->assertFalse($res12);
        $this->assertSame('expired', $d3->fresh()->status);

        // 13. Expired -> Expired (Idempotent replay allowed)
        $res13 = $donationService->processWebhook($d3, 'expire', 'echannel');
        $this->assertFalse($res13);
        $this->assertSame('expired', $d3->fresh()->status);
    }
}
