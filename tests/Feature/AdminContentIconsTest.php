<?php

namespace Tests\Feature;

use App\Enums\ContentCategory;
use App\Models\Content;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminContentIconsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->admin()->create());
    }

    private function content(string $category, string $slug, string $tags): void
    {
        Content::create([
            'title' => $slug,
            'slug' => $slug,
            'category' => $category,
            'status' => 'published',
            'body' => 'Isi',
            'tags' => $tags,
        ]);
    }

    /** @return array<int, string> */
    private function iconSources(string $html): array
    {
        preg_match_all('#<img src="http://localhost/([^"]+)" alt="" class="w-4 h-4"#', $html, $m);

        return $m[1];
    }

    public function test_issue_icons_use_the_home_page_rules_and_exist(): void
    {
        $this->content(ContentCategory::IsuKritis->value, 'dengan-ikon', 'Icon-7.svg|2.500+ Kasus');
        $this->content(ContentCategory::IsuKritis->value, 'hanya-badge', '200+ Titik');
        $this->content(ContentCategory::IsuKritis->value, 'ikon-tidak-sah', '../../.env|Badge');

        $html = $this->get(route('admin.content.index', 'isu-kritis'))->assertOk()->getContent();
        $icons = $this->iconSources($html);

        $this->assertCount(3, $icons);
        $this->assertContains('iqon/Icon-7.svg', $icons);
        foreach ($icons as $icon) {
            $this->assertMatchesRegularExpression('#^iqon/Icon-\d+\.svg$#', $icon);
            $this->assertFileExists(public_path($icon));
        }
        $this->assertStringContainsString('200+ Titik', $html);
        $this->assertStringNotContainsString('assets/images/icons/', $html);
    }

    public function test_statistic_icons_exist(): void
    {
        $this->content(ContentCategory::Statistik->value, 'stat-1', '120+');
        $this->content(ContentCategory::Statistik->value, 'stat-2', '45');

        $icons = $this->iconSources($this->get(route('admin.content.index', 'statistik'))->assertOk()->getContent());

        $this->assertCount(2, $icons);
        foreach ($icons as $icon) {
            $this->assertFileExists(public_path($icon));
        }
    }

    public function test_image_preview_has_no_empty_src(): void
    {
        $html = $this->get(route('admin.content.index', 'blog'))->assertOk()->getContent();

        $this->assertStringContainsString('id="image-preview-el"', $html);
        $this->assertStringNotContainsString('src=""', $html);
    }
}
