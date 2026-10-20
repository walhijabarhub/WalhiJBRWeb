<?php

namespace Tests\Feature;

use App\Enums\ContentCategory;
use App\Models\Content;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ContentDetailCacheTest extends TestCase
{
    use RefreshDatabase;

    private function content(string $category, string $slug, array $extra = []): Content
    {
        return Content::create([
            'title' => "Judul {$slug}",
            'slug' => $slug,
            'category' => $category,
            'status' => 'published',
            'body' => '<p>Isi</p>',
            'publish_date' => now()->subDays(count(Content::all())),
            ...$extra,
        ]);
    }

    public function test_related_and_sidebar_exclude_the_current_item_and_unpublished_content(): void
    {
        $current = $this->content(ContentCategory::Blog->value, 'artikel-utama');
        foreach (range(1, 6) as $i) {
            $this->content(ContentCategory::Blog->value, "blog-{$i}");
        }
        $this->content(ContentCategory::Blog->value, 'draf', ['status' => 'draft']);

        $response = $this->get(route('content.show', $current->slug))->assertOk();

        $related = $response->viewData('relatedNews');
        $sidebar = $response->viewData('sidebarNews');

        $this->assertCount(3, $related);
        $this->assertCount(3, $related->pluck('id')->unique());
        $this->assertNotContains($current->id, $related->pluck('id'));
        $this->assertNotContains('draf', $related->pluck('slug'));

        $this->assertCount(5, $sidebar);
        $this->assertNotContains($current->id, $sidebar->pluck('id'));
        $this->assertSame(['blog-1', 'blog-2', 'blog-3', 'blog-4', 'blog-5'], $sidebar->pluck('slug')->all());
    }

    public function test_related_is_topped_up_with_news_when_category_is_small(): void
    {
        $current = $this->content(ContentCategory::KertasPosisi->value, 'kertas-1');
        $this->content(ContentCategory::KertasPosisi->value, 'kertas-2');
        $this->content(ContentCategory::Blog->value, 'blog-1');
        $this->content(ContentCategory::SiaranPers->value, 'pers-1');

        $related = $this->get(route('content.show', $current->slug))->viewData('relatedNews');

        $this->assertEqualsCanonicalizing(['kertas-2', 'blog-1', 'pers-1'], $related->pluck('slug')->all());
    }

    public function test_detail_page_does_not_order_by_random_and_reuses_cached_lists(): void
    {
        $current = $this->content(ContentCategory::Blog->value, 'artikel-utama');
        $this->content(ContentCategory::Blog->value, 'blog-1');

        $this->get(route('content.show', $current->slug))->assertOk();

        DB::enableQueryLog();
        $this->get(route('content.show', $current->slug))->assertOk();
        $queries = collect(DB::getQueryLog())->pluck('query')->map(fn ($q) => strtolower($q));

        $this->assertEmpty($queries->filter(fn ($q) => str_contains($q, 'random()') || str_contains($q, 'rand()')));
        $this->assertEmpty($queries->filter(fn ($q) => str_contains($q, 'order by "publish_date" desc limit')), 'Sidebar and id pools must come from the cache.');
    }

    public function test_publishing_content_refreshes_the_cached_lists(): void
    {
        $current = $this->content(ContentCategory::Blog->value, 'artikel-utama');
        $this->get(route('content.show', $current->slug))->assertOk();

        $this->content(ContentCategory::Blog->value, 'artikel-baru', ['publish_date' => now()->addDay()]);

        $response = $this->get(route('content.show', $current->slug));
        $this->assertSame('artikel-baru', $response->viewData('sidebarNews')->first()->slug);
        $this->assertSame(['artikel-baru'], $response->viewData('relatedNews')->pluck('slug')->all());
    }
}
