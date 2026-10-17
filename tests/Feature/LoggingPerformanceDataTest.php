<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Comment;
use App\Models\Content;
use App\Models\Donation;
use App\Models\Subscriber;
use App\Models\User;
use App\Notifications\ConfirmNewsletterSubscription;
use App\Services\AuditLogService;
use App\Services\Content\ContentCache;
use App\Services\Content\ViewCounter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Regression tests for audit issues #65 (audit trail), #86 (search), #87 (caching),
 * #88 (view counter) and #94 (newsletter double opt-in, data retention).
 */
class LoggingPerformanceDataTest extends TestCase
{
    use RefreshDatabase;

    private function content(array $attributes = []): Content
    {
        static $n = 0;
        $n++;

        return Content::create(array_merge([
            'title' => "Artikel {$n}",
            'slug' => "artikel-{$n}",
            'category' => 'blog',
            'status' => 'published',
            'body' => '<p>Isi artikel</p>',
            'publish_date' => now()->subDays($n),
        ], $attributes));
    }

    // ── #65 Audit trail ───────────────────────────────────────────────

    public function test_audit_entries_are_stored_in_the_database(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);
        $content = $this->content();

        $this->actingAs($admin)->delete("/admin/blog/{$content->id}")->assertRedirect();

        $entry = AuditLog::where('action', 'CONTENT_DELETE')->firstOrFail();
        $this->assertSame($admin->id, $entry->user_id);
        $this->assertSame((string) $content->id, $entry->model_id);
        $this->assertSame($content->title, $entry->details['title']);
    }

    public function test_audit_channel_records_info_regardless_of_log_level(): void
    {
        config(['logging.channels.daily.level' => 'error']);

        $this->assertSame('info', config('logging.channels.audit.level'));

        AuditLogService::log('TEST_ACTION', 'System');
        $this->assertDatabaseHas('audit_logs', ['action' => 'TEST_ACTION']);
    }

    // ── #86 Search ────────────────────────────────────────────────────

    public function test_search_uses_fulltext_on_mysql(): void
    {
        config(['database.connections.mysql_sql_only' => array_merge(config('database.connections.mysql'), ['database' => 'unused'])]);

        $sql = Content::on('mysql_sql_only')->search('tambang ilegal')->toSql();

        $this->assertStringContainsString('match (`title`, `body`) against (? in boolean mode)', $sql);
    }

    public function test_search_strips_boolean_operators_and_short_words(): void
    {
        config(['database.connections.mysql_sql_only' => array_merge(config('database.connections.mysql'), ['database' => 'unused'])]);

        $query = Content::on('mysql_sql_only')->search('+hutan -(ab) "sawit"*');

        $this->assertContains('+hutan* +sawit*', $query->getBindings());
    }

    public function test_search_falls_back_to_like_and_finds_content(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            $this->markTestSkipped('InnoDB FULLTEXT indexes do not see rows from the uncommitted test transaction.');
        }

        $match = $this->content(['title' => 'Tambang Ilegal di Bandung']);
        $this->content(['title' => 'Banjir Citarum']);

        $this->get('/blog?kategori=tambang')->assertOk()->assertSee($match->title)->assertDontSee('Banjir Citarum');
    }

    // ── #87 Caching ───────────────────────────────────────────────────

    public function test_homepage_sections_are_cached_and_invalidated_on_change(): void
    {
        $this->content(['title' => 'Berita Pertama']);

        $this->get('/')->assertOk()->assertSee('Berita Pertama');
        $this->assertTrue(Cache::has(ContentCache::HOME));

        DB::enableQueryLog();
        $this->get('/')->assertOk();
        $contentQueries = collect(DB::getQueryLog())->filter(fn ($q) => str_contains($q['query'], 'from "contents"'));
        DB::disableQueryLog();
        $this->assertCount(0, $contentQueries, 'Cached homepage should not query contents for its sections');

        $this->content(['title' => 'Berita Terbaru', 'publish_date' => now()->addDay()]);
        $this->assertFalse(Cache::has(ContentCache::HOME));
        $this->get('/')->assertSee('Berita Terbaru');
    }

    public function test_homepage_loads_at_most_seven_news_items(): void
    {
        foreach (range(1, 12) as $i) {
            $this->content();
        }

        $view = $this->get('/')->assertOk()->viewData('newsCards');

        $this->assertCount(6, $view);
    }

    public function test_sitemap_is_cached_and_invalidated(): void
    {
        $this->content(['slug' => 'pertama']);
        $this->get('/sitemap.xml')->assertOk()->assertSee('pertama');
        $this->assertTrue(Cache::has(ContentCache::SITEMAP));

        $this->content(['slug' => 'kedua']);
        $this->get('/sitemap.xml')->assertSee('kedua');
    }

    public function test_view_count_updates_do_not_flush_caches(): void
    {
        $content = $this->content();
        Cache::put(ContentCache::HOME, ['cached' => true]);

        $content->increment('views');

        $this->assertTrue(Cache::has(ContentCache::HOME));
    }

    // ── #88 Views buffer ──────────────────────────────────────────────

    public function test_views_are_buffered_and_flushed_in_batches(): void
    {
        $content = $this->content();

        $this->get("/konten/{$content->slug}")->assertOk();
        $this->flushSession();
        $this->get("/konten/{$content->slug}")->assertOk();

        $this->assertSame(0, $content->fresh()->views, 'No UPDATE per page view');
        $this->assertSame(2, ViewCounter::pending($content->id));

        $this->artisan('walhi:flush-views')->assertSuccessful();

        $this->assertSame(2, $content->fresh()->views);
        $this->assertSame(0, ViewCounter::pending($content->id));
    }

    // ── #94 Newsletter double opt-in ──────────────────────────────────

    public function test_subscription_requires_email_confirmation(): void
    {
        Notification::fake();

        $this->post('/newsletter/subscribe', ['email' => 'Warga@Example.org'])->assertSessionHas('subscribe_success');

        $subscriber = Subscriber::where('email', 'warga@example.org')->firstOrFail();
        $this->assertFalse($subscriber->is_active);
        $this->assertNull($subscriber->confirmed_at);
        Notification::assertSentOnDemand(ConfirmNewsletterSubscription::class);

        $this->get(URL::temporarySignedRoute('newsletter.confirm', now()->addDay(), ['subscriber' => $subscriber->id]))
            ->assertRedirect(route('home'));

        $this->assertTrue($subscriber->fresh()->is_active);
        $this->assertNotNull($subscriber->fresh()->confirmed_at);
    }

    public function test_confirmation_link_must_be_signed(): void
    {
        $subscriber = Subscriber::create(['email' => 'warga@example.org', 'is_active' => false]);

        $this->get(route('newsletter.confirm', ['subscriber' => $subscriber->id]))->assertForbidden();
        $this->assertFalse($subscriber->fresh()->is_active);
    }

    public function test_confirmation_is_not_resent_within_cooldown(): void
    {
        Notification::fake();

        $this->post('/newsletter/subscribe', ['email' => 'warga@example.org']);
        $this->post('/newsletter/subscribe', ['email' => 'warga@example.org']);

        Notification::assertSentOnDemandTimes(ConfirmNewsletterSubscription::class, 1);
    }

    public function test_unsubscribe_requires_post_confirmation(): void
    {
        $subscriber = Subscriber::create(['email' => 'warga@example.org', 'is_active' => true, 'confirmed_at' => now()]);

        $this->get($subscriber->unsubscribeUrl())->assertOk()->assertSee('Ya, Berhenti Berlangganan');
        $this->assertTrue($subscriber->fresh()->is_active, 'GET must not unsubscribe');

        $this->post(route('newsletter.unsubscribe.store', ['token' => $subscriber->token]))->assertOk();
        $this->assertFalse($subscriber->fresh()->is_active);
    }

    public function test_resubscribing_after_soft_delete_does_not_fail(): void
    {
        Notification::fake();
        $subscriber = Subscriber::create(['email' => 'warga@example.org', 'is_active' => true, 'confirmed_at' => now()]);
        $subscriber->delete();

        $this->post('/newsletter/subscribe', ['email' => 'warga@example.org'])->assertSessionHas('subscribe_success');

        $this->assertFalse($subscriber->fresh()->trashed());
        Notification::assertSentOnDemand(ConfirmNewsletterSubscription::class);
    }

    public function test_export_contains_only_confirmed_active_subscribers(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);
        Subscriber::create(['email' => 'aktif@example.org', 'is_active' => true, 'confirmed_at' => now()]);
        Subscriber::create(['email' => 'belum@example.org', 'is_active' => false]);
        Subscriber::create(['email' => 'berhenti@example.org', 'is_active' => false, 'confirmed_at' => now()]);

        $csv = $this->actingAs($admin)->get('/admin/subscribers/export')->streamedContent();

        $this->assertStringContainsString('aktif@example.org', $csv);
        $this->assertStringNotContainsString('belum@example.org', $csv);
        $this->assertStringNotContainsString('berhenti@example.org', $csv);
    }

    // ── #94 Data retention ────────────────────────────────────────────

    public function test_prune_anonymises_old_personal_data(): void
    {
        $content = $this->content();
        $oldDonation = Donation::create(['order_id' => 'OLD', 'donor_name' => 'Budi', 'donor_email' => 'budi@example.org', 'donor_phone' => '0812', 'amount' => 50000, 'status' => 'success']);
        $newDonation = Donation::create(['order_id' => 'NEW', 'donor_name' => 'Sari', 'donor_email' => 'sari@example.org', 'donor_phone' => '0813', 'amount' => 75000, 'status' => 'success']);
        $oldComment = Comment::create(['content_id' => $content->id, 'author_name' => 'Ani', 'author_email' => 'ani@example.org', 'body' => 'Komentar', 'status' => 'approved']);
        $stale = Subscriber::create(['email' => 'lama@example.org', 'is_active' => false]);
        $confirmed = Subscriber::create(['email' => 'tetap@example.org', 'is_active' => true, 'confirmed_at' => now()]);

        DB::table('donations')->where('id', $oldDonation->id)->update(['created_at' => now()->subMonths(30)]);
        DB::table('comments')->where('id', $oldComment->id)->update(['created_at' => now()->subMonths(30)]);
        DB::table('subscribers')->whereIn('id', [$stale->id, $confirmed->id])->update(['created_at' => now()->subDays(60)]);

        $this->artisan('walhi:prune-personal-data')->assertSuccessful();

        $old = $oldDonation->fresh();
        $this->assertSame('Donatur Anonim', $old->donor_name);
        $this->assertStringEndsWith('@anonymized.invalid', $old->donor_email);
        $this->assertSame(50000, $old->amount);
        $this->assertSame('sari@example.org', $newDonation->fresh()->donor_email);
        $this->assertStringEndsWith('@anonymized.invalid', $oldComment->fresh()->author_email);
        $this->assertNull(Subscriber::withTrashed()->find($stale->id));
        $this->assertNotNull($confirmed->fresh());
        $this->assertDatabaseHas('audit_logs', ['action' => 'PERSONAL_DATA_PRUNED']);
    }

    public function test_prune_dry_run_changes_nothing(): void
    {
        $donation = Donation::create(['order_id' => 'OLD', 'donor_name' => 'Budi', 'donor_email' => 'budi@example.org', 'donor_phone' => '0812', 'amount' => 50000, 'status' => 'success']);
        DB::table('donations')->where('id', $donation->id)->update(['created_at' => now()->subMonths(30)]);

        $this->artisan('walhi:prune-personal-data', ['--dry-run' => true])->assertSuccessful();

        $this->assertSame('budi@example.org', $donation->fresh()->donor_email);
    }
}
