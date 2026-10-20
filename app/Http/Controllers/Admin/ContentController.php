<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreContentRequest;
use App\Http\Requests\Admin\UpdateContentRequest;
use App\Models\Content;
use App\Models\Donation;
use App\Services\AuditLogService;
use App\Services\Content\HtmlSanitizer;
use App\Services\Content\ImageOptimizer;
use App\Services\Content\SlugService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ContentController extends Controller
{
    public function __construct(private readonly SlugService $slugService) {}

    /**
     * Validate category string against ContentCategory enum cases.
     */
    private function validateCategory(string $category): ContentCategory
    {
        $enum = ContentCategory::tryFrom($category);
        abort_if(! $enum, 404, 'Kategori tidak valid atau tidak ditemukan.');

        return $enum;
    }

    /**
     * Map of category slugs to display titles and descriptions.
     * Used to drive the Admin UI labels.
     */
    protected array $config = [
        'blog' => ['title' => 'Blog',              'desc' => 'Kelola artikel dan tulisan di halaman Blog.'],
        'regulasi' => ['title' => 'Regulasi',           'desc' => 'Kelola dokumen peraturan dan regulasi lingkungan.'],
        'siaran-pers' => ['title' => 'Siaran Pers',        'desc' => 'Kelola siaran pers dan rilis media WALHI Jabar.'],
        'infografis' => ['title' => 'Infografis',         'desc' => 'Kelola infografis dan visualisasi data lingkungan.'],
        'kertas-posisi' => ['title' => 'Kertas Posisi',      'desc' => 'Kelola dokumen posisi kebijakan WALHI Jabar.'],
        'newsletter' => ['title' => 'E-Newsletter',       'desc' => 'Kelola edisi E-Newsletter WALHI Jabar.'],
        'buletin-bumi' => ['title' => 'Buletin Bumi',       'desc' => 'Kelola edisi Buletin Bumi.'],
        'jurnal' => ['title' => 'Jurnal Tanah Air',   'desc' => 'Kelola edisi Jurnal Tanah Air.'],
        'laporan-tahunan' => ['title' => 'Laporan Tahunan',    'desc' => 'Kelola laporan tahunan organisasi.'],
        'sejarah' => ['title' => 'Sejarah',            'desc' => 'Kelola halaman Sejarah organisasi.'],
        'visi-misi' => ['title' => 'Visi & Misi',        'desc' => 'Kelola konten Visi dan Misi.'],
        'dewan-nasional' => ['title' => 'Dewan Nasional',     'desc' => 'Kelola data anggota Dewan Nasional.'],
        'eksekutif-nasional' => ['title' => 'Eksekutif Nasional', 'desc' => 'Kelola data Eksekutif Nasional.'],
        'eksekutif-daerah' => ['title' => 'Eksekutif Daerah',  'desc' => 'Kelola data Eksekutif Daerah.'],
        'kontak' => ['title' => 'Kontak',             'desc' => 'Kelola informasi kontak organisasi.'],
        'donasi' => ['title' => 'Kampanye Donasi',    'desc' => 'Manajemen kampanye donasi dan laporan penerimaan.'],
        'pekan-rakyat' => ['title' => 'Pekan Rakyat',       'desc' => 'Manajemen event Pekan Rakyat.'],
        'statistik' => ['title' => 'Statistik Utama',    'desc' => 'Kelola angka-angka statistik utama di halaman Beranda.'],
        'isu-kritis' => ['title' => 'Isu Kritis',         'desc' => 'Kelola 5 isu kritis lingkungan di halaman Beranda.'],
        'kampanye-darurat' => ['title' => 'Kampanye Darurat',   'desc' => 'Kelola teks & link Kampanye Darurat di bar navigasi atas.'],
        'banner' => ['title' => 'Banner Beranda',     'desc' => 'Kelola slide banner hero interaktif di halaman utama Beranda.'],
    ];

    private function getCategoryConfig(string $category): array
    {
        return $this->config[$category] ?? ['title' => Str::title($category), 'desc' => ''];
    }

    // ──────────────────────────────────────────────────────────────────────────

    public function index(Request $request, string $category)
    {
        $this->validateCategory($category);
        $this->authorize('viewCategory', [Content::class, $category]);
        $config = $this->getCategoryConfig($category);
        $search = $request->input('search');

        $query = Content::where('category', $category);

        if ($search) {
            $escapedSearch = str_replace(['%', '_'], ['\%', '\_'], $search);
            $query->where(function ($q) use ($escapedSearch) {
                $q->where('title', 'like', "%{$escapedSearch}%")
                    ->orWhere('tags', 'like', "%{$escapedSearch}%");
            });
        }

        $items = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        // Status counts (consolidated into a single query via conditional aggregation)
        $rawCounts = Content::where('category', $category)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
                SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) as archived
            ")
            ->first();

        $counts = [
            'total' => (int) ($rawCounts->total ?? 0),
            'published' => (int) ($rawCounts->published ?? 0),
            'draft' => (int) ($rawCounts->draft ?? 0),
            'archived' => (int) ($rawCounts->archived ?? 0),
        ];

        // Donation-specific dashboard
        if ($category === 'donasi') {
            $recentDonations = Donation::orderBy('created_at', 'desc')->take(10)->get();
            $donationTotals = Donation::successful()->toBase()
                ->selectRaw('COALESCE(SUM(amount), 0) AS amount, COUNT(DISTINCT donor_email) AS donors, COALESCE(AVG(amount), 0) AS average')
                ->first();
            $totalAmount = (int) $donationTotals->amount;
            $uniqueDonors = (int) $donationTotals->donors;
            $avgDonation = (float) $donationTotals->average;

            // Monthly trend — last 12 months (real data only)
            [$chartLabels, $chartData] = Donation::monthlySuccessChart();

            return view('admin.donasi.index', compact(
                'items', 'config', 'counts', 'category',
                'recentDonations', 'totalAmount', 'uniqueDonors', 'avgDonation',
                'chartLabels', 'chartData'
            ));
        }

        if ($category === 'pekan-rakyat') {
            return view('admin.pekan-rakyat.index', compact('items', 'config', 'counts', 'category'));
        }

        return view('admin.content.index', compact('items', 'config', 'counts', 'category'));
    }

    // ──────────────────────────────────────────────────────────────────────────

    public function store(StoreContentRequest $request, string $category)
    {
        $this->validateCategory($category);
        $this->authorize('create', [Content::class, $category]);

        $validated = $request->validated();
        $validated['is_promoted'] = $request->boolean('is_promoted');
        $validated['body'] = HtmlSanitizer::forStorage($validated['body'] ?? null);

        // Slug
        $rawSlug = ! empty($validated['slug']) ? $validated['slug'] : $validated['title'];
        $validated['slug'] = $this->slugService->makeUnique(Str::slug($rawSlug));

        // Image / document upload or URL
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $ext = strtolower($file->getClientOriginalExtension());
            $isDoc = in_array($ext, ['pdf', 'doc', 'docx', 'xls', 'xlsx'], true);
            if ($isDoc) {
                // Store documents in private storage (local disk -> storage/app/private/documents/)
                $path = $file->store('documents', 'local');
                $validated['image_url'] = $path;
            } else {
                // Store media in public disk -> storage/app/public/uploads/
                $path = $file->store('uploads', 'public');
                ImageOptimizer::shrink($path);
                $validated['image_url'] = '/storage/'.$path;
            }
        } elseif (! empty($validated['image_url'])) {
            $validated['image_url'] = trim($validated['image_url']);
        } else {
            $validated['image_url'] = null;
        }

        // Category-specific tag encoding
        $validated['tags'] = $this->encodeTags($request, $category, $validated['tags'] ?? null);
        $validated['category'] = $category;

        $content = Content::create($validated);

        AuditLogService::log('CONTENT_CREATE', 'Content', $content->id, [
            'title' => $content->title,
            'category' => $category,
        ]);

        $this->flushGlobalViewCache($category);

        return redirect()->back()->with('success', 'Konten berhasil ditambahkan.');
    }

    // ──────────────────────────────────────────────────────────────────────────

    public function update(UpdateContentRequest $request, string $category, Content $content)
    {
        $this->validateCategory($category);
        abort_if($content->category !== $category, 404, 'Kategori tidak sesuai dengan entri konten.');

        $this->authorize('update', $content);

        $validated = $request->validated();
        $validated['is_promoted'] = $request->boolean('is_promoted');
        $validated['body'] = HtmlSanitizer::forStorage($validated['body'] ?? null);

        // Slug (unique, excluding current record)
        $validated['slug'] = $this->slugService->makeUnique(
            Str::slug($validated['slug']),
            $content->id
        );

        // Image / document upload / removal / retention
        if ($request->hasFile('image')) {
            $this->deleteOldImage($content);
            $file = $request->file('image');
            $ext = strtolower($file->getClientOriginalExtension());
            $isDoc = in_array($ext, ['pdf', 'doc', 'docx', 'xls', 'xlsx'], true);
            if ($isDoc) {
                // Store documents in private storage (local disk -> storage/app/private/documents/)
                $path = $file->store('documents', 'local');
                $validated['image_url'] = $path;
            } else {
                // Store media in public disk -> storage/app/public/uploads/
                $path = $file->store('uploads', 'public');
                ImageOptimizer::shrink($path);
                $validated['image_url'] = '/storage/'.$path;
            }
        } elseif ($request->filled('image_url')) {
            $newUrl = trim($validated['image_url']);
            $oldRaw = (string) $content->getRawOriginal('image_url');
            if ($oldRaw !== '' && $oldRaw !== $newUrl && $content->image_url !== $newUrl) {
                $this->deleteOldImage($content);
            }
            $validated['image_url'] = $newUrl;
        } elseif ($request->input('remove_image') === '1') {
            $this->deleteOldImage($content);
            $validated['image_url'] = null;
        } else {
            // Retain the existing image if no new file was uploaded and not explicitly cleared
            $validated['image_url'] = $content->getRawOriginal('image_url');
        }

        // Category-specific tag encoding
        $validated['tags'] = $this->encodeTags($request, $category, $validated['tags'] ?? null);

        $content->update($validated);

        AuditLogService::log('CONTENT_UPDATE', 'Content', $content->id, [
            'title' => $content->title,
            'category' => $category,
        ]);

        $this->flushGlobalViewCache($category);

        return redirect()->back()->with('success', 'Konten berhasil diperbarui.');
    }

    // ──────────────────────────────────────────────────────────────────────────

    public function destroy(string $category, Content $content)
    {
        $this->validateCategory($category);
        abort_if($content->category !== $category, 404, 'Kategori tidak sesuai dengan entri konten.');

        $this->authorize('delete', $content);

        AuditLogService::log('CONTENT_DELETE', 'Content', $content->id, [
            'title' => $content->title,
            'category' => $category,
        ]);

        $this->deleteOldImage($content);
        $content->delete(); // SoftDeletes — record still in DB, deleted_at is set

        $this->flushGlobalViewCache($category);

        return redirect()->back()->with('success', 'Konten berhasil dihapus.');
    }

    // ──────────────────────────────────────────────────────────────────────────

    public function toggleStatus(string $category, Content $content)
    {
        $this->validateCategory($category);
        abort_if($content->category !== $category, 404, 'Kategori tidak sesuai dengan entri konten.');

        $this->authorize('update', $content);

        $next = $content->status === 'published' ? 'archived' : 'published';
        $content->update(['status' => $next]);

        AuditLogService::log('CONTENT_TOGGLE_STATUS', 'Content', $content->id, [
            'title' => $content->title,
            'status' => $next,
            'category' => $category,
        ]);

        $this->flushGlobalViewCache($category);

        return redirect()->back()->with('success', "Status berhasil diubah ke {$next}.");
    }

    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Handle AJAX image upload from Quill WYSIWYG editor.
     */
    public function uploadEditorImage(Request $request)
    {
        $request->validate([
            'image' => [
                'required',
                'file',
                'image',
                'mimes:jpeg,png,jpg,webp,gif',
                'max:2048',
            ],
        ], [
            'image.required' => 'File gambar wajib dipilih.',
            'image.image' => 'File harus berupa gambar valid.',
            'image.mimes' => 'Format gambar harus JPEG, PNG, WebP, atau GIF.',
            'image.max' => 'Ukuran gambar maksimal 2 MB agar server tetap cepat dan ringan.',
        ]);

        $path = $request->file('image')->store('uploads', 'public');
        ImageOptimizer::shrink($path);
        $url = '/storage/'.$path;

        return response()->json([
            'success' => true,
            'url' => $url,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Private Helpers
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Encode category-specific tags from the request.
     */
    private function encodeTags(Request $request, string $category, ?string $defaultTags): ?string
    {
        if ($category === 'isu-kritis') {
            $icon = $request->input('isu_icon', 'Icon-4.svg');
            $badge = $request->input('isu_badge', 'Isu');

            return $icon.'|'.$badge;
        }

        if ($category === 'regulasi') {
            $regCategory = $request->input('reg_category', 'undang-undang');
            $regIssuer = $request->input('reg_issuer', 'Pemerintah RI');
            $regStatus = $request->input('reg_status', 'berlaku');

            return implode(', ', [$regCategory, $regIssuer, $regStatus]);
        }

        if ($category === 'banner') {
            $btn1Text = trim((string) $request->input('banner_btn1_text', ''));
            $btn1Url = trim((string) $request->input('banner_btn1_url', ''));
            $btn2Text = trim((string) $request->input('banner_btn2_text', ''));
            $btn2Url = trim((string) $request->input('banner_btn2_url', ''));

            if ($btn1Text !== '' || $btn1Url !== '' || $btn2Text !== '' || $btn2Url !== '') {
                return implode('|', [$btn1Text, $btn1Url, $btn2Text, $btn2Url]);
            }

            return $defaultTags;
        }

        return $defaultTags;
    }

    /**
     * Flush cached view-composer variables when critical global categories change.
     */
    private function flushGlobalViewCache(string $category): void
    {
        if ($category === 'kontak') {
            Cache::forget('global_contact');
        } elseif ($category === 'kampanye-darurat') {
            Cache::forget('global_campaign');
        }
    }

    /**
     * Whether another entry still points at the file, as its attachment or inside its
     * body (Quill images). Soft-deleted entries count too, so a restore keeps its file.
     * LIKE wildcards in the path are left unescaped: they can only widen the match,
     * which keeps a file rather than deleting one that is still in use.
     */
    private function isFileUsedElsewhere(Content $content, string $relativePath): bool
    {
        return Content::withTrashed()
            ->whereKeyNot($content->getKey())
            ->where(fn ($q) => $q->where('image_url', 'like', '%'.$relativePath)
                ->orWhere('body', 'like', '%'.$relativePath.'%'))
            ->exists();
    }

    /**
     * Delete a locally uploaded file if it exists and no other entry uses it.
     * Sanitizes filename with basename() to strictly prevent path traversal.
     * Checks both private 'local' disk and legacy 'public' disk.
     */
    private function deleteOldImage(Content $content): void
    {
        $raw = (string) $content->getRawOriginal('image_url');
        if ($raw !== '') {
            $folder = null;
            $filename = null;
            if (str_starts_with($raw, '/storage/uploads/')) {
                $folder = 'uploads/';
                $filename = substr($raw, strlen('/storage/uploads/'));
            } elseif (str_starts_with($raw, 'uploads/')) {
                $folder = 'uploads/';
                $filename = substr($raw, strlen('uploads/'));
            } elseif (str_starts_with($raw, '/storage/documents/')) {
                $folder = 'documents/';
                $filename = substr($raw, strlen('/storage/documents/'));
            } elseif (str_starts_with($raw, 'documents/')) {
                $folder = 'documents/';
                $filename = substr($raw, strlen('documents/'));
            }

            if ($filename !== null && $filename !== '' && $folder !== null) {
                $cleanFilename = basename($filename);
                if ($cleanFilename !== '' && ! in_array($cleanFilename, ['.', '..'], true)
                    && ! $this->isFileUsedElsewhere($content, $folder.$cleanFilename)) {
                    if ($folder === 'documents/') {
                        Storage::disk('local')->delete('documents/'.$cleanFilename);
                        Storage::disk('public')->delete('documents/'.$cleanFilename);
                    } else {
                        Storage::disk('public')->delete('uploads/'.$cleanFilename);
                    }
                }
            }
        }
    }
}
