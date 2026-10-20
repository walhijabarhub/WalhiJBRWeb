<?php

namespace Tests\Feature;

use App\Enums\ContentCategory;
use App\Models\Content;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentStorageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $editor;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin_storage@walhijabar.or.id',
        ]);

        $this->editor = User::factory()->create([
            'role' => 'editor',
            'email' => 'editor_storage@walhijabar.or.id',
        ]);

        $this->user = User::factory()->create([
            'role' => 'subscriber',
            'email' => 'user_storage@walhijabar.or.id',
        ]);
    }

    public function test_public_can_download_published_document(): void
    {
        Storage::disk('local')->put('documents/laporan-2025.pdf', '%PDF-1.4 Fake PDF Content');

        $content = Content::create([
            'title' => 'Laporan Tahunan 2025',
            'slug' => 'laporan-tahunan-2025',
            'category' => ContentCategory::LaporanTahunan->value,
            'status' => 'published',
            'image_url' => 'documents/laporan-2025.pdf',
        ]);

        $response = $this->get(route('documents.download', $content));

        $response->assertOk();
        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertStringContainsString('attachment;', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('laporan-tahunan-2025.pdf', $response->headers->get('Content-Disposition'));
    }

    public function test_unauthenticated_guest_gets_404_for_draft_document(): void
    {
        Storage::disk('local')->put('documents/draft-doc.pdf', 'Secret Draft Content');

        $content = Content::create([
            'title' => 'Draft Laporan Rahasia',
            'slug' => 'draft-laporan-rahasia',
            'category' => ContentCategory::LaporanTahunan->value,
            'status' => 'draft',
            'image_url' => 'documents/draft-doc.pdf',
        ]);

        // Unauthenticated guest must get 404 (preventing resource enumeration)
        $response = $this->get(route('documents.download', $content));

        $response->assertNotFound();
    }

    public function test_authorized_admin_can_download_draft_document(): void
    {
        Storage::disk('local')->put('documents/draft-doc.pdf', 'Secret Draft Content');

        $content = Content::create([
            'title' => 'Draft Laporan Internal',
            'slug' => 'draft-laporan-internal',
            'category' => ContentCategory::LaporanTahunan->value,
            'status' => 'draft',
            'image_url' => 'documents/draft-doc.pdf',
        ]);

        $response = $this->actingAs($this->admin)->get(route('documents.download', $content));

        $response->assertOk();
        $this->assertStringContainsString('no-cache', (string) $response->headers->get('Cache-Control'));
    }

    public function test_authorized_editor_can_download_regular_draft_document(): void
    {
        Storage::disk('local')->put('documents/editorial-draft.pdf', 'Editorial Draft Content');

        $content = Content::create([
            'title' => 'Kertas Posisi Editorial',
            'slug' => 'kertas-posisi-editorial',
            'category' => ContentCategory::KertasPosisi->value,
            'status' => 'draft',
            'image_url' => 'documents/editorial-draft.pdf',
        ]);

        $response = $this->actingAs($this->editor)->get(route('documents.download', $content));

        $response->assertOk();
    }

    public function test_editor_cannot_download_sensitive_category_draft_document(): void
    {
        Storage::disk('local')->put('documents/sensitive-donation-doc.pdf', 'Sensitive Finance Audit');

        $content = Content::create([
            'title' => 'Audit Keuangan Donasi Internal',
            'slug' => 'audit-keuangan-donasi-internal',
            'category' => ContentCategory::Donasi->value, // Sensitive category
            'status' => 'draft',
            'image_url' => 'documents/sensitive-donation-doc.pdf',
        ]);

        // Editor must be blocked with 403 Forbidden by ContentPolicy
        $response = $this->actingAs($this->editor)->get(route('documents.download', $content));

        $response->assertForbidden();
    }

    public function test_external_url_is_not_redirected_by_download_endpoint_returns_404(): void
    {
        $content = Content::create([
            'title' => 'Dokumen Eksternal Lembaga',
            'slug' => 'dokumen-eksternal-lembaga',
            'category' => ContentCategory::Regulasi->value,
            'status' => 'published',
            'image_url' => 'https://jdih.esdm.go.id/dokumen/uu-minerba.pdf',
        ]);

        // Download endpoint must refuse to act as an open redirector
        $response = $this->get(route('documents.download', $content));

        $response->assertNotFound();

        // But the Model's download_url must safely return the direct external link for the view
        $this->assertEquals('https://jdih.esdm.go.id/dokumen/uu-minerba.pdf', $content->download_url);
    }

    public function test_download_response_includes_nosniff_and_content_disposition_attachment(): void
    {
        Storage::disk('local')->put('documents/test-report.pdf', '%PDF-1.4 Content');

        $content = Content::create([
            'title' => 'Uji Header Dokumen',
            'slug' => 'uji-header-dokumen',
            'category' => ContentCategory::LaporanTahunan->value,
            'status' => 'published',
            'image_url' => 'documents/test-report.pdf',
        ]);

        $response = $this->get(route('documents.download', $content));

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertMatchesRegularExpression('/attachment;\s*filename="uji-header-dokumen\.pdf"/', $response->headers->get('Content-Disposition'));
    }

    public function test_filename_cannot_inject_crlf_or_http_headers(): void
    {
        Storage::disk('local')->put('documents/sample.pdf', '%PDF-1.4 Data');

        $content = Content::create([
            'title' => "Laporan\r\nSet-Cookie: attacker=1\r\nX-Injected: true",
            'slug' => 'laporan-injected-slug',
            'category' => ContentCategory::LaporanTahunan->value,
            'status' => 'published',
            'image_url' => 'documents/sample.pdf',
        ]);

        $response = $this->get(route('documents.download', $content));

        $response->assertOk();
        $disposition = $response->headers->get('Content-Disposition');

        $this->assertStringNotContainsString("\r", $disposition);
        $this->assertStringNotContainsString("\n", $disposition);
        $this->assertStringNotContainsString('Set-Cookie', $disposition);
        $this->assertStringNotContainsString('X-Injected', $disposition);
        $this->assertNull($response->headers->get('X-Injected'));
    }

    public function test_path_traversal_attempts_are_blocked(): void
    {
        $content = Content::create([
            'title' => 'Traversal Attempt',
            'slug' => 'traversal-attempt',
            'category' => ContentCategory::LaporanTahunan->value,
            'status' => 'published',
            'image_url' => '../../../../etc/passwd',
        ]);

        $response = $this->get(route('documents.download', $content));

        $response->assertNotFound();
    }

    public function test_uploading_pdf_stores_file_on_private_disk_not_public(): void
    {
        $file = UploadedFile::fake()->create('riset-lingkungan.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->admin)->post(
            route('admin.content.store', ['category' => ContentCategory::LaporanTahunan->value]),
            [
                'title' => 'Riset Lingkungan 2026',
                'slug' => 'riset-lingkungan-2026',
                'category' => ContentCategory::LaporanTahunan->value,
                'status' => 'published',
                'image' => $file,
            ]
        );

        $response->assertSessionHasNoErrors();

        $content = Content::where('slug', 'riset-lingkungan-2026')->firstOrFail();

        // Must be stored on local (private) disk
        $rawPath = (string) $content->getRawOriginal('image_url');
        $this->assertStringStartsWith('documents/', $rawPath);
        Storage::disk('local')->assertExists($rawPath);

        // Must NOT be stored in public disk
        Storage::disk('public')->assertMissing($rawPath);
        Storage::disk('public')->assertMissing('uploads/'.basename($rawPath));
    }

    public function test_legacy_file_on_public_disk_is_served_via_authorized_endpoint(): void
    {
        // Legacy file on public disk
        Storage::disk('public')->put('documents/legacy-report.pdf', 'Legacy Public Content');
        Storage::disk('local')->assertMissing('documents/legacy-report.pdf');

        $content = Content::create([
            'title' => 'Legacy Report 2023',
            'slug' => 'legacy-report-2023',
            'category' => ContentCategory::LaporanTahunan->value,
            'status' => 'published',
            'image_url' => '/storage/documents/legacy-report.pdf',
        ]);

        $response = $this->get(route('documents.download', $content));

        $response->assertOk();
        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
    }

    public function test_legacy_migration_command_moves_files_and_updates_database(): void
    {
        // Put legacy file on public disk
        Storage::disk('public')->put('documents/old-file.pdf', 'Old Document Content');

        // Create Content referencing public path
        $content = Content::create([
            'title' => 'Old File Content',
            'slug' => 'old-file-content',
            'category' => ContentCategory::LaporanTahunan->value,
            'status' => 'published',
            'image_url' => '/storage/documents/old-file.pdf',
        ]);

        // Run migration command with --delete-legacy
        $this->artisan('walhi:migrate-documents', ['--delete-legacy' => true])
            ->assertSuccessful();

        // File must now exist in private local disk
        Storage::disk('local')->assertExists('documents/old-file.pdf');

        // Legacy file must be removed from public disk
        Storage::disk('public')->assertMissing('documents/old-file.pdf');

        // Database record must be normalized
        $content->refresh();
        $this->assertEquals('documents/old-file.pdf', $content->getRawOriginal('image_url'));
    }
}
