<?php

namespace App\Http\Controllers;

use App\Enums\ContentCategory;
use App\Models\Content;
use App\Services\Content\ContentCache;
use App\Services\Content\ViewCounter;
use Artesaos\SEOTools\Facades\JsonLdMulti;
use Artesaos\SEOTools\Facades\OpenGraph;
use Artesaos\SEOTools\Facades\SEOMeta;
use Artesaos\SEOTools\Facades\TwitterCard;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class PublicContentController extends Controller
{
    public function home()
    {
        $sections = ContentCache::remember(ContentCache::HOME, now()->addMinutes(10), function () {
            // Only the featured item and six cards are shown, so never load every article.
            $news = Content::ofCategory(ContentCategory::newsValues())
                ->published()
                ->orderBy('is_promoted', 'desc')
                ->orderBy('publish_date', 'desc')
                ->take(7)
                ->get();

            return [
                'featuredNews' => $news->first(),
                'newsCards' => $news->skip(1)->values(),
                'reports' => Content::ofCategory(ContentCategory::LaporanTahunan)
                    ->published()
                    ->orderBy('publish_date', 'desc')
                    ->take(3)
                    ->get(),
                'sejarah' => Content::ofCategory(ContentCategory::Sejarah)
                    ->published()
                    ->first(),
                'stats' => Content::ofCategory(ContentCategory::Statistik)
                    ->published()
                    ->orderBy('id', 'asc')
                    ->get(),
                'issues' => Content::ofCategory(ContentCategory::IsuKritis)
                    ->published()
                    ->orderBy('id', 'asc')
                    ->get(),
                'banners' => Content::ofCategory(ContentCategory::Banner)
                    ->published()
                    ->orderBy('is_promoted', 'desc')
                    ->orderBy('id', 'asc')
                    ->get(),
            ];
        });

        return view('welcome', $sections);
    }

    public function blog(Request $request)
    {
        $categoryFilter = $request->input('kategori') ?: 'Semua';

        $query = Content::ofCategory(ContentCategory::Blog)->published();

        if ($categoryFilter && $categoryFilter !== 'Semua') {
            $query->search((string) $categoryFilter);
        }

        $items = $query->orderBy('publish_date', 'desc')->paginate(9)->withQueryString();

        return view('blog', compact('items', 'categoryFilter'));
    }

    public function show(string $slug)
    {
        // Eager-load approved top-level comments with their approved replies
        // to eliminate N+1 queries on the detail page.
        $item = Content::where('slug', $slug)
            ->withCount(['comments as approved_comments_count' => function ($q) {
                $q->where('status', 'approved');
            }])
            ->firstOrFail();

        // Non-published (draft/archived) entries follow ContentPolicy@view (sensitive
        // categories are admin-only); anyone without access gets 404, never 403.
        if ($item->status !== 'published' && Gate::denies('view', $item)) {
            abort(404);
        }

        // Increment views count with session deduplication
        $sessionKey = 'viewed_content_'.$item->id;
        if (! session()->has($sessionKey)) {
            ViewCounter::record($item);
            session()->put($sessionKey, true);
        }

        // Dynamic SEO Metadata Injection
        $title = $item->title.' - WALHI Jawa Barat';
        $description = $item->excerpt ?: Str::limit(strip_tags($item->body ?? ''), 155);
        $canonicalUrl = route('content.show', $item->slug);
        // A document (PDF, Office file) is never used as the share image.
        $imageUrl = $item->image_url && ! $item->isDocument()
            ? (str_starts_with($item->image_url, 'http') ? $item->image_url : asset($item->image_url))
            : asset('assets/images/resources/logo-2-walhi.png');
        $keywords = ! empty($item->tags) ? array_map('trim', explode(',', $item->tags)) : ['WALHI', 'Jawa Barat', 'Keadilan Ekologis', 'Lingkungan Hidup'];

        SEOMeta::setTitle($title, false);
        SEOMeta::setDescription($description);
        SEOMeta::setCanonical($canonicalUrl);
        SEOMeta::addKeyword($keywords);

        OpenGraph::setTitle($title);
        OpenGraph::setDescription($description);
        OpenGraph::setUrl($canonicalUrl);
        OpenGraph::addProperty('type', 'article');
        OpenGraph::addImage($imageUrl, ['height' => 630, 'width' => 1200]);
        OpenGraph::setArticle([
            'published_time' => $item->publish_date ? $item->publish_date->toIso8601String() : $item->created_at->toIso8601String(),
            'modified_time' => $item->updated_at->toIso8601String(),
            'author' => $item->author ?? 'WALHI Jawa Barat',
            'section' => $item->category instanceof \BackedEnum ? $item->category->value : (string) $item->category,
            'tag' => $keywords,
        ]);

        TwitterCard::setTitle($title);
        TwitterCard::setDescription($description);
        TwitterCard::setImage($imageUrl);
        TwitterCard::setType('summary_large_image');
        TwitterCard::setSite('@walhijabar');

        JsonLdMulti::setTitle($title);
        JsonLdMulti::setDescription($description);
        JsonLdMulti::setType('NewsArticle');
        JsonLdMulti::addImage($imageUrl);
        JsonLdMulti::addValue('datePublished', $item->publish_date ? $item->publish_date->toIso8601String() : $item->created_at->toIso8601String());
        JsonLdMulti::addValue('dateModified', $item->updated_at->toIso8601String());
        JsonLdMulti::addValue('headline', $item->title);
        JsonLdMulti::addValue('mainEntityOfPage', [
            '@type' => 'WebPage',
            '@id' => $canonicalUrl,
        ]);
        JsonLdMulti::addValue('author', [
            '@type' => 'Organization',
            'name' => $item->author ?? 'WALHI Jawa Barat',
        ]);
        JsonLdMulti::addValue('publisher', [
            '@type' => 'Organization',
            'name' => 'WALHI Jawa Barat',
            'logo' => [
                '@type' => 'ImageObject',
                'url' => asset('assets/images/resources/logo-2-walhi.png'),
            ],
        ]);

        // Read time is now an accessor on the model
        $readTime = $item->read_time;

        // Fetch approved top-level comments with pre-loaded approved replies
        $approvedComments = $item->comments()
            ->where('status', 'approved')
            ->whereNull('parent_id')
            ->with(['replies' => fn ($q) => $q->where('status', 'approved')->orderBy('created_at', 'asc')])
            ->orderBy('created_at', 'desc')
            ->get();

        $relatedNews = $this->relatedContent($item);

        // Sidebar: latest 5 published news. Six are cached so that excluding the
        // current article still leaves five.
        $sidebarNews = ContentCache::remember(ContentCache::SIDEBAR_NEWS, now()->addMinutes(10), fn () => Content::ofCategory(ContentCategory::newsValues())
            ->published()
            ->select(['id', 'title', 'slug', 'image_url', 'category', 'publish_date', 'created_at'])
            ->orderBy('publish_date', 'desc')
            ->take(6)
            ->get())
            ->reject(fn (Content $news) => $news->id === $item->id)
            ->take(5)
            ->values();

        return view('content-detail', compact('item', 'readTime', 'relatedNews', 'sidebarNews', 'approvedComments'));
    }

    /**
     * Three random published items from the same category, topped up with news.
     * Candidates come from cached id lists, so only a primary-key lookup hits the
     * database instead of ORDER BY RAND() over the whole category.
     */
    private function relatedContent(Content $item, int $limit = 3): Collection
    {
        $category = $item->category instanceof \BackedEnum ? $item->category->value : (string) $item->category;

        $pick = fn (array $ids, array $exclude, int $count) => collect($ids)->diff($exclude)->shuffle()->take($count)->values()->all();
        $idsOf = fn (string $pool, array|string $categories) => ContentCache::publishedIds($pool, fn () => Content::ofCategory($categories)
            ->published()
            ->orderBy('publish_date', 'desc')
            ->limit(200)
            ->pluck('id')
            ->all());

        $ids = $pick($idsOf($category, $category), [$item->id], $limit);

        if (count($ids) < $limit) {
            $ids = [...$ids, ...$pick($idsOf(ContentCache::NEWS_POOL, ContentCategory::newsValues()), [$item->id, ...$ids], $limit - count($ids))];
        }

        if ($ids === []) {
            return new Collection;
        }

        $found = Content::published()->whereIn('id', $ids)->get()->keyBy('id');

        return new Collection(array_values(array_filter(array_map(fn (int $id) => $found->get($id), $ids))));
    }
}
