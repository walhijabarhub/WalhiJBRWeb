<?php

namespace Tests\Feature;

use App\Enums\ContentCategory;
use App\Models\Content;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Regression tests for the October 2026 production audit (AUD-01 … AUD-06):
 * shared files, private document references, legacy public documents and
 * access to unpublished sensitive content.
 */
class StorageIntegrityAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');

        $this->admin = User::factory()->create(['role' => 'admin', 'email' => 'admin_audit@walhijabar.or.id']);
        $this->editor = User::factory()->create(['role' => 'editor', 'email' => 'editor_audit@walhijabar.or.id']);
    }

    private function makeContent(array $attributes): Content
    {
        static $n = 0;
        $n++;

        return Content::create(array_merge([
            'title' => "Konten Audit {$n}",
            'slug' => "konten-audit-{$n}",
            'category' => ContentCategory::Blog->value,
            'status' => 'published',
        ], $attributes));
    }

    private function updatePayload(Content $content, array $overrides = []): array
    {
        return array_merge([
            'title' => $content->title,
            'slug' => $content->slug,
            'status' => $content->status,
        ], $overrides);
    }

    // ── AUD-01: shared files must survive removal from one entry ─────────────

    public function test_removing_image_keeps_file_still_used_by_another_content(): void
    {
        Storage::disk('public')->put('uploads/shared.jpg', 'img');
        $a = $this->makeContent(['image_url' => '/storage/uploads/shared.jpg']);
        $this->makeContent(['image_url' => '/storage/uploads/shared.jpg']);

        $this->actingAs($this->editor)
            ->put(route('admin.content.update', ['category' => 'blog', 'content' => $a]), $this->updatePayload($a, ['remove_image' => '1']))
            ->assertSessionHasNoErrors();

        $this->assertNull($a->fresh()->getRawOriginal('image_url'));
        Storage::disk('public')->assertExists('uploads/shared.jpg');
    }

    public function test_deleting_content_keeps_file_embedded_in_another_body(): void
    {
        Storage::disk('public')->put('uploads/inline.jpg', 'img');
        $a = $this->makeContent(['image_url' => '/storage/uploads/inline.jpg']);
        $this->makeContent(['body' => '<p><img src="/storage/uploads/inline.jpg"></p>']);

        $this->actingAs($this->admin)
            ->delete(route('admin.content.destroy', ['category' => 'blog', 'content' => $a]))
            ->assertSessionHasNoErrors();

        $this->assertSoftDeleted($a);
        Storage::disk('public')->assertExists('uploads/inline.jpg');
    }

    public function test_file_of_soft_deleted_content_is_kept_for_restore(): void
    {
        Storage::disk('public')->put('uploads/trashed.jpg', 'img');
        $a = $this->makeContent(['image_url' => '/storage/uploads/trashed.jpg']);
        $this->makeContent(['image_url' => 'uploads/trashed.jpg'])->delete();

        $this->actingAs($this->editor)
            ->put(route('admin.content.update', ['category' => 'blog', 'content' => $a]), $this->updatePayload($a, ['remove_image' => '1']))
            ->assertSessionHasNoErrors();

        Storage::disk('public')->assertExists('uploads/trashed.jpg');
    }

    public function test_unshared_image_is_still_removed(): void
    {
        Storage::disk('public')->put('uploads/own.jpg', 'img');
        $a = $this->makeContent(['image_url' => '/storage/uploads/own.jpg']);

        $this->actingAs($this->admin)
            ->delete(route('admin.content.destroy', ['category' => 'blog', 'content' => $a]));

        Storage::disk('public')->assertMissing('uploads/own.jpg');
    }

    // ── AUD-02: a private document cannot be re-attached to another entry ────

    public function test_editor_cannot_attach_private_document_of_another_content(): void
    {
        Storage::disk('local')->put('documents/rahasia.pdf', '%PDF-1.4 draft');
        $this->makeContent([
            'category' => ContentCategory::KertasPosisi->value,
            'status' => 'draft',
            'image_url' => 'documents/rahasia.pdf',
        ]);

        // The download endpoint resolves any local non-"uploads/" path to documents/<basename>,
        // so paths under other prefixes must be rejected too.
        $paths = [
            'documents/rahasia.pdf', '/storage/documents/rahasia.pdf', '/documents/rahasia.pdf',
            'storage/documents/rahasia.pdf', '/assets/rahasia.pdf', '/storage/lain/rahasia.pdf',
        ];

        foreach ($paths as $i => $path) {
            $this->actingAs($this->editor)
                ->post(route('admin.content.store', ['category' => 'blog']), [
                    'title' => "Salinan {$i}",
                    'status' => 'published',
                    'image_url' => $path,
                ])
                ->assertSessionHasErrors('image_url');
        }

        $this->assertSame(0, Content::where('title', 'like', 'Salinan%')->count());
    }

    public function test_editor_cannot_switch_existing_content_to_another_private_document(): void
    {
        Storage::disk('local')->put('documents/rahasia.pdf', '%PDF-1.4 draft');
        $this->makeContent(['status' => 'draft', 'image_url' => 'documents/rahasia.pdf']);
        $own = $this->makeContent(['image_url' => '/storage/uploads/cover.jpg']);

        $this->actingAs($this->editor)
            ->put(route('admin.content.update', ['category' => 'blog', 'content' => $own]), $this->updatePayload($own, ['image_url' => 'documents/rahasia.pdf']))
            ->assertSessionHasErrors('image_url');

        $this->assertSame('/storage/uploads/cover.jpg', $own->fresh()->getRawOriginal('image_url'));
    }

    public function test_editing_document_content_resubmitting_its_own_path_is_allowed(): void
    {
        Storage::disk('local')->put('documents/milik-sendiri.pdf', '%PDF-1.4 own');
        $doc = $this->makeContent([
            'category' => ContentCategory::KertasPosisi->value,
            'image_url' => 'documents/milik-sendiri.pdf',
        ]);

        // The edit modal sends the accessor form (/storage/documents/...) back unchanged.
        $this->actingAs($this->editor)
            ->put(route('admin.content.update', ['category' => 'kertas-posisi', 'content' => $doc]), $this->updatePayload($doc, [
                'title' => 'Judul Baru',
                'image_url' => $doc->image_url,
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame('Judul Baru', $doc->fresh()->title);
        Storage::disk('local')->assertExists('documents/milik-sendiri.pdf');
        $this->get(route('documents.download', $doc->fresh()))->assertOk();
    }

    // ── AUD-03: nginx must not serve legacy documents from the public disk ───

    public function test_nginx_blocks_direct_access_to_public_documents_folder(): void
    {
        $conf = file_get_contents(base_path('docker/nginx/snippets/laravel.conf'));

        $this->assertMatchesRegularExpression(
            '#location\s+\^~\s+/storage/documents/\s*\{[^}]*return\s+404;#',
            $conf,
            'Legacy documents on the public disk must only be reachable through /dokumen/{slug}/unduh.'
        );
    }

    // ── AUD-04: legacy document migration is verifiable and non-destructive ──

    public function test_migrate_documents_dry_run_changes_nothing(): void
    {
        Storage::disk('public')->put('documents/lama.pdf', 'legacy');
        $c = $this->makeContent(['category' => ContentCategory::LaporanTahunan->value, 'image_url' => '/storage/documents/lama.pdf']);

        $this->artisan('walhi:migrate-documents', ['--dry-run' => true, '--delete-legacy' => true])
            ->assertSuccessful();

        Storage::disk('local')->assertMissing('documents/lama.pdf');
        Storage::disk('public')->assertExists('documents/lama.pdf');
        $this->assertSame('/storage/documents/lama.pdf', $c->fresh()->getRawOriginal('image_url'));
    }

    public function test_migrate_documents_never_overwrites_a_different_private_file(): void
    {
        Storage::disk('public')->put('documents/bentrok.pdf', 'legacy version');
        Storage::disk('local')->put('documents/bentrok.pdf', 'private version');

        $this->artisan('walhi:migrate-documents', ['--delete-legacy' => true])
            ->assertFailed();

        $this->assertSame('private version', Storage::disk('local')->get('documents/bentrok.pdf'));
        Storage::disk('public')->assertExists('documents/bentrok.pdf');
    }

    public function test_migrate_documents_removes_legacy_copy_only_when_identical(): void
    {
        Storage::disk('public')->put('documents/sama.pdf', 'same bytes');
        Storage::disk('local')->put('documents/sama.pdf', 'same bytes');

        $this->artisan('walhi:migrate-documents', ['--delete-legacy' => true])
            ->assertSuccessful();

        Storage::disk('public')->assertMissing('documents/sama.pdf');
        $this->assertSame('same bytes', Storage::disk('local')->get('documents/sama.pdf'));
    }

    // ── AUD-05: unpublished sensitive content follows ContentPolicy@view ─────

    public function test_editor_cannot_preview_unpublished_sensitive_content(): void
    {
        $draft = $this->makeContent(['category' => ContentCategory::Kontak->value, 'status' => 'draft']);

        $this->actingAs($this->editor)->get(route('content.show', $draft->slug))->assertNotFound();
        $this->actingAs($this->admin)->get(route('content.show', $draft->slug))->assertOk();
    }

    // ── AUD-06: a document is never advertised as the share image ───────────

    public function test_document_path_is_not_used_as_share_image(): void
    {
        Storage::disk('local')->put('documents/laporan.pdf', '%PDF-1.4');
        $doc = $this->makeContent([
            'category' => ContentCategory::LaporanTahunan->value,
            'image_url' => 'documents/laporan.pdf',
        ]);

        $this->get(route('content.show', $doc->slug))
            ->assertOk()
            ->assertDontSee('/storage/documents/laporan.pdf', false);
    }
}
