<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Content;
use App\Models\User;
use App\Rules\SafeUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Regression tests for audit issues #68 (banner URLs), #69 (stored body sanitization),
 * #73 (campaign/contact URLs), #76 (CSP) and #93 (sensitive category listing).
 */
class ContentSecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::Admin->value]);
        $this->editor = User::factory()->create(['role' => UserRole::Editor->value]);
    }

    public function test_safe_url_accepts_http_relative_and_anchor_links(): void
    {
        $this->assertTrue(SafeUrl::isSafe('https://walhijabar.co.id/kampanye'));
        $this->assertTrue(SafeUrl::isSafe('http://example.org'));
        $this->assertTrue(SafeUrl::isSafe('/publikasi/siaran-pers'));
        $this->assertTrue(SafeUrl::isSafe('#isu'));
    }

    public function test_safe_url_rejects_script_and_ambiguous_schemes(): void
    {
        foreach ([
            'javascript:alert(1)',
            'JaVaScRiPt:alert(1)',
            'javascript://comment%0aalert(1)',
            'data:text/html;base64,AAAA',
            'vbscript:msgbox(1)',
            '//evil.example',
            '/\\evil.example',
            ' javascript:alert(1)',
            "java\tscript:alert(1)",
            'ftp://example.org/file',
        ] as $url) {
            $this->assertFalse(SafeUrl::isSafe($url), "Expected unsafe: {$url}");
        }

        $this->assertFalse(SafeUrl::isSafe('/relative', allowRelative: false));
        $this->assertSame('#', SafeUrl::sanitize('javascript:alert(1)'));
    }

    public function test_banner_button_with_script_url_is_rejected(): void
    {
        $response = $this->actingAs($this->editor)->post('/admin/banner', [
            'title' => 'Banner Baru',
            'status' => 'published',
            'banner_btn1_text' => 'Klik',
            'banner_btn1_url' => 'javascript:alert(document.cookie)',
        ]);

        $response->assertSessionHasErrors('banner_btn1_url');
        $this->assertDatabaseMissing('contents', ['title' => 'Banner Baru']);
    }

    public function test_banner_button_with_safe_url_is_saved(): void
    {
        $response = $this->actingAs($this->editor)->post('/admin/banner', [
            'title' => 'Banner Aman',
            'status' => 'published',
            'banner_btn1_text' => 'Donasi',
            'banner_btn1_url' => '/dukung-kami/donasi-publik',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('contents', [
            'title' => 'Banner Aman',
            'tags' => 'Donasi|/dukung-kami/donasi-publik||',
        ]);
    }

    public function test_legacy_unsafe_banner_url_is_neutralised_on_homepage(): void
    {
        Content::create([
            'title' => 'Banner Lama',
            'slug' => 'banner-lama',
            'category' => 'banner',
            'status' => 'published',
            'tags' => 'Klik|javascript:alert(1)||',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('javascript:alert(1)', false);
    }

    public function test_body_is_sanitized_when_stored(): void
    {
        $this->actingAs($this->editor)->post('/admin/blog', [
            'title' => 'Artikel Berbahaya',
            'status' => 'draft',
            'body' => '<p>Halo</p><img src="x" onerror="alert(1)"><script>alert(2)</script>',
        ])->assertSessionHasNoErrors();

        $body = Content::where('title', 'Artikel Berbahaya')->value('body');

        $this->assertStringContainsString('<p>Halo</p>', $body);
        $this->assertStringNotContainsString('onerror', $body);
        $this->assertStringNotContainsString('<script', $body);
    }

    public function test_plain_text_body_is_stored_unchanged(): void
    {
        $plain = "email: kontak@walhijabar.co.id\nwhatsapp: +62 821-1982-1159";

        $this->actingAs($this->admin)->post('/admin/kontak', [
            'title' => 'Kontak',
            'status' => 'published',
            'body' => $plain,
        ])->assertSessionHasNoErrors();

        $this->assertSame($plain, Content::where('category', 'kontak')->value('body'));
    }

    public function test_plain_text_with_angle_brackets_is_not_entity_encoded(): void
    {
        $this->actingAs($this->editor)->post('/admin/statistik', [
            'title' => 'Target Emisi',
            'status' => 'published',
            'body' => 'target <50% di 2030',
        ])->assertSessionHasNoErrors();

        $this->assertSame('target <50% di 2030', Content::where('title', 'Target Emisi')->value('body'));
    }

    public function test_banner_tags_posted_directly_are_validated(): void
    {
        $this->actingAs($this->editor)->post('/admin/banner', [
            'title' => 'Banner Lewat Tags',
            'status' => 'published',
            'tags' => 'Klik|javascript:alert(1)||',
        ])->assertSessionHasErrors('tags');

        $this->assertDatabaseMissing('contents', ['title' => 'Banner Lewat Tags']);
    }

    public function test_sidebar_hides_admin_only_links_from_editors(): void
    {
        $editorHtml = $this->actingAs($this->editor)->get('/admin/blog')->assertOk()->getContent();
        $this->assertStringNotContainsString(route('admin.content.index', 'donasi'), $editorHtml);
        $this->assertStringNotContainsString(route('admin.content.index', 'kampanye-darurat'), $editorHtml);

        $adminHtml = $this->actingAs($this->admin)->get('/admin/blog')->assertOk()->getContent();
        $this->assertStringContainsString(route('admin.content.index', 'donasi'), $adminHtml);
    }

    public function test_admin_edit_payload_contains_sanitized_body_for_legacy_rows(): void
    {
        Content::create([
            'title' => 'Konten Lama',
            'slug' => 'konten-lama',
            'category' => 'blog',
            'status' => 'draft',
            'body' => '<p>ok</p><img src=x onerror=alert(1)>',
        ]);

        $html = $this->actingAs($this->admin)->get('/admin/blog')->assertOk()->getContent();

        $this->assertStringContainsString('Konten Lama', $html);
        $this->assertStringNotContainsString('onerror', $html);
    }

    public function test_campaign_link_with_script_url_is_rejected_and_neutralised(): void
    {
        $this->actingAs($this->admin)->post('/admin/kampanye-darurat', [
            'title' => 'Kampanye',
            'status' => 'published',
            'tags' => 'javascript://x%0aalert(1)',
        ])->assertSessionHasErrors('tags');

        Content::create([
            'title' => 'Kampanye Lama',
            'slug' => 'kampanye-lama',
            'category' => 'kampanye-darurat',
            'status' => 'published',
            'tags' => 'javascript://x%0aalert(1)',
        ]);
        Cache::forget('global_campaign');

        $this->get('/')->assertOk()->assertDontSee('javascript://', false);
    }

    public function test_campaign_title_is_not_double_encoded(): void
    {
        Content::create([
            'title' => 'Tolak Tambang & Galian',
            'slug' => 'tolak-tambang',
            'category' => 'kampanye-darurat',
            'status' => 'published',
            'tags' => 'https://walhijabar.co.id/kampanye',
        ]);
        Cache::forget('global_campaign');

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('Tolak Tambang &amp; Galian', $html);
        $this->assertStringNotContainsString('&amp;amp;', $html);
    }

    public function test_editor_cannot_open_sensitive_category_listings(): void
    {
        foreach (['donasi', 'kontak', 'kampanye-darurat'] as $category) {
            $this->actingAs($this->editor)->get("/admin/{$category}")->assertForbidden();
            $this->actingAs($this->admin)->get("/admin/{$category}")->assertOk();
        }

        $this->actingAs($this->editor)->get('/admin/blog')->assertOk();
    }

    public function test_csp_disallows_inline_scripts_and_handlers(): void
    {
        $response = $this->get('/');
        $csp = $response->headers->get('Content-Security-Policy');

        $this->assertStringNotContainsString("'unsafe-inline' https://app", $csp);
        $this->assertDoesNotMatchRegularExpression("/script-src [^;]*'unsafe-(inline|eval)'/", $csp);
        $this->assertStringContainsString("script-src-attr 'none'", $csp);
        $this->assertStringNotContainsString('https://cdn.jsdelivr.net ', $csp);
        $this->assertDoesNotMatchRegularExpression('/img-src[^;]*\bhttp:/', $csp);
    }

    public function test_public_pages_have_no_inline_event_handlers(): void
    {
        foreach (['/', '/blog', '/tentang-kami', '/kontak', '/pengaduan', '/dukung-kami/donasi-publik', '/publikasi/siaran-pers'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertDoesNotMatchRegularExpression('/\son[a-z]+="/', $html, "Inline handler found on {$url}");
        }
    }
}
