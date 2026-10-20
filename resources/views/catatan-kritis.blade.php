<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @include('partials.seo-meta', ['title' => 'Catatan Kritis - WALHI Jawa Barat'])

        @include('partials.head-assets', ['lucide' => true])

        <style>
            .hover-action-dark-btn:hover {
                background: #256D4A !important;
                border-color: #256D4A !important;
            }
            .hover-action-light-btn:hover {
                background: #e9e5d9 !important;
            }
            .press-release-card {
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }
            .press-release-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 12px 32px rgba(29, 29, 29, 0.12);
            }

            @media (max-width: 640px) {
                .card-header-bar {
                    flex-direction: column !important;
                    align-items: flex-start !important;
                    gap: 12px !important;
                }
                .card-actions-bar {
                    flex-direction: column !important;
                    align-items: stretch !important;
                    gap: 12px !important;
                }
                .card-actions-bar > * {
                    width: 100% !important;
                    text-align: center !important;
                    justify-content: center !important;
                }
            }
        </style>
    </head>
    <body style="width: 100%; background: #F4F1EA; margin: 0; overflow-x: clip; color: #1D1D1D; font-family: Montserrat, sans-serif;">
        <div style="position: relative; width: 100%; overflow-x: clip; background: #F4F1EA;">
            @include('partials.site-header')

            <main style="display: flex; flex-direction: column; align-items: stretch;">

                <!-- Hero Section -->
                <section style="background: #1D1D1D; border-bottom: 4px #256D4A solid; color: #F4F1EA;" class="py-12 md:py-16">
                    <div class="w-full max-w-5xl mx-auto px-4 sm:px-8">
                        <div style="display: flex; flex-direction: column; gap: 24px; max-width: 860px; width: 100%;">
                            <!-- Breadcrumbs -->
                            <div style="display: flex; align-items: center; gap: 8px; font-size: 12px; font-family: Montserrat, sans-serif; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px;">
                                <a href="{{ route('home') }}" style="color: #F4F1EA; text-decoration: none; opacity: 0.8;">Beranda</a>
                                <span style="color: #256D4A; font-weight: 400; font-size: 16px;">/</span>
                                <span style="color: #F4F1EA; opacity: 0.8;">Publikasi</span>
                                <span style="color: #256D4A; font-weight: 400; font-size: 16px;">/</span>
                                <span style="color: #5C8D59;">Catatan Kritis</span>
                            </div>

                            <h1 style="margin: 0; color: #F4F1EA; font-size: clamp(48px, 6vw, 76px); font-family: Aspekta, sans-serif; font-weight: 800; line-height: 1; letter-spacing: 1.6px; text-transform: uppercase;">
                                CATATAN KRITIS
                            </h1>
                            <div style="width: 128px; height: 8px; background: #D95C3F;"></div>
                            <p style="margin: 0; color: #5C8D59; font-size: 20px; line-height: 32px; font-family: Montserrat, sans-serif;">
                                Analisis Mendalam dan Catatan Kritis Kebijakan Tata Ruang serta Lingkungan Hidup
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Grid Content Section -->
                <section style="background: #F4F1EA; color: #1D1D1D; border-bottom: 4px #1D1D1D solid;" class="py-16 md:py-20">
                    <div class="w-full max-w-6xl mx-auto px-4 sm:px-8">

                        <!-- Grid layout -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8 w-full">
                            @forelse($items as $item)
                                @php
                                    $months = [
                                        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
                                        '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
                                        '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
                                    ];
                                    $dateObj = \Carbon\Carbon::parse($item->publish_date ?? $item->created_at);
                                    $formattedDate = $dateObj->format('d') . ' ' . $months[$dateObj->format('m')] . ' ' . $dateObj->format('Y');

                                    $isPdf = $item->image_url && (str_ends_with(strtolower($item->image_url), '.pdf') || str_ends_with(strtolower($item->image_url), '.doc') || str_ends_with(strtolower($item->image_url), '.docx'));
                                    $hasCoverImage = $item->image_url && !$isPdf;
                                    $coverImage = $hasCoverImage ? $item->image_url : asset('assets/images/blog/news-' . (($loop->index % 4) + 1) . '-3.jpg');

                                    $cleanBody = strip_tags($item->body);
                                    $wordsArray = preg_split('/\s+/', trim($cleanBody));
                                    $hasMore = count($wordsArray) > 14;
                                    $limitedBody = $hasMore ? implode(' ', array_slice($wordsArray, 0, 14)) . '...' : $cleanBody;
                                @endphp

                                <article style="background: white; border: 4px solid #1D1D1D; outline: 4px #1D1D1D solid; outline-offset: -4px; overflow: hidden; display: flex; flex-direction: column; height: 100%;" class="infographic-card">
                                    <!-- Image cover container -->
                                    <div style="width: 100%; height: 240px; overflow: hidden; border-bottom: 4px solid #1D1D1D; background: #1D1D1D; position: relative;">
                                        <img style="width: 100%; height: 100%; object-fit: cover;" src="{{ $coverImage }}" alt="{{ $item->title }}" loading="{{ $loop->index < 3 ? 'eager' : 'lazy' }}" decoding="async" />
                                        <div style="position: absolute; left: 16px; top: 16px; background: #D95C3F; color: #F4F1EA; padding: 4px 12px; font-family: Montserrat, sans-serif; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; border: 1px solid #1D1D1D;">
                                            Catatan Kritis
                                        </div>
                                    </div>

                                    <!-- Card details -->
                                    <div style="padding: 24px; display: flex; flex-direction: column; justify-content: space-between; flex: 1; gap: 16px;">
                                        <div style="display: flex; flex-direction: column; gap: 10px;">
                                            <div style="display: flex; align-items: center; gap: 6px; color: #5C8D59; font-family: Montserrat, sans-serif; font-size: 12px; font-weight: 600;">
                                                <i data-lucide="calendar" style="width: 14px; height: 14px;"></i>
                                                <span>{{ $formattedDate }}</span>
                                            </div>

                                            <h2 style="margin: 0; color: #1D1D1D; font-size: 20px; font-family: Aspekta, sans-serif; font-weight: 800; text-transform: uppercase; line-height: 1.3; letter-spacing: 0.5px;">
                                                <a href="{{ route('content.show', $item->slug) }}" style="color: #1D1D1D; text-decoration: none; transition: color 0.2s;" data-hover-style="color:#256D4A" data-rest-style="color:#1D1D1D">
                                                    {{ $item->title }}
                                                </a>
                                            </h2>

                                            <p style="margin: 0; color: #444; font-size: 14px; font-family: Montserrat, sans-serif; line-height: 1.6;">
                                                {{ $limitedBody }}
                                            </p>
                                        </div>

                                        <div style="display: flex; flex-direction: column; gap: 8px;">
                                            <a href="{{ route('content.show', $item->slug) }}"
                                               style="width: 100%; height: 48px; background: #256D4A; color: #F4F1EA; border: none; font-family: Aspekta, sans-serif; font-weight: 700; font-size: 14px; letter-spacing: 0.35px; text-transform: uppercase; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; box-sizing: border-box; transition: background 0.2s;"
                                               class="hover-download-btn">
                                                <i data-lucide="book-open" style="width: 18px; height: 18px;"></i>
                                                Baca Catatan Kritis
                                            </a>

                                            @if($isPdf)
                                                @if($item->has_download)
                                                <a href="{{ $item->download_url ?: $item->image_url }}" target="_blank"
                                                   style="width: 100%; height: 40px; background: white; color: #1D1D1D; border: 2px solid #1D1D1D; font-family: Aspekta, sans-serif; font-weight: 700; font-size: 12px; letter-spacing: 0.35px; text-transform: uppercase; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 6px; text-decoration: none; box-sizing: border-box; transition: background 0.2s;"
                                                   class="hover-action-light-btn">
                                                    <i data-lucide="download" style="width: 14px; height: 14px;"></i>
                                                    Unduh Lampiran PDF
                                                </a>
                                                @else
                                                <button disabled
                                                   style="width: 100%; height: 40px; background: white; color: #aaa; border: 2px solid #ddd; font-family: Aspekta, sans-serif; font-weight: 700; font-size: 12px; letter-spacing: 0.35px; text-transform: uppercase; cursor: not-allowed; display: inline-flex; align-items: center; justify-content: center; gap: 6px; box-sizing: border-box; opacity: 0.6;">
                                                    <i data-lucide="download" style="width: 14px; height: 14px; color: #aaa;"></i>
                                                    Dokumen belum tersedia
                                                </button>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </article>
                            @empty
                                <div style="grid-column: 1 / -1; background: white; border: 4px solid #1D1D1D; padding: 48px; text-align: center; font-size: 18px; font-family: Montserrat, sans-serif; color: #888;">
                                    <i data-lucide="alert-circle" style="width: 48px; height: 48px; margin: 0 auto 16px; color: #8B6B4A; display: block;"></i>
                                    Belum ada dokumen catatan kritis yang diterbitkan.
                                </div>
                            @endforelse
                        </div>

                        <!-- Neobrutalist Pagination -->
                        @if($items->hasPages())
                            <div style="display: flex; justify-content: center; align-items: center; gap: 12px; margin-top: 48px;">
                                <a href="{{ $items->previousPageUrl() }}" style="display: inline-flex; align-items: center; justify-content: center; width: 48px; height: 48px; background: white; border: 2px solid #1D1D1D; color: #1D1D1D; font-weight: 700; text-decoration: none; cursor: pointer; {{ $items->onFirstPage() ? 'opacity: 0.5; pointer-events: none;' : '' }}">
                                    ‹
                                </a>
                                <span style="font-weight: 700; font-size: 16px; color: #1D1D1D; font-family: Montserrat, sans-serif;">
                                    Halaman {{ $items->currentPage() }} dari {{ $items->lastPage() }}
                                </span>
                                <a href="{{ $items->nextPageUrl() }}" style="display: inline-flex; align-items: center; justify-content: center; width: 48px; height: 48px; background: white; border: 2px solid #1D1D1D; color: #1D1D1D; font-weight: 700; text-decoration: none; cursor: pointer; {{ !$items->hasMorePages() ? 'opacity: 0.5; pointer-events: none;' : '' }}">
                                    ›
                                </a>
                            </div>
                        @endif

                    </div>
                </section>
            </main>

            @include('partials.site-footer')
        </div>

        <script nonce="{{ Vite::cspNonce() }}">
            document.addEventListener('DOMContentLoaded', () => {
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            });
        </script>
    </body>
</html>
