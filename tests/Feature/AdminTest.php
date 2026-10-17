<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\User;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed database for testing queries
        $this->seed(ContentSeeder::class);

        // Authenticate user
        $user = User::factory()->admin()->create();
        $this->actingAs($user);
    }

    public function test_admin_dashboard_loads(): void
    {
        $response = $this->get('/admin');
        $response->assertStatus(200);
    }

    public function test_admin_blog_loads(): void
    {
        $response = $this->get('/admin/blog');
        $response->assertStatus(200);
    }

    public function test_admin_banner_loads(): void
    {
        $response = $this->get('/admin/banner');
        $response->assertStatus(200);
    }

    public function test_admin_donasi_loads(): void
    {
        $response = $this->get('/admin/donasi');
        $response->assertStatus(200);
    }

    public function test_admin_pekan_rakyat_loads(): void
    {
        $response = $this->get('/admin/pekan-rakyat');
        $response->assertStatus(200);
    }

    public function test_admin_sejarah_loads(): void
    {
        $response = $this->get('/admin/tentang/sejarah');
        $response->assertStatus(200);
    }

    public function test_admin_comments_loads(): void
    {
        $response = $this->get('/admin/comments');
        $response->assertStatus(200);
    }

    public function test_admin_subscribers_loads(): void
    {
        $response = $this->get('/admin/subscribers');
        $response->assertStatus(200);
    }

    public function test_content_image_upload_under_2mb_succeeds(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('cover.jpg', 800, 600)->size(1500); // 1.5 MB

        $response = $this->post('/admin/blog', [
            'title' => 'Artikel Uji Gambar',
            'slug' => 'artikel-uji-gambar',
            'status' => 'published',
            'body' => '<p>Isi artikel dengan gambar.</p>',
            'image' => $file,
            'publish_date' => '2026-08-22',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('contents', [
            'slug' => 'artikel-uji-gambar',
        ]);

        $content = Content::where('slug', 'artikel-uji-gambar')->first();
        $this->assertNotNull($content->image_url);
        $this->assertTrue(str_contains($content->image_url, '/storage/uploads/'));

        // Verify post detail page renders the image properly
        $publicRes = $this->get('/konten/artikel-uji-gambar');
        $publicRes->assertStatus(200);
        $publicRes->assertSee($content->image_url);
    }

    public function test_content_image_upload_over_2mb_is_rejected(): void
    {
        Storage::fake('public');

        $largeFile = UploadedFile::fake()->image('huge.jpg', 2000, 2000)->size(3000); // 3 MB (exceeds 2MB)

        $response = $this->post('/admin/blog', [
            'title' => 'Artikel Gambar Terlalu Besar',
            'slug' => 'artikel-gambar-besar',
            'status' => 'published',
            'body' => '<p>Test</p>',
            'image' => $largeFile,
            'publish_date' => '2026-08-22',
        ]);

        $response->assertSessionHasErrors('image');
    }

    public function test_editor_image_upload_under_2mb_returns_json_url(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('editor_photo.jpg', 600, 400)->size(1200); // 1.2 MB

        $response = $this->postJson('/admin/upload-image', [
            'image' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'url',
        ]);
        $this->assertTrue(str_contains($response->json('url'), '/storage/uploads/'));
    }

    public function test_editor_image_upload_over_2mb_is_rejected(): void
    {
        Storage::fake('public');

        $largeFile = UploadedFile::fake()->image('huge_photo.jpg', 3000, 3000)->size(3500); // 3.5 MB

        $response = $this->postJson('/admin/upload-image', [
            'image' => $largeFile,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('image');
    }

    public function test_admin_dashboard_renders_real_data_and_no_dummy_strings(): void
    {
        $response = $this->get('/admin');
        $response->assertStatus(200);

        // Ensure dummy mock names are completely gone
        $response->assertDontSee('Siti Rahayu');
        $response->assertDontSee('Budi Santoso');
        $response->assertDontSee('press@mediaindonesia.co.id');
        $response->assertDontSee('14,320');

        // Ensure real labels are present
        $response->assertSee('Aktivitas Terkini');
        $response->assertSee('Total Pembaca');
        $response->assertViewHas('recentActivities');
        $response->assertViewHas('stats');
    }

    public function test_admin_sidebar_accordion_menus_and_buttons_render_properly(): void
    {
        $response = $this->get('/admin');
        $response->assertStatus(200);

        // Submenu accordion triggers
        $response->assertSee('data-group-toggle="beranda"', false);
        $response->assertSee('data-group-toggle="publikasi"', false);
        $response->assertSee('data-group-toggle="dukung"', false);
        $response->assertSee('data-group-toggle="tentang"', false);

        // Submenu containers
        $response->assertSee('id="sub-beranda"', false);
        $response->assertSee('id="sub-publikasi"', false);
        $response->assertSee('id="sub-dukung"', false);
        $response->assertSee('id="sub-tentang"', false);

        // Chevrons with rotation
        $response->assertSee('id="chevron-beranda"', false);
        $response->assertSee('id="chevron-publikasi"', false);
        $response->assertSee('id="chevron-dukung"', false);
        $response->assertSee('id="chevron-tentang"', false);

        // Inline event handlers are blocked; behaviour is bound from resources/js/csp-actions.js
        $response->assertHeader('Content-Security-Policy');
        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("script-src-attr 'none'", $csp);
        $this->assertDoesNotMatchRegularExpression('/\son(click|submit|change|input|mouseover|mouseout)="/', $response->getContent());
    }
}
