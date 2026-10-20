<?php

namespace Tests\Feature;

use App\Enums\ContentCategory;
use App\Models\Content;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LazyImagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_defers_below_the_fold_backgrounds_but_not_the_hero(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('data-lazy-bg="http://localhost/assets/images/backgrounds/call-to-action-bg.jpg"', $html);
        $this->assertStringNotContainsString("url('http://localhost/assets/images/backgrounds/call-to-action-bg.jpg')", $html);
        // Hero slides stay eager: they are the largest element on first paint.
        $this->assertMatchesRegularExpression('/style="background-image: url\(\'[^\']+\'\);"/', $html);
    }

    public function test_lazy_background_loader_is_bundled(): void
    {
        $this->assertStringContainsString("import './lazy-backgrounds';", file_get_contents(resource_path('js/app.js')));
        $this->assertStringContainsString('IntersectionObserver', file_get_contents(resource_path('js/lazy-backgrounds.js')));
    }

    public function test_list_pages_load_first_row_eagerly_and_the_rest_lazily(): void
    {
        foreach (range(1, 5) as $i) {
            Content::create([
                'title' => "Siaran {$i}",
                'slug' => "siaran-{$i}",
                'category' => ContentCategory::SiaranPers->value,
                'status' => 'published',
                'body' => 'Isi',
                'image_url' => '/assets/images/blog/news-1-1.jpg',
                'publish_date' => now()->subDays($i),
            ]);
        }

        $html = $this->get(route('siaran-pers'))->assertOk()->getContent();

        $this->assertSame(3, substr_count($html, 'loading="eager"'));
        $this->assertSame(2, substr_count($html, 'loading="lazy"'));
    }
}
