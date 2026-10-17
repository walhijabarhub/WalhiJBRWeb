<?php

namespace App\Http\Controllers;

use App\Enums\ContentCategory;
use App\Models\Content;
use App\Services\Content\ContentCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Handles static-ish public pages that were previously inline closures
 * in routes/web.php. Extracting them here makes the routes file clean
 * and makes each page independently testable.
 */
class PageController extends Controller
{
    public function about()
    {
        $visiMisi = Content::ofCategory(ContentCategory::VisiMisi)
            ->published()
            ->first();

        return view('tentang-kami', compact('visiMisi'));
    }

    public function kontak()
    {
        return view('kontak');
    }

    public function pengaduan()
    {
        return view('pengaduan');
    }

    public function privacy()
    {
        return view('kebijakan-privasi');
    }

    public function transparency()
    {
        return view('transparansi-dana');
    }

    public function regulasi(Request $request)
    {
        $search = $request->input('search');
        $categoryFilter = $request->input('kategori');

        $query = Content::ofCategory(ContentCategory::Regulasi)->published();
        $totalRegulasi = Content::ofCategory(ContentCategory::Regulasi)->published()->count();

        if ($search) {
            $query->search((string) $search);
        }

        if ($categoryFilter) {
            $tagMap = [
                'undang-undang' => 'undang-undang',
                'peraturan-pemerintah' => 'peraturan pemerintah',
                'peraturan-daerah' => 'peraturan daerah',
                'keputusan-menteri' => 'keputusan menteri',
                'peraturan-menteri' => 'peraturan menteri',
            ];
            if (isset($tagMap[$categoryFilter])) {
                $mappedTag = $tagMap[$categoryFilter];
                $query->where('tags', 'like', "%{$mappedTag}%");
            }
        }

        $items = $query->orderBy('publish_date', 'desc')->paginate(10)->withQueryString();

        // Regulation type counts (separate targeted queries)
        $countUU = Content::ofCategory(ContentCategory::Regulasi)->published()
            ->where('tags', 'like', '%undang-undang%')->count();

        $countPP = Content::ofCategory(ContentCategory::Regulasi)->published()
            ->where('tags', 'like', '%peraturan pemerintah%')->count();

        $countPD = Content::ofCategory(ContentCategory::Regulasi)->published()
            ->where('tags', 'like', '%peraturan daerah%')->count();

        $countKM = Content::ofCategory(ContentCategory::Regulasi)->published()
            ->where(function ($q) {
                $q->where('tags', 'like', '%keputusan menteri%')
                    ->orWhere('tags', 'like', '%peraturan menteri%');
            })->count();

        return view('regulasi', compact('items', 'countUU', 'countPP', 'countPD', 'countKM', 'search', 'categoryFilter', 'totalRegulasi'));
    }

    public function siaranPers()
    {
        $items = Content::ofCategory(ContentCategory::SiaranPers)
            ->published()
            ->orderBy('publish_date', 'desc')
            ->paginate(9)
            ->withQueryString();

        return view('siaran-pers', compact('items'));
    }

    public function infografis()
    {
        $items = Content::ofCategory(ContentCategory::Infografis)
            ->published()
            ->orderBy('publish_date', 'desc')
            ->paginate(9)
            ->withQueryString();

        return view('infografis', compact('items'));
    }

    public function laporanTahunan()
    {
        $items = Content::ofCategory(ContentCategory::LaporanTahunan)
            ->published()
            ->orderBy('publish_date', 'desc')
            ->paginate(5)
            ->withQueryString();

        return view('laporan-tahunan', compact('items'));
    }

    public function kertasPosisi()
    {
        $items = Content::ofCategory(ContentCategory::KertasPosisi)
            ->published()
            ->orderBy('publish_date', 'desc')
            ->paginate(9)
            ->withQueryString();

        return view('kertas-posisi', compact('items'));
    }

    public function catatanKritis()
    {
        $items = Content::ofCategory(ContentCategory::CatatanKritis)
            ->published()
            ->orderBy('publish_date', 'desc')
            ->paginate(9)
            ->withQueryString();

        return view('catatan-kritis', compact('items'));
    }

    public function donasi()
    {
        return view('donasi');
    }

    public function sitemap()
    {
        $contents = ContentCache::remember(ContentCache::SITEMAP, now()->addHour(), fn () => Content::select('slug', 'updated_at')
            ->published()
            ->orderBy('updated_at', 'desc')
            ->get());

        return response()
            ->view('sitemap', compact('contents'))
            ->header('Content-Type', 'text/xml');
    }

    public function robots()
    {
        $path = public_path('robots.txt');

        if (file_exists($path)) {
            $content = file_get_contents($path);
            $content = str_replace('Sitemap: /sitemap.xml', 'Sitemap: '.url('sitemap.xml'), $content);
        } else {
            $sitemapUrl = url('sitemap.xml');
            $content = "User-agent: *\nAllow: /\nDisallow: /admin/\nDisallow: /login\nDisallow: /register\nDisallow: /profile\n\nSitemap: {$sitemapUrl}\n";
        }

        return response($content, 200)->header('Content-Type', 'text/plain');
    }
}
