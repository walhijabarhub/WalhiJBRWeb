<?php

namespace Tests\Feature;

use App\Enums\ContentCategory;
use App\Models\Content;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * A document whose file is not on disk must show a disabled "Dokumen belum tersedia"
 * button instead of a link that ends in 404; a present file or an external link keeps
 * the normal download button. Nothing is generated in place of a missing file.
 */
class MissingDocumentDisplayTest extends TestCase
{
    use RefreshDatabase;

    private const MISSING_LABEL = 'Dokumen belum tersedia';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
    }

    private function document(string $category, string $slug, ?string $imageUrl, bool $withFile = false): Content
    {
        if ($withFile && $imageUrl) {
            Storage::disk('local')->put('documents/'.basename($imageUrl), '%PDF-1.4 test');
        }

        return Content::create([
            'title' => 'Dokumen '.$slug,
            'slug' => $slug,
            'category' => $category,
            'status' => 'published',
            'image_url' => $imageUrl,
            'publish_date' => now()->toDateString(),
        ]);
    }

    public function test_has_download_reflects_the_file_on_disk(): void
    {
        $missing = $this->document(ContentCategory::Regulasi->value, 'tanpa-berkas', 'documents/tidak-ada.pdf');
        $present = $this->document(ContentCategory::Regulasi->value, 'ada-berkas', 'documents/ada.pdf', withFile: true);
        $external = $this->document(ContentCategory::KertasPosisi->value, 'tautan-luar', 'https://example.org/kertas.pdf');
        $legacy = $this->document(ContentCategory::Regulasi->value, 'berkas-lama', '/storage/documents/lama.pdf');
        Storage::disk('public')->put('documents/lama.pdf', '%PDF-1.4 legacy');
        $none = $this->document(ContentCategory::Regulasi->value, 'tanpa-lampiran', null);

        $this->assertFalse($missing->has_download);
        $this->assertTrue($present->has_download);
        $this->assertTrue($external->has_download);
        $this->assertTrue($legacy->has_download);
        $this->assertFalse($none->has_download);

        // Nothing is created in place of the missing file.
        Storage::disk('local')->assertMissing('documents/tidak-ada.pdf');
        $this->get(route('documents.download', $missing))->assertNotFound();
        $this->get(route('documents.download', $present))->assertOk();
    }

    public function test_regulasi_list_disables_button_for_missing_file(): void
    {
        $missing = $this->document(ContentCategory::Regulasi->value, 'perda-hilang', 'documents/perda-hilang.pdf');
        $present = $this->document(ContentCategory::Regulasi->value, 'perda-ada', 'documents/perda-ada.pdf', withFile: true);

        $this->get(route('regulasi'))
            ->assertOk()
            ->assertSee(self::MISSING_LABEL)
            ->assertDontSee(route('documents.download', $missing), false)
            ->assertSee(route('documents.download', $present), false);
    }

    public function test_laporan_tahunan_list_disables_button_for_missing_file(): void
    {
        $missing = $this->document(ContentCategory::LaporanTahunan->value, 'laporan-hilang', 'documents/laporan-hilang.pdf');

        $this->get(route('laporan-tahunan'))
            ->assertOk()
            ->assertSee(self::MISSING_LABEL)
            ->assertDontSee(route('documents.download', $missing), false);
    }

    public function test_card_lists_disable_attachment_button_for_missing_file(): void
    {
        $pages = [
            'kertas-posisi' => ContentCategory::KertasPosisi,
            'catatan-kritis' => ContentCategory::CatatanKritis,
            'siaran-pers' => ContentCategory::SiaranPers,
        ];

        foreach ($pages as $route => $category) {
            $missing = $this->document($category->value, "{$route}-hilang", "documents/{$route}-hilang.pdf");

            $this->get(route($route))
                ->assertOk()
                ->assertSee(self::MISSING_LABEL)
                ->assertDontSee(route('documents.download', $missing), false);
        }
    }

    public function test_detail_pages_disable_button_for_missing_file(): void
    {
        $cases = [
            ContentCategory::Regulasi->value => 'uu-hilang',
            ContentCategory::LaporanTahunan->value => 'laporan-detail-hilang',
            ContentCategory::Infografis->value => 'infografis-hilang',
        ];

        foreach ($cases as $category => $slug) {
            $missing = $this->document($category, $slug, "documents/{$slug}.pdf");

            $this->get(route('content.show', $slug))
                ->assertOk()
                ->assertSee(self::MISSING_LABEL)
                ->assertDontSee(route('documents.download', $missing), false);
        }
    }

    public function test_present_file_and_external_link_keep_the_download_button(): void
    {
        $present = $this->document(ContentCategory::Infografis->value, 'infografis-ada', 'documents/infografis-ada.pdf', withFile: true);
        $this->document(ContentCategory::KertasPosisi->value, 'kertas-luar', 'https://example.org/kertas-luar.pdf');

        $this->get(route('content.show', 'infografis-ada'))
            ->assertOk()
            ->assertSee(route('documents.download', $present), false)
            ->assertDontSee(self::MISSING_LABEL);

        $this->get(route('kertas-posisi'))
            ->assertOk()
            ->assertSee('https://example.org/kertas-luar.pdf', false);
    }

    public function test_pdf_attachment_is_never_rendered_as_an_image(): void
    {
        $this->document(ContentCategory::Infografis->value, 'infografis-pdf', 'documents/infografis-pdf.pdf', withFile: true);

        $this->get(route('infografis'))->assertOk()->assertDontSee('src="/storage/documents/infografis-pdf.pdf"', false);
        $this->get(route('content.show', 'infografis-pdf'))->assertOk()->assertDontSee('src="/storage/documents/infografis-pdf.pdf"', false);
    }
}
