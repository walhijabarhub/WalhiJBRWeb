<?php

use App\Http\Controllers\Admin\AdminCommentController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminSubscriberController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DocumentDownloadController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicContentController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

// ============================================================
// PUBLIC ROUTES (No Authentication Required)
// ============================================================

Route::get('/', [PublicContentController::class, 'home'])->name('home');

// Google Search Console Verification
Route::get('/google8abf3dd50328ee1e.html', function () {
    return response('google-site-verification: google8abf3dd50328ee1e.html', 200)
        ->header('Content-Type', 'text/html');
});

// Redirect /dashboard → admin dashboard (Breeze compatibility)
Route::get('/dashboard', fn () => redirect()->route('admin.dashboard'))
    ->middleware(['auth'])
    ->name('dashboard');

// Blog & Content
Route::get('/blog', [PublicContentController::class, 'blog'])
    ->middleware('throttle:search')
    ->name('blog');
Route::get('/konten/{slug}', [PublicContentController::class, 'show'])->name('content.show');

// Comments & Newsletter
Route::post('/konten/{content}/komentar', [CommentController::class, 'store'])
    ->middleware('throttle:comment')
    ->name('comments.store');
Route::post('/newsletter/subscribe', [SubscriptionController::class, 'subscribe'])
    ->middleware('throttle:comment')
    ->name('newsletter.subscribe');

// Donation
Route::post('/donasi/pay', [DonationController::class, 'pay'])
    ->middleware('throttle:donation')
    ->name('donasi.pay');
Route::post('/donasi/webhook', [DonationController::class, 'webhook'])
    ->middleware('throttle:60,1')
    ->name('donasi.webhook');

// Public Pages (moved from inline closures to PageController)
Route::get('/tentang-kami', [PageController::class, 'about'])->name('about');
Route::get('/kontak', [PageController::class, 'kontak'])->name('kontak');
Route::get('/pengaduan', [PageController::class, 'pengaduan'])->name('pengaduan');
Route::get('/kebijakan-privasi', [PageController::class, 'privacy'])->name('privacy');
Route::get('/transparansi-dana', [PageController::class, 'transparency'])->name('transparency');
Route::get('/regulasi', [PageController::class, 'regulasi'])->middleware('throttle:search')->name('regulasi');
Route::get('/publikasi/siaran-pers', [PageController::class, 'siaranPers'])->name('siaran-pers');
Route::get('/publikasi/infografis', [PageController::class, 'infografis'])->name('infografis');
Route::get('/publikasi/laporan-tahunan', [PageController::class, 'laporanTahunan'])->name('laporan-tahunan');
Route::get('/publikasi/kertas-posisi', [PageController::class, 'kertasPosisi'])->name('kertas-posisi');
Route::get('/publikasi/catatan-kritis', [PageController::class, 'catatanKritis'])->name('catatan-kritis');
Route::get('/dukung-kami/donasi-publik', [PageController::class, 'donasi'])->name('donasi');

// Authorized Document Download (SEC-008)
Route::get('/dokumen/{content:slug}/unduh', [DocumentDownloadController::class, 'download'])
    ->middleware('throttle:60,1')
    ->name('documents.download');

// SEO
Route::get('/sitemap.xml', [PageController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [PageController::class, 'robots']);

// ============================================================
// MOCK ROUTES — Local / Testing Environment Only
// ============================================================
// These routes are intentionally excluded from production.
// Never register them unconditionally.
if (app()->environment('local', 'testing')) {
    Route::post('/donasi/mock-payment-status', [DonationController::class, 'mockPaymentStatus'])
        ->name('donasi.mock-payment-status');
}

// ============================================================
// AUTHENTICATED ROUTES (Login Required)
// ============================================================

Route::middleware(['auth', 'verified', 'two-factor'])->group(function () {
    // Profile management (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ──────────────────────────────────────────────────────────────────────
    // ADMIN ROUTES
    // ──────────────────────────────────────────────────────────────────────
    Route::prefix('admin')->name('admin.')->middleware('throttle:admin-actions')->group(function () {
        // Shared routes for both admin and editor
        Route::middleware('role:admin,editor')->group(function () {
            Route::get('/', [AdminController::class, 'index'])->name('dashboard');

            // Comment moderation (Approving and marking spam)
            Route::get('/comments', [AdminCommentController::class, 'index'])->name('comments.index');
            Route::post('/comments/{comment}/approve', [AdminCommentController::class, 'approve'])->name('comments.approve');
            Route::post('/comments/{comment}/spam', [AdminCommentController::class, 'spam'])->name('comments.spam');

            // Newsletter subscribers listing
            Route::get('/subscribers', [AdminSubscriberController::class, 'index'])->name('subscribers.index');

            // WYSIWYG Editor image upload (Quill)
            Route::post('/upload-image', [ContentController::class, 'uploadEditorImage'])->name('upload-image');

            $categoryPattern = implode('|', array_map(fn (\App\Enums\ContentCategory $c) => $c->value, \App\Enums\ContentCategory::cases()));

            // "Tentang" sub-prefix (sejarah, visi-misi, dewan-nasional, etc.)
            Route::prefix('tentang')->where(['category' => $categoryPattern])->group(function () {
                Route::get('/{category}', [ContentController::class, 'index'])->name('content.tentang.index');
                Route::post('/{category}', [ContentController::class, 'store'])->name('content.tentang.store');
                Route::put('/{category}/{content}', [ContentController::class, 'update'])->name('content.tentang.update');
                Route::patch('/{category}/{content}/toggle-status', [ContentController::class, 'toggleStatus'])->name('content.tentang.toggle-status');
            });

            // Root categories
            Route::where(['category' => $categoryPattern])->group(function () {
                Route::get('/{category}', [ContentController::class, 'index'])->name('content.index');
                Route::post('/{category}', [ContentController::class, 'store'])->name('content.store');
                Route::put('/{category}/{content}', [ContentController::class, 'update'])->name('content.update');
                Route::patch('/{category}/{content}/toggle-status', [ContentController::class, 'toggleStatus'])->name('content.toggle-status');
            });
        });

        // Destructive / Export actions restricted to admin role only
        Route::middleware('role:admin')->group(function () {
            $categoryPattern = implode('|', array_map(fn (\App\Enums\ContentCategory $c) => $c->value, \App\Enums\ContentCategory::cases()));

            Route::delete('/comments/{comment}', [AdminCommentController::class, 'destroy'])->name('comments.destroy');
            Route::get('/subscribers/export', [AdminSubscriberController::class, 'export'])->name('subscribers.export');
            Route::delete('/subscribers/{subscriber}', [AdminSubscriberController::class, 'destroy'])->name('subscribers.destroy');
            Route::delete('/tentang/{category}/{content}', [ContentController::class, 'destroy'])->where(['category' => $categoryPattern])->name('content.tentang.destroy');
            Route::delete('/{category}/{content}', [ContentController::class, 'destroy'])->where(['category' => $categoryPattern])->name('content.destroy');
        });
    });
});

require __DIR__.'/auth.php';
