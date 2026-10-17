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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PublicContentController extends Controller
{
    public function home()
    {
        $sections = Cache::remember(ContentCache::HOME, now()->addMinutes(10), function () {
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

        // Ensure non-published (draft/archived) articles are not accessible to public guests
        if ($item->status !== 'published') {
            $user = auth()->user();
            if (! $user || ! $user->canManageContent()) {
                abort(404);
            }
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
        $imageUrl = $item->image_url
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

        // Related content (same category, exclude current)
        $relatedNews = Content::ofCategory($item->category)
            ->published()
            ->where('id', '!=', $item->id)
            ->inRandomOrder()
            ->take(3)
            ->get();

        // Top-up with blog/siaran-pers if not enough related content
        if ($relatedNews->count() < 3) {
            $extraNews = Content::ofCategory(ContentCategory::newsValues())
                ->published()
                ->where('id', '!=', $item->id)
                ->whereNotIn('id', $relatedNews->pluck('id'))
                ->inRandomOrder()
                ->take(3 - $relatedNews->count())
                ->get();
            $relatedNews = $relatedNews->merge($extraNews);
        }

        // Sidebar: latest 5 published news
        $sidebarNews = Content::ofCategory(ContentCategory::newsValues())
            ->published()
            ->where('id', '!=', $item->id)
            ->orderBy('publish_date', 'desc')
            ->take(5)
            ->get();

        return view('content-detail', compact('item', 'readTime', 'relatedNews', 'sidebarNews', 'approvedComments'));
    }
}
