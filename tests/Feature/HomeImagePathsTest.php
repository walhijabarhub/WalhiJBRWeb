<?php

namespace Tests\Feature;

use App\Enums\ContentCategory;
use App\Models\Content;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Every bundled image the home page points to must exist in public/, both for the
 * built-in fallbacks and for isu-kritis entries that store a bare theme file name.
 */
class HomeImagePathsTest extends TestCase
{
    use RefreshDatabase;

    private function missingBundledImages(string $html): array
    {
        preg_match_all('#https?://[^/"\'\s]+/((?:assets|iqon)/[^"\'\s)]+\.(?:jpe?g|png|svg|webp|gif))#i', $html, $matches);

        return collect($matches[1])
            ->unique()
            ->reject(fn (string $path) => is_file(public_path(urldecode($path))))
            ->values()
            ->all();
    }

    public function test_fallback_images_exist(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame([], $this->missingBundledImages($html));
    }

    public function test_issue_with_bare_theme_file_name_uses_bundled_image(): void
    {
        Content::create([
            'title' => 'Pertambangan Ilegal',
            'slug' => 'pertambangan-ilegal-isu',
            'category' => ContentCategory::IsuKritis->value,
            'status' => 'published',
            'body' => 'Isi',
            'tags' => 'Icon-4.svg|200+ Titik',
            'image_url' => 'causes-1-1.jpg',
        ]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('assets/images/resources/causes-1-1.jpg', $html);
        $this->assertStringNotContainsString('/storage/causes-1-1.jpg', $html);
        $this->assertSame([], $this->missingBundledImages($html));
    }
}
