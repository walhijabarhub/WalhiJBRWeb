<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Content;
use App\Models\Donation;
use App\Models\Subscriber;
use App\Models\User;
use App\Services\CsvSanitizer;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    // ──────────────────────────────────────────────────────────────────────────
    // 1. Authorization & Privilege Escalation Tests
    // ──────────────────────────────────────────────────────────────────────────

    public function test_unauthenticated_guest_cannot_access_admin_endpoints(): void
    {
        $response = $this->get('/admin');
        $response->assertRedirect(route('login'));

        $response = $this->get('/admin/comments');
        $response->assertRedirect(route('login'));

        $response = $this->get('/admin/subscribers');
        $response->assertRedirect(route('login'));
    }

    public function test_default_login_url_is_hidden_and_returns_404(): void
    {
        $this->get('/login')->assertStatus(404);
        $this->post('/login')->assertStatus(404);
    }

    public function test_normal_user_cannot_access_admin_dashboard_or_subscribers(): void
    {
        $user = User::factory()->create(); // default subscriber role

        $response = $this->actingAs($user)->get('/admin');
        $response->assertStatus(403);

        $response = $this->actingAs($user)->get('/admin/subscribers');
        $response->assertStatus(403);
    }

    public function test_editor_cannot_perform_destructive_admin_actions(): void
    {
        $editor = User::factory()->editor()->create();

        $content = Content::create([
            'title' => 'Article to Delete',
            'slug' => 'article-to-delete',
            'category' => 'blog',
            'status' => 'published',
            'body' => 'Body text',
        ]);

        $comment = Comment::create([
            'content_id' => $content->id,
            'author_name' => 'John',
            'author_email' => 'john@example.com',
            'body' => 'Test comment',
            'status' => 'pending',
        ]);

        $subscriber = Subscriber::create([
            'email' => 'sub@example.com',
            'is_active' => true,
        ]);

        // Editor cannot delete content
        $response = $this->actingAs($editor)->delete("/admin/blog/{$content->id}");
        $response->assertStatus(403);

        // Editor cannot delete comment
        $response = $this->actingAs($editor)->delete("/admin/comments/{$comment->id}");
        $response->assertStatus(403);

        // Editor cannot delete subscriber
        $response = $this->actingAs($editor)->delete("/admin/subscribers/{$subscriber->id}");
        $response->assertStatus(403);

        // Editor cannot export subscriber list
        $response = $this->actingAs($editor)->get('/admin/subscribers/export');
        $response->assertStatus(403);
    }

    public function test_editor_cannot_modify_sensitive_organizational_categories(): void
    {
        $editor = User::factory()->editor()->create();

        // 1. Editor CAN manage normal content like blog
        $blogResponse = $this->actingAs($editor)->post('/admin/blog', [
            'title' => 'Editor Normal Article',
            'slug' => 'editor-normal-article',
            'status' => 'published',
            'body' => 'Normal body',
        ]);
        $blogResponse->assertSessionHasNoErrors();
        $this->assertDatabaseHas('contents', ['slug' => 'editor-normal-article']);

        // 2. Editor CANNOT create in sensitive categories
        foreach (['kontak', 'kampanye-darurat', 'donasi'] as $cat) {
            $response = $this->actingAs($editor)->post("/admin/{$cat}", [
                'title' => 'Malicious Config Edit',
                'slug' => 'malicious-'.$cat,
                'status' => 'published',
                'body' => 'Tampered body',
            ]);
            $response->assertStatus(403);
        }

        // 3. Editor CANNOT update or toggle status in sensitive categories
        foreach (['kontak', 'kampanye-darurat', 'donasi'] as $cat) {
            $sensitiveContent = Content::create([
                'title' => 'Official '.ucfirst($cat),
                'slug' => 'official-'.$cat,
                'category' => $cat,
                'status' => 'published',
                'body' => 'Official body',
            ]);

            $updateResponse = $this->actingAs($editor)->put("/admin/{$cat}/{$sensitiveContent->id}", [
                'title' => 'Tampered '.ucfirst($cat),
                'slug' => 'official-'.$cat,
                'status' => 'published',
                'body' => 'Tampered body',
            ]);
            $updateResponse->assertStatus(403);

            $toggleResponse = $this->actingAs($editor)->patch("/admin/{$cat}/{$sensitiveContent->id}/toggle-status");
            $toggleResponse->assertStatus(403);
        }
    }

    public function test_admin_can_modify_sensitive_organizational_categories(): void
    {
        $admin = User::factory()->admin()->create();

        foreach (['kontak', 'kampanye-darurat', 'donasi'] as $cat) {
            $createResponse = $this->actingAs($admin)->post("/admin/{$cat}", [
                'title' => 'Admin '.ucfirst($cat),
                'slug' => 'admin-'.$cat,
                'status' => 'published',
                'body' => 'Admin verified body',
            ]);
            $createResponse->assertSessionHasNoErrors();
            $this->assertDatabaseHas('contents', ['slug' => 'admin-'.$cat]);

            $content = Content::where('slug', 'admin-'.$cat)->first();

            $updateResponse = $this->actingAs($admin)->put("/admin/{$cat}/{$content->id}", [
                'title' => 'Admin Updated '.ucfirst($cat),
                'slug' => 'admin-'.$cat,
                'status' => 'published',
                'body' => 'Admin updated body',
            ]);
            $updateResponse->assertSessionHasNoErrors();

            $toggleResponse = $this->actingAs($admin)->patch("/admin/{$cat}/{$content->id}/toggle-status");
            $toggleResponse->assertStatus(302);
        }
    }

    public function test_admin_can_perform_administrative_and_export_actions(): void
    {
        $admin = User::factory()->admin()->create();

        $subscriber = Subscriber::create([
            'email' => 'sub_admin@example.com',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get('/admin/subscribers/export');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $response = $this->actingAs($admin)->delete("/admin/subscribers/{$subscriber->id}");
        $response->assertStatus(302);
        $this->assertSoftDeleted('subscribers', ['id' => $subscriber->id]);
    }

    public function test_draft_content_is_hidden_from_guests_and_subscribers_but_visible_to_editors(): void
    {
        $draft = Content::create([
            'title' => 'Secret Draft Investigation',
            'slug' => 'secret-draft-investigation',
            'category' => 'blog',
            'status' => 'draft',
            'body' => 'Confidential unpublished findings.',
        ]);

        // Guest gets 404
        $response = $this->get('/konten/secret-draft-investigation');
        $response->assertStatus(404);

        // Subscriber gets 404
        $subscriberUser = User::factory()->create();
        $response = $this->actingAs($subscriberUser)->get('/konten/secret-draft-investigation');
        $response->assertStatus(404);

        // Editor can view draft
        $editor = User::factory()->editor()->create();
        $response = $this->actingAs($editor)->get('/konten/secret-draft-investigation');
        $response->assertStatus(200)
            ->assertSee('Secret Draft Investigation');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 2. Mass Assignment Security Tests
    // ──────────────────────────────────────────────────────────────────────────

    public function test_user_cannot_escalate_role_via_profile_update(): void
    {
        $user = User::factory()->create();
        $this->assertFalse($user->isAdmin());

        $response = $this->actingAs($user)->patch('/profile', [
            'name' => 'Hacked Name',
            'email' => $user->email,
            'role' => 'admin',
        ]);

        $response->assertSessionHasNoErrors();
        $user->refresh();
        $this->assertNotSame('admin', $user->role);
        $this->assertFalse($user->isAdmin());
    }

    public function test_comment_submission_cannot_force_approved_status(): void
    {
        $content = Content::create([
            'title' => 'Sample Post',
            'slug' => 'sample-post',
            'category' => 'blog',
            'status' => 'published',
            'body' => 'Post body',
        ]);

        $response = $this->post("/konten/{$content->id}/komentar", [
            'author_name' => 'Attacker',
            'author_email' => 'attacker@example.com',
            'body' => 'Legitimate looking comment with malicious status attempt.',
            'status' => 'approved',
        ]);

        $response->assertSessionHas('comment_success');
        $this->assertDatabaseHas('comments', [
            'author_email' => 'attacker@example.com',
            'status' => 'pending',
        ]);
        $this->assertDatabaseMissing('comments', [
            'author_email' => 'attacker@example.com',
            'status' => 'approved',
        ]);
    }

    public function test_comments_on_unpublished_content_return_404_and_are_not_stored(): void
    {
        foreach (['draft', 'archived'] as $status) {
            $content = Content::create([
                'title' => "Unpublished {$status}",
                'slug' => "unpublished-{$status}",
                'category' => 'blog',
                'status' => $status,
                'body' => 'Hidden body',
            ]);

            $this->post("/konten/{$content->id}/komentar", [
                'author_name' => 'Probe',
                'author_email' => "probe-{$status}@example.com",
                'body' => 'Comment on an unpublished entry.',
            ])->assertNotFound();

            $this->assertDatabaseMissing('comments', ['content_id' => $content->id]);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 3. XSS & HTMLPurifier Sanitization Tests
    // ──────────────────────────────────────────────────────────────────────────

    public function test_htmlpurifier_renders_html5_figure_and_preserves_safe_html(): void
    {
        $content = new Content([
            'body' => '<p>Intro paragraph</p><figure class="my-4 text-center"><img src="https://example.com/photo.jpg" alt="Photo" /><figcaption>Image description</figcaption></figure><strong>Bold text</strong>',
        ]);

        $sanitized = $content->sanitized_body;

        $this->assertStringContainsString('<figure', $sanitized);
        $this->assertStringContainsString('<figcaption>Image description</figcaption>', $sanitized);
        $this->assertStringContainsString('https://example.com/photo.jpg', $sanitized);
        $this->assertStringContainsString('<strong>Bold text</strong>', $sanitized);
    }

    public function test_htmlpurifier_strips_dangerous_xss_payloads(): void
    {
        $maliciousPayloads = [
            '<script>alert("XSS")</script><p>Text</p>',
            '<img src="x" onerror="alert(document.cookie)" />',
            '<div onclick="evil()">Clickable</div>',
            '<a href="javascript:alert(1)">Evil Link</a>',
            '<a href="data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==">Data Link</a>',
            '<iframe src="https://attacker.com"></iframe>',
            '<object data="evil.swf"></object>',
            '<embed src="evil.swf">',
            '<svg onload="alert(1)"><circle r="10"/></svg>',
        ];

        foreach ($maliciousPayloads as $payload) {
            $content = new Content(['body' => $payload]);
            $sanitized = $content->sanitized_body;

            $this->assertStringNotContainsString('<script', $sanitized, "Failed stripping script tag in: {$payload}");
            $this->assertStringNotContainsString('onerror', $sanitized, "Failed stripping onerror in: {$payload}");
            $this->assertStringNotContainsString('onclick', $sanitized, "Failed stripping onclick in: {$payload}");
            $this->assertStringNotContainsString('javascript:', $sanitized, "Failed stripping javascript: URI in: {$payload}");
            $this->assertStringNotContainsString('data:text/html', $sanitized, "Failed stripping data: URI in: {$payload}");
            $this->assertStringNotContainsString('<iframe', $sanitized, "Failed stripping iframe in: {$payload}");
            $this->assertStringNotContainsString('<object', $sanitized, "Failed stripping object in: {$payload}");
            $this->assertStringNotContainsString('<embed', $sanitized, "Failed stripping embed in: {$payload}");
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 4. File Upload & Storage Security Tests
    // ──────────────────────────────────────────────────────────────────────────

    public function test_executable_and_svg_file_uploads_are_rejected(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $dangerousFiles = [
            UploadedFile::fake()->create('shell.php', 10, 'text/x-php'),
            UploadedFile::fake()->create('shell.phtml', 10, 'application/x-httpd-php'),
            UploadedFile::fake()->create('malicious.svg', 10, 'image/svg+xml'),
            UploadedFile::fake()->create('script.sh', 10, 'application/x-sh'),
            UploadedFile::fake()->create('.htaccess', 10, 'text/plain'),
        ];

        foreach ($dangerousFiles as $file) {
            $response = $this->actingAs($admin)->post('/admin/blog', [
                'title' => 'Test Post',
                'category' => 'blog',
                'status' => 'published',
                'image' => $file,
            ]);

            $response->assertSessionHasErrors('image');
        }
    }

    public function test_private_local_disk_is_not_served_over_public_http(): void
    {
        $this->assertFalse(config('filesystems.disks.local.serve', false));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 5. Payment Webhook Security & Idempotency Tests
    // ──────────────────────────────────────────────────────────────────────────

    public function test_donation_webhook_rejects_missing_or_invalid_signature(): void
    {
        config(['midtrans.server_key' => 'SB-Mid-server-TESTKEY123']);

        $donation = Donation::create([
            'order_id' => 'WALHI-DON-TEST-001',
            'donor_name' => 'Ahmad',
            'donor_email' => 'ahmad@example.com',
            'donor_phone' => '08123456789',
            'amount' => 50000,
            'status' => 'pending',
        ]);

        // Send forged signature
        $response = $this->postJson('/donasi/webhook', [
            'order_id' => 'WALHI-DON-TEST-001',
            'status_code' => '200',
            'gross_amount' => '50000.00',
            'signature_key' => 'invalid_forged_hash',
            'transaction_status' => 'settlement',
        ]);

        $response->assertStatus(403);
        $donation->refresh();
        $this->assertSame('pending', $donation->status);
    }

    public function test_donation_webhook_rejects_amount_mismatch(): void
    {
        $serverKey = 'SB-Mid-server-TESTKEY123';
        config(['midtrans.server_key' => $serverKey]);

        $donation = Donation::create([
            'order_id' => 'WALHI-DON-TEST-002',
            'donor_name' => 'Ahmad',
            'donor_email' => 'ahmad@example.com',
            'donor_phone' => '08123456789',
            'amount' => 50000,
            'status' => 'pending',
        ]);

        // Valid signature for 10000 but donation in DB is 50000
        $forgedAmount = '10000.00';
        $validSignatureForForgedAmount = hash('sha512', 'WALHI-DON-TEST-002200'.$forgedAmount.$serverKey);

        $response = $this->postJson('/donasi/webhook', [
            'order_id' => 'WALHI-DON-TEST-002',
            'status_code' => '200',
            'gross_amount' => $forgedAmount,
            'signature_key' => $validSignatureForForgedAmount,
            'transaction_status' => 'settlement',
        ]);

        $response->assertStatus(400);
        $donation->refresh();
        $this->assertSame('pending', $donation->status);
    }

    public function test_successful_donation_cannot_be_downgraded_by_webhook(): void
    {
        $serverKey = 'SB-Mid-server-TESTKEY123';
        config(['midtrans.server_key' => $serverKey]);

        $donation = Donation::create([
            'order_id' => 'WALHI-DON-TEST-003',
            'donor_name' => 'Ahmad',
            'donor_email' => 'ahmad@example.com',
            'donor_phone' => '08123456789',
            'amount' => 50000,
            'status' => 'success',
        ]);

        $grossAmount = '50000.00';
        $signature = hash('sha512', 'WALHI-DON-TEST-003200'.$grossAmount.$serverKey);
        Http::fake(['api.sandbox.midtrans.com/v2/*' => Http::response([
            'status_code' => '407', 'transaction_status' => 'expire', 'gross_amount' => $grossAmount,
        ])]);

        $response = $this->postJson('/donasi/webhook', [
            'order_id' => 'WALHI-DON-TEST-003',
            'status_code' => '200',
            'gross_amount' => $grossAmount,
            'signature_key' => $signature,
            'transaction_status' => 'expire',
        ]);

        $response->assertStatus(200);
        $donation->refresh();
        $this->assertSame('success', $donation->status);
    }

    public function test_duplicate_settlement_webhook_is_idempotent_and_does_not_mutate_state_twice(): void
    {
        $serverKey = 'SB-Mid-server-TESTKEY123';
        config(['midtrans.server_key' => $serverKey]);

        $donation = Donation::create([
            'order_id' => 'WALHI-DON-TEST-004',
            'donor_name' => 'Ahmad',
            'donor_email' => 'ahmad@example.com',
            'donor_phone' => '08123456789',
            'amount' => 75000,
            'status' => 'pending',
        ]);

        $grossAmount = '75000.00';
        $signature = hash('sha512', 'WALHI-DON-TEST-004200'.$grossAmount.$serverKey);
        Http::fake(['api.sandbox.midtrans.com/v2/*' => Http::response([
            'status_code' => '200', 'transaction_status' => 'settlement', 'payment_type' => 'qris', 'gross_amount' => $grossAmount,
        ])]);

        $payload = [
            'order_id' => 'WALHI-DON-TEST-004',
            'status_code' => '200',
            'gross_amount' => $grossAmount,
            'signature_key' => $signature,
            'transaction_status' => 'settlement',
            'payment_type' => 'qris',
        ];

        // First webhook call: transitions from pending to success
        $response1 = $this->postJson('/donasi/webhook', $payload);
        $response1->assertStatus(200);
        $donation->refresh();
        $this->assertSame('success', $donation->status);
        $this->assertSame('qris', $donation->payment_type);

        // Second duplicate webhook call (replay / network retry): returns 200, remains success, no duplicate record
        $response2 = $this->postJson('/donasi/webhook', $payload);
        $response2->assertStatus(200);
        $donation->refresh();
        $this->assertSame('success', $donation->status);
        $this->assertSame(1, Donation::where('order_id', 'WALHI-DON-TEST-004')->count());
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 6. CSV Formula Injection Sanitization Tests
    // ──────────────────────────────────────────────────────────────────────────

    public function test_csv_sanitizer_escapes_dangerous_formula_prefixes(): void
    {
        $maliciousInputs = [
            '=cmd|"/C calc"!A0' => "'=cmd|\"/C calc\"!A0",
            '+1234567890' => "'+1234567890",
            '-5+5' => "'-5+5",
            '@SUM(1,2)' => "'@SUM(1,2)",
            "\tTAB_INJECT" => "'\tTAB_INJECT",
            '|PIPE_INJECT' => "'|PIPE_INJECT",
            '%PERCENT_INJECT' => "'%PERCENT_INJECT",
        ];

        foreach ($maliciousInputs as $raw => $expected) {
            $this->assertSame($expected, CsvSanitizer::sanitize($raw));
        }

        // Safe strings remain unchanged
        $this->assertSame('clean_user@example.com', CsvSanitizer::sanitize('clean_user@example.com'));
        $this->assertSame('', CsvSanitizer::sanitize(''));
        $this->assertSame('', CsvSanitizer::sanitize(null));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 7. Security Headers & CSRF Tests
    // ──────────────────────────────────────────────────────────────────────────

    public function test_security_headers_are_present_in_response(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $this->assertTrue($response->headers->has('Content-Security-Policy'));

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("script-src 'self' 'nonce-", $csp);
        $this->assertStringContainsString("style-src 'self' 'unsafe-inline'", $csp);
    }

    public function test_script_tags_render_properly_in_views(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);

        $response->assertSee('<script', false);
    }

    public function test_auth_and_password_endpoints_have_noindex_headers(): void
    {
        $endpoints = [
            route('login'),
            '/forgot-password',
            '/reset-password/sample-token',
        ];

        foreach ($endpoints as $url) {
            $response = $this->get($url);
            $response->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet');
        }
    }

    public function test_comment_reply_rejects_cross_content_parent_id(): void
    {
        $contentA = Content::create([
            'title' => 'Article A',
            'slug' => 'article-a',
            'category' => 'blog',
            'status' => 'published',
            'body' => 'Body A',
        ]);

        $contentB = Content::create([
            'title' => 'Article B',
            'slug' => 'article-b',
            'category' => 'blog',
            'status' => 'published',
            'body' => 'Body B',
        ]);

        $commentA = Comment::create([
            'content_id' => $contentA->id,
            'author_name' => 'User A',
            'author_email' => 'usera@example.com',
            'body' => 'Comment on A',
            'status' => 'approved',
        ]);

        // Attempting to reply to Comment on A while submitting for Content B must be rejected
        $response = $this->post("/konten/{$contentB->id}/komentar", [
            'author_name' => 'Attacker',
            'author_email' => 'attacker@example.com',
            'body' => 'Cross-article IDOR reply attempt',
            'parent_id' => $commentA->id,
        ]);

        $response->assertSessionHasErrors('parent_id');
        $this->assertDatabaseMissing('comments', [
            'author_email' => 'attacker@example.com',
        ]);
    }

    public function test_blog_and_regulasi_search_endpoints_load_and_are_throttled(): void
    {
        $response = $this->get('/blog?kategori=air');
        $response->assertStatus(200);

        $responseRegulasi = $this->get('/regulasi?search=lingkungan');
        $responseRegulasi->assertStatus(200);
    }

    public function test_user_model_protects_role_from_mass_assignment_and_seeder_persists_admin(): void
    {
        // Mass assignment via constructor must NOT assign role
        $user = new User([
            'name' => 'Seeded Admin',
            'email' => 'custom_admin@example.com',
            'password' => 'secret123',
            'role' => 'admin',
        ]);
        $user->save();

        $this->assertNotEquals('admin', $user->fresh()->role);
        $this->assertFalse($user->fresh()->isAdmin());

        // Explicit assignment works securely
        $user->assignRole('admin');
        $user->save();
        $this->assertEquals('admin', $user->fresh()->role);
        $this->assertTrue($user->fresh()->isAdmin());

        // Seeder successfully creates admin & editor using secure assignment
        $this->seed(AdminUserSeeder::class);
        $seededAdmin = User::where('email', config('auth.admin_seed.email') ?: 'admin@walhijabar.or.id')->first();
        $this->assertNotNull($seededAdmin);
        $this->assertTrue($seededAdmin->isAdmin());

        $seededEditor = User::where('email', config('auth.editor_seed.email', 'editor@walhijabar.or.id'))->first();
        $this->assertNotNull($seededEditor);
        $this->assertTrue($seededEditor->canManageContent());
        $this->assertFalse($seededEditor->canDelete());
    }

    public function test_content_request_rejects_dangerous_image_url_protocols(): void
    {
        $admin = User::factory()->admin()->create();

        // javascript: scheme must be rejected
        $response1 = $this->actingAs($admin)->post('/admin/blog', [
            'title' => 'XSS Attack',
            'status' => 'published',
            'image_url' => 'javascript:alert(document.cookie)',
        ]);
        $response1->assertSessionHasErrors('image_url');

        // data: scheme must be rejected
        $response2 = $this->actingAs($admin)->post('/admin/blog', [
            'title' => 'XSS Attack 2',
            'status' => 'published',
            'image_url' => 'data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==',
        ]);
        $response2->assertSessionHasErrors('image_url');

        // vbscript: scheme must be rejected
        $response3 = $this->actingAs($admin)->post('/admin/blog', [
            'title' => 'XSS Attack 3',
            'status' => 'published',
            'image_url' => 'vbscript:msgbox(1)',
        ]);
        $response3->assertSessionHasErrors('image_url');

        // Protocol-relative //evil.example must be rejected
        $responseProto = $this->actingAs($admin)->post('/admin/blog', [
            'title' => 'Protocol Relative Attack',
            'status' => 'published',
            'image_url' => '//evil.example/photo.jpg',
        ]);
        $responseProto->assertSessionHasErrors('image_url');

        // Triple-slash ///evil.example must be rejected
        $responseTriple = $this->actingAs($admin)->post('/admin/blog', [
            'title' => 'Triple Slash Attack',
            'status' => 'published',
            'image_url' => '///evil.example/photo.jpg',
        ]);
        $responseTriple->assertSessionHasErrors('image_url');

        // Arbitrary unwhitelisted local path must be rejected
        $responseArbitrary = $this->actingAs($admin)->post('/admin/blog', [
            'title' => 'Arbitrary Path',
            'status' => 'published',
            'image_url' => '/etc/passwd',
        ]);
        $responseArbitrary->assertSessionHasErrors('image_url');

        // Positive allowlist: valid HTTPS external URL
        $responseSafe = $this->actingAs($admin)->post('/admin/blog', [
            'title' => 'Safe Article',
            'status' => 'published',
            'image_url' => 'https://images.unsplash.com/photo-sample.jpg',
        ]);
        $responseSafe->assertSessionHasNoErrors();

        // Positive allowlist: valid /storage/uploads/ path
        $responseLocal = $this->actingAs($admin)->post('/admin/blog', [
            'title' => 'Safe Local Article',
            'status' => 'published',
            'image_url' => '/storage/uploads/photo.jpg',
        ]);
        $responseLocal->assertSessionHasNoErrors();
    }

    public function test_content_model_sanitizes_dangerous_image_url_schemes(): void
    {
        $contentXss = new Content(['image_url' => 'javascript:alert(1)']);
        $this->assertNull($contentXss->image_url);

        $contentData = new Content(['image_url' => 'data:text/html,<script>alert(1)</script>']);
        $this->assertNull($contentData->image_url);

        // Protocol-relative URLs must sanitize to null
        $contentProto = new Content(['image_url' => '//evil.example/photo.jpg']);
        $this->assertNull($contentProto->image_url);

        $contentTriple = new Content(['image_url' => '///evil.example/photo.jpg']);
        $this->assertNull($contentTriple->image_url);

        // Arbitrary path outside whitelist must sanitize to null
        $contentArbitrary = new Content(['image_url' => '/var/log/app.log']);
        $this->assertNull($contentArbitrary->image_url);

        $contentSafe = new Content(['image_url' => 'https://example.com/safe.jpg']);
        $this->assertSame('https://example.com/safe.jpg', $contentSafe->image_url);

        $contentRelative = new Content(['image_url' => '/storage/uploads/safe.jpg']);
        $this->assertSame('/storage/uploads/safe.jpg', $contentRelative->image_url);

        $contentDoc = new Content(['image_url' => '/storage/documents/report.pdf']);
        $this->assertSame('/storage/documents/report.pdf', $contentDoc->image_url);
    }

    public function test_delete_old_image_prevents_path_traversal(): void
    {
        Storage::fake('public');

        // Put a sensitive file outside uploads/
        Storage::disk('public')->put('sensitive.txt', 'do not delete');
        // Put a legitimate file inside uploads/
        Storage::disk('public')->put('uploads/valid.jpg', 'image data');

        $admin = User::factory()->admin()->create();

        // Create content with path traversal in raw image_url
        $content = Content::create([
            'title' => 'Traversal Article',
            'slug' => 'traversal-article',
            'category' => 'blog',
            'status' => 'published',
            'body' => 'Test body',
            'image_url' => '/storage/uploads/../../sensitive.txt',
        ]);

        // Attempt to remove image via content update
        $response = $this->actingAs($admin)->put('/admin/blog/'.$content->id, [
            'title' => 'Updated Traversal Article',
            'slug' => 'traversal-article',
            'status' => 'published',
            'remove_image' => '1',
        ]);

        $response->assertSessionHasNoErrors();

        // The sensitive file outside uploads must NOT have been deleted
        Storage::disk('public')->assertExists('sensitive.txt');

        // Verify that legitimate deletion works as expected
        $legitContent = Content::create([
            'title' => 'Legit Article',
            'slug' => 'legit-article',
            'category' => 'blog',
            'status' => 'published',
            'body' => 'Legit body',
            'image_url' => '/storage/uploads/valid.jpg',
        ]);

        $legitRes = $this->actingAs($admin)->put('/admin/blog/'.$legitContent->id, [
            'title' => 'Updated Legit Article',
            'slug' => 'legit-article',
            'status' => 'published',
            'remove_image' => '1',
        ]);
        $legitRes->assertSessionHasNoErrors();

        Storage::disk('public')->assertMissing('uploads/valid.jpg');
    }

    public function test_upload_rejects_dangerous_extensions_and_mime_spoofing(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $dangerousFiles = [
            'shell.php' => '<?php echo "evil"; ?>',
            'shell.php5' => '<?php echo "evil"; ?>',
            'shell.phtml' => '<?php echo "evil"; ?>',
            'payload.phar' => '<?php echo "evil"; ?>',
            'exploit.svg' => '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>',
            '.htaccess' => 'SetHandler application/x-httpd-php',
            'script.sh' => "#!/bin/bash\nrm -rf /",
            'binary.exe' => 'MZ..........',
        ];

        foreach ($dangerousFiles as $filename => $content) {
            $file = UploadedFile::fake()->createWithContent($filename, $content);

            $response = $this->actingAs($admin)->post('/admin/blog', [
                'title' => 'Dangerous File Test '.$filename,
                'status' => 'published',
                'image' => $file,
            ]);

            $response->assertSessionHasErrors('image');
        }

        // Test MIME spoofing: PHP payload with spoofed image name
        $spoofedFile = UploadedFile::fake()->createWithContent('malicious.jpg', '<?php system($_GET["cmd"]); ?>');
        $spoofedResponse = $this->actingAs($admin)->post('/admin/blog', [
            'title' => 'Spoofed Mime Test',
            'status' => 'published',
            'image' => $spoofedFile,
        ]);
        $spoofedResponse->assertSessionHasErrors('image');
    }

    public function test_upload_separates_documents_and_images_storage(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        // 1. Upload valid image (should be stored in /storage/uploads/)
        $image = UploadedFile::fake()->image('nature.jpg', 640, 480);
        $imageRes = $this->actingAs($admin)->post('/admin/blog', [
            'title' => 'Article with Image',
            'status' => 'published',
            'image' => $image,
        ]);
        $imageRes->assertSessionHasNoErrors();

        $contentWithImage = Content::where('title', 'Article with Image')->first();
        $this->assertNotNull($contentWithImage);
        $this->assertStringStartsWith('/storage/uploads/', $contentWithImage->image_url);

        // 2. Upload valid PDF document (should be stored in /storage/documents/)
        $pdfContent = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Count 1/Kids[3 0 R]>>endobj\nxref\n0 3\ntrailer<</Size 3/Root 1 0 R>>\nstartxref\n100\n%%EOF";
        $pdf = UploadedFile::fake()->createWithContent('laporan.pdf', $pdfContent);
        $docRes = $this->actingAs($admin)->post('/admin/laporan-tahunan', [
            'title' => 'Laporan Tahunan 2025',
            'status' => 'published',
            'image' => $pdf,
        ]);
        $docRes->assertSessionHasNoErrors();

        $contentWithDoc = Content::where('title', 'Laporan Tahunan 2025')->first();
        $this->assertNotNull($contentWithDoc);
        $this->assertStringStartsWith('/storage/documents/', $contentWithDoc->image_url);
    }
}
