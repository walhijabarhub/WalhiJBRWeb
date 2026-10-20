<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @include('partials.seo-meta', ['title' => 'Regulasi Hukum & Kebijakan - WALHI Jawa Barat'])

        @include('partials.head-assets', ['lucide' => true])

        <style>
            .hover-stat-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 8px 24px rgba(29, 29, 29, 0.15);
            }
            .hover-stat-card {
                transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease, background 0.2s ease !important;
            }
            .hover-search-btn:hover {
                background: #1D1D1D !important;
            }
            .hover-clear-btn:hover {
                background: #e9e5d9 !important;
            }
            .hover-category-pill:hover {
                background: #256D4A !important;
                color: #F4F1EA !important;
                border-color: #256D4A !important;
            }
            .regulasi-card {
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }
            .regulasi-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 12px 28px rgba(29, 29, 29, 0.12);
            }
            
            /* Responsive Layout Media Queries */
            @media (max-width: 900px) {
                .card-two-column-layout {
                    grid-template-columns: 1fr !important;
                }
                .card-sidebar-section {
                    border-left: none !important;
                    border-top: 2px solid #1D1D1D !important;
                    padding: 24px !important;
                    flex-direction: row !important;
                    justify-content: space-between !important;
                    align-items: center !important;
                }
            }
            
            /* Responsive layout for cards list */
            @media (max-width: 768px) {
                .regulation-card {
                    flex-direction: column !important;
                }
                .card-sidebar {
                    width: 100% !important;
                    border-right: none !important;
                    border-bottom: 4px solid #1D1D1D !important;
                    flex-direction: row !important;
                    justify-content: space-between !important;
                    align-items: center !important;
                    padding: 16px 24px !important;
                }
                .card-sidebar-section {
                    flex-direction: row !important;
                    align-items: center !important;
                    gap: 12px !important;
                }
                .card-sidebar-divider {
                    padding-top: 0 !important;
                    border-top: none !important;
                    border-left: 1px solid rgba(244, 241, 234, 0.3) !important;
                    padding-left: 16px !important;
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
                                <span style="color: #5C8D59;">Regulasi</span>
                            </div>
                            
                            <h1 style="margin: 0; color: #F4F1EA; font-size: clamp(48px, 6vw, 76px); font-family: Aspekta, sans-serif; font-weight: 800; line-height: 1; letter-spacing: 1.6px; text-transform: uppercase;">
                                REGULASI
                            </h1>
                            <div style="width: 128px; height: 8px; background: #D95C3F;"></div>
                            <p style="margin: 0; color: #5C8D59; font-size: 20px; line-height: 32px; font-family: Montserrat, sans-serif;">
                                Database Peraturan Perundangan Lingkungan Hidup
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Main Body Section -->
                <section style="background: #F4F1EA; color: #1D1D1D; border-bottom: 4px #1D1D1D solid;" class="py-16 md:py-20">
                    <div class="w-full max-w-5xl mx-auto px-4 sm:px-8 flex flex-col gap-10">
                        
                        <!-- Stats Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
                            <!-- Undang-Undang -->
                            @php
                                $isActiveUU = $categoryFilter === 'undang-undang';
                            @endphp
                            <a href="{{ route('regulasi', array_merge(request()->except(['kategori', 'page']), $isActiveUU ? [] : ['kategori' => 'undang-undang'])) }}" 
                               style="text-decoration: none; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; padding: 24px; border: 4px solid {{ $isActiveUU ? '#256D4A' : '#1D1D1D' }}; background: {{ $isActiveUU ? '#1D1D1D' : 'white' }};"
                               class="hover-stat-card">
                                <span style="font-family: Aspekta, sans-serif; font-weight: 800; font-size: 40px; line-height: 1; color: #256D4A;">
                                    {{ $countUU }}
                                </span>
                                <span style="font-family: Aspekta, sans-serif; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 0.35px; color: {{ $isActiveUU ? '#F4F1EA' : '#1D1D1D' }}; text-align: center;">
                                    Undang-Undang
                                </span>
                            </a>
                            
                            <!-- Peraturan Pemerintah -->
                            @php
                                $isActivePP = $categoryFilter === 'peraturan-pemerintah';
                            @endphp
                            <a href="{{ route('regulasi', array_merge(request()->except(['kategori', 'page']), $isActivePP ? [] : ['kategori' => 'peraturan-pemerintah'])) }}" 
                               style="text-decoration: none; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; padding: 24px; border: 4px solid {{ $isActivePP ? '#256D4A' : '#1D1D1D' }}; background: {{ $isActivePP ? '#1D1D1D' : 'white' }};"
                               class="hover-stat-card">
                                <span style="font-family: Aspekta, sans-serif; font-weight: 800; font-size: 40px; line-height: 1; color: #5C8D59;">
                                    {{ $countPP }}
                                </span>
                                <span style="font-family: Aspekta, sans-serif; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 0.35px; color: {{ $isActivePP ? '#F4F1EA' : '#1D1D1D' }}; text-align: center;">
                                    Peraturan Pemerintah
                                </span>
                            </a>

                            <!-- Peraturan Daerah -->
                            @php
                                $isActivePD = $categoryFilter === 'peraturan-daerah';
                            @endphp
                            <a href="{{ route('regulasi', array_merge(request()->except(['kategori', 'page']), $isActivePD ? [] : ['kategori' => 'peraturan-daerah'])) }}" 
                               style="text-decoration: none; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; padding: 24px; border: 4px solid {{ $isActivePD ? '#256D4A' : '#1D1D1D' }}; background: {{ $isActivePD ? '#1D1D1D' : 'white' }};"
                               class="hover-stat-card">
                                <span style="font-family: Aspekta, sans-serif; font-weight: 800; font-size: 40px; line-height: 1; color: #8B6B4A;">
                                    {{ $countPD }}
                                </span>
                                <span style="font-family: Aspekta, sans-serif; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 0.35px; color: {{ $isActivePD ? '#F4F1EA' : '#1D1D1D' }}; text-align: center;">
                                    Peraturan Daerah
                                </span>
                            </a>

                            <!-- Keputusan Menteri -->
                            @php
                                $isActiveKM = $categoryFilter === 'keputusan-menteri' || $categoryFilter === 'peraturan-menteri';
                            @endphp
                            <a href="{{ route('regulasi', array_merge(request()->except(['kategori', 'page']), $isActiveKM ? [] : ['kategori' => 'keputusan-menteri'])) }}" 
                               style="text-decoration: none; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; padding: 24px; border: 4px solid {{ $isActiveKM ? '#256D4A' : '#1D1D1D' }}; background: {{ $isActiveKM ? '#1D1D1D' : 'white' }};"
                               class="hover-stat-card">
                                <span style="font-family: Aspekta, sans-serif; font-weight: 800; font-size: 40px; line-height: 1; color: #D95C3F;">
                                    {{ $countKM }}
                                </span>
                                <span style="font-family: Aspekta, sans-serif; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 0.35px; color: {{ $isActiveKM ? '#F4F1EA' : '#1D1D1D' }}; text-align: center;">
                                    Keputusan Menteri
                                </span>
                            </a>
                        </div>

                        <!-- Search Bar -->
                        <div style="background: white; border: 4px solid #1D1D1D; padding: 24px 32px;">
                            <form action="{{ route('regulasi') }}" method="GET" style="display: flex; flex-wrap: wrap; gap: 16px; align-items: stretch; width: 100%;">
                                @if($categoryFilter)
                                    <input type="hidden" name="kategori" value="{{ $categoryFilter }}" />
                                @endif
                                <div style="flex: 1 1 500px; position: relative; display: flex; align-items: center; border: 2px solid #1D1D1D; height: 60px;">
                                    <i data-lucide="search" style="position: absolute; left: 24px; color: #1D1D1D; width: 20px; height: 20px;"></i>
                                    <input type="text" 
                                           name="search" 
                                           value="{{ $search }}" 
                                           placeholder="Cari kata kunci undang-undang, judul peraturan, nomor, isu, atau materi..." 
                                           autocomplete="off"
                                           style="width: 100%; height: 100%; border: none; padding-left: 60px; padding-right: 24px; font-size: 16px; font-family: Montserrat, sans-serif; background: transparent; outline: none; box-sizing: border-box; color: #1D1D1D;" />
                                </div>
                                <button type="submit" 
                                        style="width: 180px; height: 60px; background: #1D1D1D; color: #F4F1EA; border: none; font-family: Aspekta, sans-serif; font-weight: 500; font-size: 16px; letter-spacing: 0.8px; text-transform: uppercase; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: background 0.2s;"
                                        class="hover-search-btn">
                                    <i data-lucide="search" style="width: 16px; height: 16px;"></i>
                                    Cari
                                </button>
                                @if($search || $categoryFilter)
                                    <a href="{{ route('regulasi') }}" 
                                       style="height: 60px; background: #F4F1EA; color: #1D1D1D; border: 2px solid #1D1D1D; font-family: Aspekta, sans-serif; font-weight: 700; font-size: 16px; letter-spacing: 0.8px; text-transform: uppercase; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; padding: 0 24px; text-decoration: none; box-sizing: border-box; transition: background 0.2s;"
                                       class="hover-clear-btn">
                                        Reset
                                    </a>
                                @endif
                            </form>
                        </div>

                        <!-- Regulations Cards List -->
                        <div style="display: flex; flex-direction: column; gap: 24px; width: 100%;">
                            @forelse($items as $item)
                                @php
                                    $tags = array_map('trim', explode(',', $item->tags ?? ''));
                                    
                                    // Default values
                                    $cardCategory = 'Undang-Undang';
                                    $categoryColor = '#256D4A';
                                    $issuer = 'Pemerintah RI';
                                    $statusText = 'Berlaku';
                                    
                                    if (count($tags) >= 3) {
                                        $catVal = strtolower($tags[0]);
                                        $issuer = $tags[1];
                                        $statusVal = strtolower($tags[2]);
                                        
                                        if ($catVal === 'undang-undang') {
                                            $cardCategory = 'Undang-Undang';
                                            $categoryColor = '#256D4A';
                                        } elseif ($catVal === 'peraturan pemerintah') {
                                            $cardCategory = 'Peraturan Pemerintah';
                                            $categoryColor = '#5C8D59';
                                        } elseif ($catVal === 'peraturan daerah') {
                                            $cardCategory = 'Peraturan Daerah';
                                            $categoryColor = '#8B6B4A';
                                        } elseif ($catVal === 'peraturan menteri' || $catVal === 'keputusan menteri') {
                                            $cardCategory = 'Peraturan Menteri';
                                            $categoryColor = '#D95C3F';
                                        }
                                        
                                        if ($statusVal === 'tidak berlaku') {
                                            $statusText = 'Tidak Berlaku';
                                        }
                                    } else {
                                        if (in_array('undang-undang', $tags)) {
                                            $cardCategory = 'Undang-Undang';
                                            $categoryColor = '#256D4A';
                                        } elseif (in_array('peraturan pemerintah', $tags)) {
                                            $cardCategory = 'Peraturan Pemerintah';
                                            $categoryColor = '#5C8D59';
                                        } elseif (in_array('peraturan daerah', $tags)) {
                                            $cardCategory = 'Peraturan Daerah';
                                            $categoryColor = '#8B6B4A';
                                        } elseif (in_array('keputusan menteri', $tags) || in_array('peraturan menteri', $tags)) {
                                            $cardCategory = 'Peraturan Menteri';
                                            $categoryColor = '#D95C3F';
                                        }
                                        
                                        foreach ($tags as $t) {
                                            if (stripos($t, 'kementerian') !== false || stripos($t, 'kemen') !== false || stripos($t, 'pemprov') !== false || stripos($t, 'pemkab') !== false || stripos($t, 'pemerintah') !== false) {
                                                $issuer = $t;
                                                break;
                                            }
                                        }
                                        
                                        if (in_array('tidak berlaku', $tags)) {
                                            $statusText = 'Tidak Berlaku';
                                        }
                                    }
                                    
                                    // Year
                                    $year = $item->publish_date ? \Carbon\Carbon::parse($item->publish_date)->format('Y') : '2025';
                                    if ($year === '2025' && preg_match('/\b(19|20)\d{2}\b/', $item->title, $matches)) {
                                        $year = $matches[0];
                                    }
                                @endphp
                                
                                <div style="background: white; border: 4px solid #1D1D1D; display: flex; flex-direction: row; overflow: hidden; min-height: 260px;" class="regulation-card">
                                    <!-- Left Sidebar (Category & Year) -->
                                    <div style="width: 192px; background: {{ $categoryColor }}; padding: 24px; display: flex; flex-direction: column; justify-content: space-between; flex-shrink: 0; box-sizing: border-box; border-right: 4px solid #1D1D1D;" class="card-sidebar">
                                        <div style="display: flex; flex-direction: column; gap: 8px;" class="card-sidebar-section">
                                            <span style="color: #F4F1EA; font-size: 12px; font-family: Montserrat, sans-serif; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; opacity: 0.85;">KATEGORI</span>
                                            <span style="color: #F4F1EA; font-size: 20px; font-family: Aspekta, sans-serif; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; line-height: 1.2;">{{ $cardCategory }}</span>
                                        </div>
                                        <div style="display: flex; flex-direction: column; gap: 8px; padding-top: 16px; border-top: 1px solid rgba(244, 241, 234, 0.3);" class="card-sidebar-section card-sidebar-divider">
                                            <span style="color: #F4F1EA; font-size: 12px; font-family: Montserrat, sans-serif; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; opacity: 0.85;">TAHUN</span>
                                            <span style="color: #F4F1EA; font-size: 28px; font-family: Aspekta, sans-serif; font-weight: 800; letter-spacing: 1px; line-height: 1;">{{ $year }}</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Right Content Area -->
                                    <div style="flex: 1; padding: 24px; display: flex; flex-direction: column; justify-content: space-between; gap: 16px; box-sizing: border-box;">
                                        <div>
                                            <!-- Header (Title & Status Badge) -->
                                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap;">
                                                <h2 style="margin: 0; color: #1D1D1D; font-size: 22px; font-family: Aspekta, sans-serif; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; line-height: 1.3; flex: 1;">
                                                    {{ $item->title }}
                                                </h2>
                                                <span style="background: {{ $statusText === 'Berlaku' ? '#256D4A' : '#888' }}; color: #F4F1EA; padding: 4px 12px; font-size: 11px; font-family: Montserrat, sans-serif; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; display: inline-block;">
                                                    {{ $statusText }}
                                                </span>
                                            </div>
                                            
                                            <!-- Info Line (Issuer, Year) -->
                                            <div style="display: flex; align-items: center; gap: 16px; margin-top: 12px; color: #5C8D59; font-family: Montserrat, sans-serif; font-size: 14px; font-weight: 600;">
                                                <div style="display: flex; align-items: center; gap: 6px;">
                                                    <i data-lucide="scale" style="width: 16px; height: 16px;"></i>
                                                    <span>{{ $issuer }}</span>
                                                </div>
                                                <div style="display: flex; align-items: center; gap: 6px;">
                                                    <i data-lucide="calendar" style="width: 16px; height: 16px;"></i>
                                                    <span>{{ $year }}</span>
                                                </div>
                                            </div>
                                            
                                            <!-- Description / Body -->
                                            @php
                                                $cleanBody = strip_tags($item->body);
                                                $wordsArray = preg_split('/\s+/', trim($cleanBody));
                                                $hasMore = count($wordsArray) > 13;
                                                if ($hasMore) {
                                                    $limitedBody = implode(' ', array_slice($wordsArray, 0, 13)) . '...';
                                                } else {
                                                    $limitedBody = $cleanBody;
                                                }
                                            @endphp
                                            <p style="margin: 16px 0 0; color: #1D1D1D; font-size: 15px; line-height: 1.6; font-family: Montserrat, sans-serif;">
                                                {{ $limitedBody }}
                                                @if($hasMore)
                                                    <a href="{{ route('content.show', $item->slug) }}" style="color: #256D4A; font-weight: 600; text-decoration: underline; margin-left: 4px;">Baca Selengkapnya</a>
                                                @endif
                                            </p>
                                        </div>
                                        
                                        <!-- Actions Buttons -->
                                        <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-top: 8px;">
                                            <a href="{{ route('content.show', $item->slug) }}" 
                                               style="height: 48px; padding: 0 24px; background: #1D1D1D; color: #F4F1EA; border: none; font-family: Aspekta, sans-serif; font-weight: 700; font-size: 12px; letter-spacing: 0.35px; text-transform: uppercase; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; transition: background 0.2s;"
                                               class="hover-action-dark-btn">
                                                <i data-lucide="book-open" style="width: 14px; height: 14px;"></i>
                                                Baca Lengkap
                                            </a>
                                            
                                            @if($item->has_download)
                                                <a href="{{ $item->download_url ?: $item->image_url }}" target="_blank"
                                                   style="height: 48px; padding: 0 24px; background: white; color: #1D1D1D; border: 2px solid #1D1D1D; font-family: Aspekta, sans-serif; font-weight: 700; font-size: 12px; letter-spacing: 0.35px; text-transform: uppercase; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; box-sizing: border-box; transition: background 0.2s;"
                                                   class="hover-action-light-btn">
                                                    <i data-lucide="download" style="width: 14px; height: 14px;"></i>
                                                    Download PDF
                                                </a>
                                            @else
                                                <button disabled
                                                        style="height: 48px; padding: 0 24px; background: white; color: #aaa; border: 2px solid #ddd; font-family: Aspekta, sans-serif; font-weight: 700; font-size: 12px; letter-spacing: 0.35px; text-transform: uppercase; cursor: not-allowed; display: inline-flex; align-items: center; justify-content: center; gap: 8px; box-sizing: border-box; opacity: 0.6;">
                                                    <i data-lucide="download" style="width: 14px; height: 14px; color: #aaa;"></i>
                                                    Dokumen belum tersedia
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div style="background: white; border: 4px solid #1D1D1D; padding: 48px; text-align: center; font-size: 16px; font-family: Montserrat, sans-serif; color: #555; line-height: 1.6;">
                                    <i data-lucide="alert-circle" style="width: 48px; height: 48px; margin: 0 auto 16px; color: #8B6B4A; display: block;"></i>
                                    @if(isset($totalRegulasi) && $totalRegulasi === 0)
                                        <strong style="color: #1D1D1D; font-size: 18px; display: block; margin-bottom: 8px;">Arsip Regulasi Sedang Diperbarui</strong>
                                        Basis data regulasi lingkungan hidup Jawa Barat sedang dalam proses sinkronisasi dan digitalisasi oleh Tim Riset & Advokasi WALHI Jawa Barat.
                                    @else
                                        <strong style="color: #1D1D1D; font-size: 18px; display: block; margin-bottom: 8px;">Tidak Ada Regulasi yang Cocok</strong>
                                        Tidak ditemukan regulasi yang sesuai dengan kata kunci atau filter yang Anda pilih. Coba bersihkan filter kategori atau gunakan kata kunci lain.
                                    @endif
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
            // Initialize Lucide icons safely on page load
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof lucide !== 'undefined' && lucide.createIcons) {
                    lucide.createIcons();
                }
            });
        </script>
    </body>
</html>
