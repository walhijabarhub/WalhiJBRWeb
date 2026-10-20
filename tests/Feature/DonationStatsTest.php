<?php

namespace Tests\Feature;

use App\Models\Donation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DonationStatsTest extends TestCase
{
    use RefreshDatabase;

    private int $order = 0;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->actingAs(User::factory()->admin()->create());
    }

    private function donation(int $amount, string $status, string $email, ?string $createdAt = null): Donation
    {
        $donation = Donation::create([
            'order_id' => 'TEST-'.(++$this->order),
            'donor_name' => 'Donatur',
            'donor_email' => $email,
            'donor_phone' => '081200000000',
            'amount' => $amount,
            'status' => $status,
        ]);

        if ($createdAt) {
            $donation->forceFill(['created_at' => $createdAt])->save();
        }

        return $donation;
    }

    private function seedDonations(): void
    {
        $this->donation(100000, 'success', 'a@example.org');
        $this->donation(50000, 'success', 'a@example.org', now()->subMonthNoOverflow()->startOfMonth()->addDay());
        $this->donation(30000, 'success', 'b@example.org');
        $this->donation(999000, 'pending', 'c@example.org');
        $this->donation(888000, 'failed', 'd@example.org');
    }

    public function test_dashboard_totals_count_only_successful_donations(): void
    {
        $this->seedDonations();

        $stats = $this->get('/admin')->assertOk()->viewData('stats');

        $this->assertSame(180000, $stats['total_donations_amount']);
        $this->assertSame(3, $stats['successful_donations_count']);
        $this->assertCount(12, $stats['chart_labels']);
        $this->assertSame(130000, $stats['chart_data'][11]);
        $this->assertSame(50000, $stats['chart_data'][10]);
        $this->assertTrue($stats['has_donations']);
    }

    public function test_donation_page_totals(): void
    {
        $this->seedDonations();

        $response = $this->get(route('admin.content.index', 'donasi'))->assertOk();

        $this->assertSame(180000, $response->viewData('totalAmount'));
        $this->assertSame(2, $response->viewData('uniqueDonors'));
        $this->assertEqualsWithDelta(60000, $response->viewData('avgDonation'), 0.01);
        $this->assertSame([0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 50000, 130000], $response->viewData('chartData'));
    }

    public function test_totals_are_zero_without_donations(): void
    {
        $stats = $this->get('/admin')->assertOk()->viewData('stats');
        $this->assertSame(0, $stats['total_donations_amount']);
        $this->assertFalse($stats['has_donations']);

        $response = $this->get(route('admin.content.index', 'donasi'))->assertOk();
        $this->assertSame(0, $response->viewData('totalAmount'));
        $this->assertSame(0, $response->viewData('uniqueDonors'));
        $this->assertEquals(0, $response->viewData('avgDonation'));
    }

    public function test_recent_activity_shows_status_label_for_unfinished_donations(): void
    {
        $this->donation(25000, 'pending', 'c@example.org');

        $activities = collect($this->get('/admin')->viewData('recentActivities'));

        $this->assertStringContainsString('(Menunggu Pembayaran)', $activities->firstWhere('type', 'donation')['detail']);
    }

    /**
     * Without donations Chart.js would centre the y-axis on 0 and show negative
     * amounts ("Rp -0,2"); both charts pin the axis at zero (#145).
     */
    public function test_donation_charts_start_the_y_axis_at_zero(): void
    {
        foreach (['/admin', route('admin.content.index', 'donasi')] as $page) {
            $html = $this->get($page)->assertOk()->getContent();

            $this->assertMatchesRegularExpression('/y:\s*\{[^{}]*min:\s*0,\s*suggestedMax:\s*100000,/', $html, $page);
        }
    }
}
