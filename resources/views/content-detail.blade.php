<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @include('partials.seo-meta', [
            'title' => $item->title . ' - WALHI Jawa Barat',
            'description' => Str::limit(strip_tags($item->body), 155),
            'image' => $item->isDocument() ? null : $item->image_url,
            'type' => 'article'
        ])
 
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,600&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="{{ asset('assets/fonts/webfonts/font-face.css') }}">
 
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <!-- Lucide Script for Icons -->
        <script nonce="{{ Vite::cspNonce() }}" src="https://unpkg.com/lucide@0.460.0/dist/umd/lucide.min.js" integrity="sha384-ieG+IKD0d/ZPXyCBTMVAbqsQdns8QGJR/e26WMw7M4fkaI/rHcS/YIoi+ah9WGge" crossorigin="anonymous"></script>
 
        <style>
            .btn-action {
                transition: all 0.2s ease;
            }
            .btn-action:hover {
                transform: translateY(-2px);
                box-shadow: 4px 4px 0px 0px #1D1D1D;
            }
            .share-btn:hover {
                background-color: #256D4A !important;
                color: #FFFFFF !important;
                border-color: #256D4A !important;
            }
            .post-content {
                font-family: Montserrat, sans-serif;
                font-size: 18px;
                line-height: 1.8;
                color: #2D3748;
            }
            .post-content p {
                margin-bottom: 1.5em;
            }
            .post-content h1, .post-content h2, .post-content h3, .post-content h4 {
                color: #1D1D1D;
                font-family: Aspekta, sans-serif;
                font-weight: 700;
                margin-top: 1.75em;
                margin-bottom: 0.75em;
                letter-spacing: 0.5px;
            }
            .post-content h2 { font-size: 28px; }
            .post-content h3 { font-size: 24px; }
            .post-content h4 { font-size: 20px; }
            .post-content ul, .post-content ol {
                margin: 1em 0 1.25em 1.5em;
            }
            .post-content li {
                margin-bottom: 0.5em;
            }
            .post-content blockquote {
                border-left: 4px solid #256D4A;
                margin: 1.5em 0;
                font-style: italic;
                background: #FFFFFF;
                border: 2px solid #1D1D1D;
                border-left: 6px solid #256D4A;
                padding: 16px 20px;
                box-shadow: 2px 2px 0px 0px #1D1D1D;
            }
        </style>
    </head>
    <body style="width: 100%; background: #F4F1EA; margin: 0; overflow-x: clip; color: #1D1D1D; font-family: Montserrat, sans-serif;">
        <div style="position: relative; width: 100%; overflow-x: clip; background: #F4F1EA;">
            @include('partials.site-header')
 
            <main style="display: flex; flex-direction: column; align-items: stretch;">
                
                <!-- Content Section -->
                <section style="background: #F4F1EA;" class="py-12 md:py-16">
                    <!-- Back Button & Breadcrumbs Container -->
                    <div class="w-full max-w-[1440px] mx-auto px-2 sm:px-4 flex flex-col gap-10">
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                            @php
                                $backUrl = route('home');
                                if($item->category === 'blog') $backUrl = route('blog');
                                elseif($item->category === 'regulasi') $backUrl = route('regulasi');
                                elseif($item->category === 'siaran-pers') $backUrl = route('siaran-pers');
                                elseif($item->category === 'laporan-tahunan') $backUrl = route('laporan-tahunan');
                                elseif($item->category === 'infografis') $backUrl = route('infografis');
                                elseif($item->category === 'kertas-posisi') $backUrl = route('kertas-posisi');
                                elseif($item->category === 'catatan-kritis') $backUrl = route('catatan-kritis');
                            @endphp
                            <a href="{{ $backUrl }}" style="display: inline-flex; align-items: center; gap: 8px; height: 44px; padding: 0 20px; background: white; color: #1D1D1D; border: 2px solid #1D1D1D; text-decoration: none; font-size: 14px; font-weight: 700; text-transform: uppercase;" class="btn-back">
                                <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i>
                                Kembali
                            </a>
                            
                            <!-- Breadcrumbs -->
                            <div style="display: flex; align-items: center; gap: 8px; font-size: 12px; font-family: Montserrat, sans-serif; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px;">
                                <a href="{{ route('home') }}" style="color: #1D1D1D; text-decoration: none; opacity: 0.8;">Beranda</a>
                                <span style="color: #256D4A; font-weight: 400; font-size: 16px;">/</span>
                                <span style="color: #1D1D1D; opacity: 0.8; text-transform: uppercase;">{{ str_replace('-', ' ', $item->category) }}</span>
                                <span style="color: #256D4A; font-weight: 400; font-size: 16px;">/</span>
                                <span style="color: #5C8D59;">Detail</span>
                            </div>
                        </div>
 
                        <!-- Rendering based on Category -->
                        @if($item->category === 'regulasi')
                            <!-- REGULASI LAYOUT -->
                            @php
                                $tags = array_map('trim', explode(',', $item->tags ?? ''));
                                $cardCategory = 'Undang-Undang';
                                $statusText = 'Berlaku';
                                $issuer = 'Pemerintah RI';
                                
                                if (count($tags) >= 3) {
                                    $catVal = strtolower($tags[0]);
                                    $issuer = $tags[1];
                                    $statusVal = strtolower($tags[2]);
                                    
                                    if ($catVal === 'undang-undang') $cardCategory = 'Undang-Undang';
                                    elseif ($catVal === 'peraturan pemerintah') $cardCategory = 'Peraturan Pemerintah';
                                    elseif ($catVal === 'peraturan daerah') $cardCategory = 'Peraturan Daerah';
                                    elseif ($catVal === 'peraturan menteri' || $catVal === 'keputusan menteri') $cardCategory = 'Peraturan Menteri';
                                    
                                    if ($statusVal === 'tidak berlaku') $statusText = 'Tidak Berlaku';
                                } else {
                                    if (in_array('undang-undang', $tags)) $cardCategory = 'Undang-Undang';
                                    elseif (in_array('peraturan pemerintah', $tags)) $cardCategory = 'Peraturan Pemerintah';
                                    elseif (in_array('peraturan daerah', $tags)) $cardCategory = 'Peraturan Daerah';
                                    elseif (in_array('keputusan menteri', $tags) || in_array('peraturan menteri', $tags)) $cardCategory = 'Peraturan Menteri';
                                    
                                    $statusText = in_array('tidak berlaku', $tags) ? 'Tidak Berlaku' : 'Berlaku';
                                    foreach ($tags as $t) {
                                        if (stripos($t, 'kementerian') !== false || stripos($t, 'kemen') !== false || stripos($t, 'pemprov') !== false || stripos($t, 'pemkab') !== false || stripos($t, 'pemerintah') !== false) {
                                            $issuer = $t;
                                            break;
                                        }
                                    }
                                }
                                $year = $item->publish_date ? \Carbon\Carbon::parse($item->publish_date)->format('Y') : '2025';
                            @endphp
 
                            <div style="background: white; border: 4px solid #1D1D1D; outline: 4px #1D1D1D solid; outline-offset: -4px; padding: 40px; display: flex; flex-direction: column; gap: 32px;" class="w-full max-w-4xl mx-auto">
                                <!-- Title & Metadata -->
                                <div style="display: flex; flex-direction: column; gap: 16px; border-bottom: 2px solid #f0ede8; padding-bottom: 20px;">
                                    <h1 style="margin: 0; color: #1D1D1D; font-size: clamp(26px, 4vw, 44px); font-family: Aspekta, sans-serif; font-weight: 800; line-height: 1.15; letter-spacing: 0.5px; text-transform: uppercase;">
                                        {{ $item->title }}
                                    </h1>
                                    
                                    <div class="flex items-center flex-nowrap gap-1.5 md:gap-2.5 lg:gap-4 overflow-x-auto whitespace-nowrap scrollbar-none text-[9px] md:text-[10.5px] lg:text-[12px]" style="color: #5C8D59; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; -ms-overflow-style: none; scrollbar-width: none;">
                                        <div style="display: flex; align-items: center; gap: 2px;" class="flex-shrink-0">
                                            <i data-lucide="user" class="w-2.5 h-2.5 md:w-3 md:h-3 lg:w-3.5 lg:h-3.5 flex-shrink-0"></i>
                                            <span>{{ $item->author ?: 'WALHI Jawa Barat' }}</span>
                                        </div>
                                        <span class="flex-shrink-0">▪</span>
                                        <div style="display: flex; align-items: center; gap: 2px;" class="flex-shrink-0">
                                            <i data-lucide="calendar" class="w-2.5 h-2.5 md:w-3 md:h-3 lg:w-3.5 lg:h-3.5 flex-shrink-0"></i>
                                            <span>{{ $item->publish_date ? \Carbon\Carbon::parse($item->publish_date)->translatedFormat('d F Y') : $item->created_at->translatedFormat('d F Y') }}</span>
                                        </div>
                                        <span class="flex-shrink-0">▪</span>
                                        <div style="display: flex; align-items: center; gap: 2px;" class="flex-shrink-0">
                                            <i data-lucide="message-square" class="w-2.5 h-2.5 md:w-3 md:h-3 lg:w-3.5 lg:h-3.5 flex-shrink-0"></i>
                                            <span>
                                                @php
                                                    $approvedCommentsCount = $item->comments()->where('status', 'approved')->count();
                                                @endphp
                                                {{ $approvedCommentsCount === 0 ? 'Belum ada komentar' : $approvedCommentsCount . ' Komentar' }}
                                            </span>
                                        </div>
                                        <span class="flex-shrink-0">▪</span>
                                        <div style="display: flex; align-items: center; gap: 2px;" class="flex-shrink-0">
                                            <i data-lucide="clock" class="w-2.5 h-2.5 md:w-3 md:h-3 lg:w-3.5 lg:h-3.5 flex-shrink-0"></i>
                                            <span>{{ $readTime }} menit baca</span>
                                        </div>
                                    </div>
                                </div>

                                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #1D1D1D; padding-bottom: 20px; flex-wrap: wrap; gap: 16px;">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <span style="background: #256D4A; color: white; padding: 4px 12px; font-size: 12px; font-weight: 700; text-transform: uppercase; font-family: Montserrat, sans-serif;">{{ $cardCategory }}</span>
                                        <span style="font-family: Aspekta, sans-serif; font-weight: 800; font-size: 20px; color: #1D1D1D;">Tahun {{ $year }}</span>
                                    </div>
                                    <span style="background: {{ $statusText === 'Berlaku' ? '#256D4A' : '#888' }}; color: white; padding: 4px 12px; font-size: 12px; font-weight: 700; text-transform: uppercase; font-family: Montserrat, sans-serif;">{{ $statusText }}</span>
                                </div>
 
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 24px; background: #F4F1EA; padding: 24px; border-left: 4px solid #256D4A;">
                                    <div>
                                        <div style="font-size: 12px; color: #888; font-weight: 700; text-transform: uppercase;">Instansi Penerbit</div>
                                        <div style="font-size: 16px; font-weight: 600; color: #1D1D1D; margin-top: 4px;">{{ $issuer }}</div>
                                    </div>
                                    <div>
                                        <div style="font-size: 12px; color: #888; font-weight: 700; text-transform: uppercase;">Tanggal Penetapan</div>
                                        <div style="font-size: 16px; font-weight: 600; color: #1D1D1D; margin-top: 4px;">{{ $item->publish_date ? \Carbon\Carbon::parse($item->publish_date)->translatedFormat('d F Y') : '-' }}</div>
                                    </div>
                                </div>
 
                                <div>
                                    <h3 style="font-family: Aspekta, sans-serif; font-weight: 800; font-size: 24px; margin: 0 0 12px; text-transform: uppercase;">Ringkasan / Isi Regulasi</h3>
                                    <div style="font-size: 16px; line-height: 1.8; color: #333; white-space: pre-line;">{{ $item->body }}</div>
                                </div>
 
                                <div style="border-top: 2px solid #1D1D1D; padding-top: 24px;">
                                    @if($item->has_download)
                                        <a href="{{ $item->download_url ?: $item->image_url }}" target="_blank" rel="noopener noreferrer" style="height: 52px; padding: 0 32px; background: #1D1D1D; color: #F4F1EA; border: none; font-weight: 700; font-size: 14px; text-transform: uppercase; display: inline-flex; align-items: center; gap: 8px; text-decoration: none;" class="btn-action">
                                            <i data-lucide="download" style="width: 18px; height: 18px;"></i>
                                            Unduh Dokumen PDF
                                        </a>
                                    @else
                                        <button disabled style="height: 52px; padding: 0 32px; background: #ddd; color: #aaa; border: none; font-weight: 700; font-size: 14px; text-transform: uppercase; cursor: not-allowed; display: inline-flex; align-items: center; gap: 8px;">
                                            <i data-lucide="download" style="width: 18px; height: 18px; color: #aaa;"></i>
                                            Dokumen belum tersedia
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @elseif($item->category === 'laporan-tahunan')
                            <!-- LAPORAN TAHUNAN LAYOUT -->
                            @php
                                $bodyData = json_decode($item->body, true);
                                $isJson = (json_last_error() === JSON_ERROR_NONE && is_array($bodyData));
                                if ($isJson) {
                                    $subtitle = $bodyData['subtitle'] ?? '';
                                    $stats = $bodyData['stats'] ?? [];
                                    $pages = $bodyData['pages'] ?? 'Dokumen Laporan';
                                    $reportDesc = $bodyData['description'] ?? '';
                                } else {
                                    $subtitle = '';
                                    $stats = [];
                                    $pages = 'Dokumen Laporan';
                                    $reportDesc = $item->body;
                                }
                                $year = $item->publish_date ? \Carbon\Carbon::parse($item->publish_date)->format('Y') : '2025';
                                
                                $ext = $item->image_url ? pathinfo($item->image_url, PATHINFO_EXTENSION) : '';
                                if (in_array(strtolower($ext), ['xls', 'xlsx'])) {
                                    $btnText = 'Unduh Berkas Excel';
                                } elseif (in_array(strtolower($ext), ['pdf'])) {
                                    $btnText = 'Unduh Berkas PDF';
                                } else {
                                    $btnText = 'Unduh Berkas';
                                }
                            @endphp

                            <div style="background: white; border: 4px solid #1D1D1D; outline: 4px #1D1D1D solid; outline-offset: -4px; padding: 40px; display: flex; flex-direction: column; gap: 32px;" class="w-full max-w-4xl mx-auto">
                                <!-- Title & Metadata -->
                                <div style="display: flex; flex-direction: column; gap: 16px; border-bottom: 2px solid #f0ede8; padding-bottom: 20px;">
                                    <h1 style="margin: 0; color: #1D1D1D; font-size: clamp(26px, 4vw, 44px); font-family: Aspekta, sans-serif; font-weight: 800; line-height: 1.15; letter-spacing: 0.5px; text-transform: uppercase;">
                                        {{ $item->title }}
                                    </h1>
                                    
                                    <div class="flex items-center flex-nowrap gap-1.5 md:gap-2.5 lg:gap-4 overflow-x-auto whitespace-nowrap scrollbar-none text-[9px] md:text-[10.5px] lg:text-[12px]" style="color: #5C8D59; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; -ms-overflow-style: none; scrollbar-width: none;">
                                        <div style="display: flex; align-items: center; gap: 2px;" class="flex-shrink-0">
                                            <i data-lucide="user" class="w-2.5 h-2.5 md:w-3 md:h-3 lg:w-3.5 lg:h-3.5 flex-shrink-0"></i>
                                            <span>{{ $item->author ?: 'WALHI Jawa Barat' }}</span>
                                        </div>
                                        <span class="flex-shrink-0">▪</span>
                                        <div style="display: flex; align-items: center; gap: 2px;" class="flex-shrink-0">
                                            <i data-lucide="calendar" class="w-2.5 h-2.5 md:w-3 md:h-3 lg:w-3.5 lg:h-3.5 flex-shrink-0"></i>
                                            <span>{{ $item->publish_date ? \Carbon\Carbon::parse($item->publish_date)->translatedFormat('d F Y') : $item->created_at->translatedFormat('d F Y') }}</span>
                                        </div>
                                        <span class="flex-shrink-0">▪</span>
                                        <div style="display: flex; align-items: center; gap: 2px;" class="flex-shrink-0">
                                            <i data-lucide="message-square" class="w-2.5 h-2.5 md:w-3 md:h-3 lg:w-3.5 lg:h-3.5 flex-shrink-0"></i>
                                            <span>
                                                @php
                                                    $approvedCommentsCount = $item->comments()->where('status', 'approved')->count();
                                                @endphp
                                                {{ $approvedCommentsCount === 0 ? 'Belum ada komentar' : $approvedCommentsCount . ' Komentar' }}
                                            </span>
                                        </div>
                                        <span class="flex-shrink-0">▪</span>
                                        <div style="display: flex; align-items: center; gap: 2px;" class="flex-shrink-0">
                                            <i data-lucide="clock" class="w-2.5 h-2.5 md:w-3 md:h-3 lg:w-3.5 lg:h-3.5 flex-shrink-0"></i>
                                            <span>{{ $readTime }} menit baca</span>
                                        </div>
                                    </div>
                                </div>

                                <div style="display: flex; gap: 24px; align-items: center; border-bottom: 2px solid #1D1D1D; padding-bottom: 20px;">
                                    <div style="width: 80px; height: 80px; background: #256D4A; display: flex; flex-direction: column; justify-content: center; align-items: center; color: white; font-family: Aspekta, sans-serif; font-weight: 800; font-size: 24px;">
                                        {{ $year }}
                                    </div>
                                    <div>
                                        <h2 style="font-family: Aspekta, sans-serif; font-weight: 800; font-size: 26px; margin: 0; text-transform: uppercase; letter-spacing: 0.5px;">{{ $item->title }}</h2>
                                        @if($subtitle)
                                            <p style="margin: 4px 0 0; color: #5C8D59; font-weight: 600; font-size: 16px; font-family: Montserrat, sans-serif;">{{ $subtitle }}</p>
                                        @endif
                                    </div>
                                </div>

                                <div>
                                    <h3 style="font-family: Aspekta, sans-serif; font-weight: 800; font-size: 22px; margin: 0 0 12px; text-transform: uppercase;">Deskripsi Laporan</h3>
                                    <div style="font-size: 16px; line-height: 1.8; color: #333; white-space: pre-line; font-family: Montserrat, sans-serif;">{{ $reportDesc }}</div>
                                </div>

                                @if(count($stats) > 0)
                                    <div>
                                        <h3 style="font-family: Aspekta, sans-serif; font-weight: 800; font-size: 22px; margin: 0 0 16px; text-transform: uppercase;">Poin Utama Laporan</h3>
                                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px;">
                                            @foreach($stats as $stat)
                                                <div style="background: #F4F1EA; border-left: 4px solid #256D4A; padding: 16px; display: flex; align-items: flex-start; gap: 12px;">
                                                    <i data-lucide="check-square" style="width: 20px; height: 20px; color: #256D4A; flex-shrink: 0; margin-top: 2px;"></i>
                                                    <span style="font-weight: 600; font-size: 14px; line-height: 1.4;">{{ $stat }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <div style="display: flex; gap: 24px; color: #5C8D59; font-weight: 600; font-size: 14px;">
                                    <div style="display: flex; align-items: center; gap: 6px;">
                                        <i data-lucide="file-text" style="width: 18px; height: 18px;"></i>
                                        <span>{{ $pages }}</span>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 6px;">
                                        <i data-lucide="eye" style="width: 18px; height: 18px;"></i>
                                        <span>{{ $item->views }} Kali Dibaca</span>
                                    </div>
                                </div>

                                <div style="border-top: 2px solid #1D1D1D; padding-top: 24px; display: flex; gap: 12px;">
                                    @if($item->has_download)
                                        <a href="{{ $item->download_url ?: $item->image_url }}" target="_blank" rel="noopener noreferrer" style="height: 52px; padding: 0 32px; background: #1D1D1D; color: #F4F1EA; border: none; font-weight: 700; font-size: 14px; text-transform: uppercase; display: inline-flex; align-items: center; gap: 8px; text-decoration: none;" class="btn-action">
                                            <i data-lucide="download" style="width: 18px; height: 18px;"></i>
                                            {{ $btnText }}
                                        </a>
                                    @else
                                        <button disabled style="height: 52px; padding: 0 32px; background: #ddd; color: #aaa; border: none; font-weight: 700; font-size: 14px; text-transform: uppercase; cursor: not-allowed; display: inline-flex; align-items: center; gap: 8px;">
                                            <i data-lucide="download" style="width: 18px; height: 18px; color: #aaa;"></i>
                                            Dokumen belum tersedia
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @else
                            <!-- DEFAULT BLOG & SIARAN PERS LAYOUT WITH SIDEBAR -->
                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                                
                                <!-- Left Column: Article & Comments (75% width) -->
                                <div class="lg:col-span-9 flex flex-col gap-10">
                                    <article style="background: white; border: 4px solid #1D1D1D; outline: 4px #1D1D1D solid; outline-offset: -4px;" class="p-5 md:p-8 lg:p-10 flex flex-col gap-5 md:gap-6">
                                        <!-- Title & Metadata -->
                                        <div style="display: flex; flex-direction: column; gap: 16px; border-bottom: 2px solid #f0ede8; padding-bottom: 20px; margin-bottom: 10px;">
                                            <h1 style="margin: 0; color: #1D1D1D; font-size: clamp(26px, 4vw, 44px); font-family: Aspekta, sans-serif; font-weight: 800; line-height: 1.15; letter-spacing: 0.5px; text-transform: uppercase;">
                                                {{ $item->title }}
                                            </h1>
                                            
                                            <div class="flex items-center flex-nowrap gap-1.5 md:gap-2.5 lg:gap-4 overflow-x-auto whitespace-nowrap scrollbar-none text-[9px] md:text-[10.5px] lg:text-[12px]" style="color: #5C8D59; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; -ms-overflow-style: none; scrollbar-width: none;">
                                                <div style="display: flex; align-items: center; gap: 2px;" class="flex-shrink-0">
                                                    <i data-lucide="user" class="w-2.5 h-2.5 md:w-3 md:h-3 lg:w-3.5 lg:h-3.5 flex-shrink-0"></i>
                                                    <span>{{ $item->author ?: 'WALHI Jawa Barat' }}</span>
                                                </div>
                                                <span class="flex-shrink-0">▪</span>
                                                <div style="display: flex; align-items: center; gap: 2px;" class="flex-shrink-0">
                                                    <i data-lucide="calendar" class="w-2.5 h-2.5 md:w-3 md:h-3 lg:w-3.5 lg:h-3.5 flex-shrink-0"></i>
                                                    <span>{{ $item->publish_date ? \Carbon\Carbon::parse($item->publish_date)->translatedFormat('d F Y') : $item->created_at->translatedFormat('d F Y') }}</span>
                                                </div>
                                                <span class="flex-shrink-0">▪</span>
                                                <div style="display: flex; align-items: center; gap: 2px;" class="flex-shrink-0">
                                                    <i data-lucide="message-square" class="w-2.5 h-2.5 md:w-3 md:h-3 lg:w-3.5 lg:h-3.5 flex-shrink-0"></i>
                                                    <span>
                                                        @php
                                                            $approvedCommentsCount = (int) ($item->approved_comments_count ?? 0);
                                                        @endphp
                                                        {{ $approvedCommentsCount === 0 ? 'Belum ada komentar' : $approvedCommentsCount . ' Komentar' }}
                                                    </span>
                                                </div>
                                                <span class="flex-shrink-0">▪</span>
                                                <div style="display: flex; align-items: center; gap: 2px;" class="flex-shrink-0">
                                                    <i data-lucide="clock" class="w-2.5 h-2.5 md:w-3 md:h-3 lg:w-3.5 lg:h-3.5 flex-shrink-0"></i>
                                                    <span>{{ $readTime }} menit baca</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Cover Image (a PDF/Office attachment is not an image) -->
                                        @if($item->image_url && ! $item->isDocument())
                                            <div style="width: 100%; border: 4px solid #1D1D1D; background: #1D1D1D; overflow: hidden; max-height: 480px;">
                                                <img style="width: 100%; height: auto; object-fit: cover; display: block;" src="{{ $item->image_url }}" alt="{{ $item->title }}" />
                                            </div>
                                        @endif

                                        <!-- Article Body -->
                                        {{-- Security: body is sanitized server-side using HTMLPurifier.
                                             Raw {!! !!} without sanitization is an XSS vulnerability. --}}
                                        <div class="post-content" style="font-size: 17px; line-height: 1.85; color: #1D1D1D; font-family: Montserrat, sans-serif; max-width: 820px; width: 100%;">
                                            {!! $item->sanitized_body !!}
                                        </div>

                                        <!-- Document / Infographic Download & Action Card (QA-002, QA-018) -->
                                        @if($item->isDocument() || in_array($item->category, ['infografis', 'kertas-posisi', 'catatan-kritis']))
                                            @php
                                                $ext = $item->image_url ? strtoupper(pathinfo($item->image_url, PATHINFO_EXTENSION)) : 'PDF';
                                                $docLabel = $item->category === 'infografis' ? 'Infografis Publikasi' : ($item->category === 'kertas-posisi' ? 'Kertas Posisi Kebijakan' : 'Dokumen Advokasi');
                                            @endphp
                                            <div style="background: #F4F1EA; border: 3px solid #1D1D1D; box-shadow: 4px 4px 0px 0px #256D4A; padding: 24px; display: flex; flex-direction: column; gap: 16px; margin: 16px 0;">
                                                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; border-bottom: 2px solid #1D1D1D; padding-bottom: 12px;">
                                                    <div style="display: flex; align-items: center; gap: 8px;">
                                                        <span style="background: #256D4A; color: white; padding: 3px 8px; font-size: 11px; font-weight: 700; text-transform: uppercase;">{{ $ext ?: 'PDF' }}</span>
                                                        <span style="font-size: 13px; font-weight: 700; text-transform: uppercase; color: #1D1D1D;">{{ $docLabel }}</span>
                                                    </div>
                                                    <span style="font-size: 12px; font-weight: 600; color: #5C8D59;">WALHI Jawa Barat</span>
                                                </div>

                                                <p style="margin: 0; font-size: 14px; line-height: 1.6; color: #333;">
                                                    Dokumen publik ini dapat diakses dan diunduh secara bebas untuk kepentingan riset, edukasi publik, dan advokasi keadilan ekologis.
                                                </p>

                                                <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center; padding-top: 6px;">
                                                    @if($item->has_download)
                                                        <a href="{{ $item->download_url }}" target="_blank" rel="noopener noreferrer" style="height: 46px; padding: 0 24px; background: #1D1D1D; color: #F4F1EA; border: none; font-weight: 700; font-size: 13px; text-transform: uppercase; display: inline-flex; align-items: center; gap: 8px; text-decoration: none;" class="btn-action">
                                                            <i data-lucide="download" style="width: 16px; height: 16px;"></i>
                                                            Unduh Dokumen Lengkap ({{ $ext ?: 'PDF' }})
                                                        </a>
                                                    @else
                                                        <button disabled style="height: 46px; padding: 0 24px; background: #ddd; color: #888; border: none; font-weight: 700; font-size: 13px; text-transform: uppercase; cursor: not-allowed; display: inline-flex; align-items: center; gap: 8px;">
                                                            <i data-lucide="file-text" style="width: 16px; height: 16px;"></i>
                                                            Dokumen belum tersedia
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif

                                        <!-- Share Buttons -->
                                        <div style="border-top: 2px solid #1D1D1D; padding-top: 24px; display: flex; flex-direction: column; gap: 12px; margin-top: 12px;">
                                            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #888;">Bagikan Tulisan Ini</div>
                                            <div style="display: flex; flex-wrap: wrap; gap: 8px; align-items: center;">
                                                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}" target="_blank" style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; height: 38px; padding: 0 16px; background: #3b5998; color: white; border: 2px solid #1D1D1D; text-decoration: none; font-size: 11px; font-weight: 700; text-transform: uppercase;" class="btn-action shadow-[2px_2px_0px_0px_#1D1D1D]">
                                                    <i data-lucide="facebook" style="width: 14px; height: 14px;"></i>
                                                    Facebook
                                                </a>
                                                <a href="https://twitter.com/intent/tweet?url={{ urlencode(url()->current()) }}&text={{ urlencode($item->title) }}" target="_blank" style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; height: 38px; padding: 0 16px; background: #000000; color: white; border: 2px solid #1D1D1D; text-decoration: none; font-size: 11px; font-weight: 700; text-transform: uppercase;" class="btn-action shadow-[2px_2px_0px_0px_#1D1D1D]">
                                                    <i data-lucide="twitter" style="width: 14px; height: 14px;"></i>
                                                    Twitter
                                                </a>
                                                <a href="https://api.whatsapp.com/send?text={{ urlencode($item->title . ' ' . url()->current()) }}" target="_blank" style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; height: 38px; padding: 0 16px; background: #25D366; color: white; border: 2px solid #1D1D1D; text-decoration: none; font-size: 11px; font-weight: 700; text-transform: uppercase;" class="btn-action shadow-[2px_2px_0px_0px_#1D1D1D]">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2zm.01 1.67c2.2 0 4.26.86 5.82 2.42a8.225 8.225 0 0 1 2.41 5.83c0 4.54-3.7 8.24-8.24 8.24-1.48 0-2.93-.4-4.2-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.196 8.196 0 0 1-1.26-4.36c0-4.54 3.7-8.24 8.24-8.24zm4.52 11.51c-.25-.13-1.47-.72-1.7-.81-.23-.08-.39-.13-.56.13-.17.25-.64.81-.79.97-.14.17-.29.19-.54.06-.25-.13-1.06-.39-2.03-1.25-.75-.67-1.26-1.5-1.41-1.75-.14-.25-.02-.39.11-.51.11-.11.25-.29.38-.44.12-.14.17-.25.25-.42.08-.17.04-.31-.02-.44-.06-.13-.56-1.35-.77-1.85-.2-.49-.41-.42-.56-.43h-.48c-.17 0-.44.06-.67.31-.23.25-.88.86-.88 2.09 0 1.23.9 2.42 1.02 2.59.13.17 1.77 2.7 4.29 3.78.6.26 1.07.41 1.43.53.6.19 1.15.16 1.58.1.48-.07 1.47-.6 1.68-1.18.21-.58.21-1.08.15-1.18-.07-.11-.23-.17-.48-.29z"/></svg>
                                                    WhatsApp
                                                </a>
                                                <a href="https://telegram.me/share/url?url={{ urlencode(url()->current()) }}&text={{ urlencode($item->title) }}" target="_blank" style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; height: 38px; padding: 0 16px; background: #0088cc; color: white; border: 2px solid #1D1D1D; text-decoration: none; font-size: 11px; font-weight: 700; text-transform: uppercase;" class="btn-action shadow-[2px_2px_0px_0px_#1D1D1D]">
                                                    <i data-lucide="send" style="width: 14px; height: 14px;"></i>
                                                    Telegram
                                                </a>
                                                <button data-on-click="copyToClipboard" data-args="{{ json_encode([url()->current()]) }}" style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; height: 38px; padding: 0 16px; background: #ffffff; color: #1D1D1D; border: 2px solid #1D1D1D; cursor: pointer; font-size: 11px; font-weight: 700; text-transform: uppercase; font-family: Aspekta, sans-serif;" class="btn-action shadow-[2px_2px_0px_0px_#1D1D1D]">
                                                    <i data-lucide="link" style="width: 14px; height: 14px;"></i>
                                                    Salin Link
                                                </button>
                                                <span id="copy-success-msg" style="display: none; font-size: 11px; color: #256D4A; font-weight: 700; text-transform: uppercase; margin-left: 8px;">Disalin!</span>
                                            </div>
                                        </div>

                                        <!-- Tags / Chips -->
                                        @if($item->tags)
                                            <div style="display: flex; flex-wrap: wrap; gap: 8px; border-top: 2px solid #1D1D1D; padding-top: 24px;">
                                                @foreach(array_map('trim', explode(',', $item->tags)) as $tag)
                                                    <span style="padding: 6px 12px; background: #F4F1EA; border: 2px solid #1D1D1D; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #1D1D1D;">
                                                        #{{ $tag }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </article>

                                    <!-- Comments Section -->
                                    @if($item->category === 'blog' || $item->category === 'siaran-pers' || $item->category === 'infografis')
                                        <div style="border-top: 4px solid #1D1D1D; margin-top: 48px; padding-top: 48px; display: flex; flex-direction: column; gap: 32px;">
                                            <h3 style="font-family: Aspekta, sans-serif; font-weight: 800; font-size: 32px; text-transform: uppercase; letter-spacing: 0.5px; margin: 0; color: #1D1D1D;">
                                                {{-- Use withCount value from controller (no extra query) --}}
                                                Komentar ({{ $item->approved_comments_count ?? 0 }})
                                            </h3>

                                            <!-- Success Alert -->
                                            @if(session('comment_success'))
                                                <div style="background: #256D4A; border: 4px solid #1D1D1D; color: white; padding: 20px; font-weight: 600; font-size: 16px; margin: 0;" class="shadow-[4px_4px_0px_0px_#1D1D1D]">
                                                    {{ session('comment_success') }}
                                                </div>
                                            @endif

                                            <!-- Comments List -->
                                            {{-- $approvedComments is pre-loaded in the controller with eager-loaded
                                                 replies to eliminate N+1 queries. --}}
                                            <div style="display: flex; flex-direction: column; gap: 24px;">
                                                @forelse($approvedComments as $comment)
                                                    <div style="background: white; border: 4px solid #1D1D1D; display: flex; flex-direction: column; gap: 16px;" class="p-4 md:p-5 shadow-[4px_4px_0px_0px_#1D1D1D]">
                                                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                                                            <div style="font-weight: 800; font-size: 18px; color: #1D1D1D; text-transform: uppercase; font-family: Aspekta, sans-serif;">{{ $comment->author_name }}</div>
                                                            <div style="font-size: 12px; color: #666; font-weight: 600; font-family: Montserrat, sans-serif;">{{ $comment->created_at->translatedFormat('d M Y - H:i') }}</div>
                                                        </div>
                                                        <p style="font-size: 16px; line-height: 1.6; color: #333; margin: 0; white-space: pre-wrap; font-family: Montserrat, sans-serif;">{{ $comment->body }}</p>
                                                        
                                                        <!-- Reply Button -->
                                                        <div style="display: flex; justify-content: flex-end;">
                                                            <button type="button" data-toggle-target="#reply-form-{{ $comment->id }}" style="background: transparent; border: none; font-size: 12px; font-weight: 700; color: #256D4A; cursor: pointer; text-transform: uppercase; padding: 0; font-family: Aspekta, sans-serif; letter-spacing: 0.5px;">Balas Komentar</button>
                                                        </div>

                                                        <!-- Replies nested list (already eager-loaded, zero extra queries) -->
                                                        @if($comment->replies->count() > 0)
                                                             <div style="margin-top: 12px; display: flex; flex-direction: column; gap: 16px; border-left: 4px solid #256D4A; padding-left: 20px;">
                                                                @foreach($comment->replies as $reply)
                                                                    <div style="background: #F4F1EA; border: 4px solid #1D1D1D; padding: 16px; display: flex; flex-direction: column; gap: 8px;" class="shadow-[4px_4px_0px_0px_#1D1D1D]">
                                                                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                                                                            <div style="font-weight: 800; font-size: 15px; color: #1D1D1D; text-transform: uppercase; font-family: Aspekta, sans-serif;">
                                                                                {{ $reply->author_name }}
                                                                            </div>
                                                                            <div style="font-size: 11px; color: #666; font-weight: 600; font-family: Montserrat, sans-serif;">{{ $reply->created_at->translatedFormat('d M Y - H:i') }}</div>
                                                                        </div>
                                                                        <p style="font-size: 14px; line-height: 1.5; color: #333; margin: 0; white-space: pre-wrap; font-family: Montserrat, sans-serif;">{{ $reply->body }}</p>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        @endif

                                                        <!-- Nested Reply Form -->
                                                        <div id="reply-form-{{ $comment->id }}" style="display: none; margin-top: 16px; padding-top: 20px; border-top: 2px dashed #1D1D1D;">
                                                            <form action="{{ route('comments.store', $item->id) }}" method="POST" style="display: flex; flex-direction: column; gap: 12px;">
                                                                @csrf
                                                                <input type="hidden" name="parent_id" value="{{ $comment->id }}" />
                                                                
                                                                <!-- Honeypot -->
                                                                <input type="text" name="extra_phone" style="display: none !important;" tabindex="-1" autocomplete="off" />

                                                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                                                    <input type="text" name="author_name" placeholder="Nama Anda" autocomplete="name" required style="border: 2px solid #1D1D1D; padding: 10px; font-size: 14px; outline: none; background: white; font-family: Montserrat, sans-serif;" />
                                                                    <input type="email" name="author_email" placeholder="Email Anda" autocomplete="email" required style="border: 2px solid #1D1D1D; padding: 10px; font-size: 14px; outline: none; background: white; font-family: Montserrat, sans-serif;" />
                                                                </div>
                                                                <textarea name="body" rows="3" placeholder="Tulis balasan komentar Anda..." required style="border: 2px solid #1D1D1D; padding: 10px; font-size: 14px; outline: none; background: white; font-family: Montserrat, sans-serif; resize: vertical;"></textarea>
                                                                <x-turnstile />
                                                                <button type="submit" style="align-self: flex-start; height: 44px; padding: 0 20px; background: #256D4A; color: white; border: 2px solid #1D1D1D; font-weight: 700; font-size: 12px; text-transform: uppercase; cursor: pointer; font-family: Aspekta, sans-serif;">Kirim Balasan</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <div style="font-style: italic; color: #666; font-size: 16px; font-family: Montserrat, sans-serif;">Belum ada komentar. Jadilah yang pertama memberikan tanggapan!</div>
                                                @endforelse
                                            </div>

                                            <!-- Add Comment Form -->
                                            <div style="background: white; border: 4px solid #1D1D1D; outline: 4px #1D1D1D solid; outline-offset: -4px;" class="p-5 md:p-6 lg:p-8 shadow-[8px_8px_0px_0px_#1D1D1D]">
                                                <h4 style="font-family: Aspekta, sans-serif; font-weight: 800; font-size: 26px; text-transform: uppercase; margin: 0 0 20px; color: #1D1D1D; letter-spacing: 0.5px;">Tulis Komentar Anda</h4>
                                                <form action="{{ route('comments.store', $item->id) }}" method="POST" style="display: flex; flex-direction: column; gap: 16px;">
                                                    @csrf
                                                    
                                                    <!-- Honeypot -->
                                                    <input type="text" name="extra_phone" style="display: none !important;" tabindex="-1" autocomplete="off" />

                                                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; width: 100%;">
                                                        <div style="display: flex; flex-direction: column; gap: 6px;">
                                                            <label style="font-weight: 700; font-size: 12px; text-transform: uppercase; color: #1D1D1D; font-family: Montserrat, sans-serif;">Nama Lengkap *</label>
                                                            <input type="text" name="author_name" autocomplete="name" required style="border: 2px solid #1D1D1D; padding: 12px; font-size: 15px; outline: none; background: white; font-family: Montserrat, sans-serif;" />
                                                        </div>
                                                        <div style="display: flex; flex-direction: column; gap: 6px;">
                                                            <label style="font-weight: 700; font-size: 12px; text-transform: uppercase; color: #1D1D1D; font-family: Montserrat, sans-serif;">Email (tidak dipublikasikan) *</label>
                                                            <input type="email" name="author_email" autocomplete="email" required style="border: 2px solid #1D1D1D; padding: 12px; font-size: 15px; outline: none; background: white; font-family: Montserrat, sans-serif;" />
                                                        </div>
                                                    </div>

                                                    <div style="display: flex; flex-direction: column; gap: 6px;">
                                                        <label style="font-weight: 700; font-size: 12px; text-transform: uppercase; color: #1D1D1D; font-family: Montserrat, sans-serif;">Isi Komentar *</label>
                                                        <textarea name="body" rows="5" required style="border: 2px solid #1D1D1D; padding: 12px; font-size: 15px; outline: none; background: white; font-family: Montserrat, sans-serif; resize: vertical;"></textarea>
                                                    </div>

                                                    <x-turnstile />
                                                    <button type="submit" style="align-self: flex-start; height: 52px; padding: 0 32px; background: #256D4A; color: white; border: 2px solid #1D1D1D; font-weight: 700; font-size: 14px; text-transform: uppercase; cursor: pointer; font-family: Aspekta, sans-serif;" class="btn-action">Kirim Komentar</button>
                                                </form>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <!-- Right Column: Sidebar (25% width) -->
                                <div class="lg:col-span-3 flex flex-col gap-8 lg:sticky lg:top-6">
                                    <!-- Subscription Box -->
                                    <div style="background: linear-gradient(135deg, #256D4A 0%, #8B6B4A 100%); border: 4px solid #1D1D1D;" class="p-4 md:p-5 shadow-[4px_4px_0px_0px_#1D1D1D]">
                                        <h4 style="font-family: Aspekta, sans-serif; font-weight: 800; font-size: 18px; color: white; text-transform: uppercase; margin: 0 0 16px; line-height: 1.3; letter-spacing: 0.5px;">Langganan Nawala WALHI Jabar untuk menerima notifikasi update kami</h4>
                                        
                                        @if(session('subscribe_success'))
                                            <div style="background: white; border: 2px solid #1D1D1D; color: #256D4A; padding: 12px; font-weight: 700; font-size: 13px; margin-bottom: 12px;">
                                                {{ session('subscribe_success') }}
                                            </div>
                                        @endif

                                        <form action="{{ route('newsletter.subscribe') }}" method="POST" style="display: flex; flex-direction: column; gap: 12px;">
                                            @csrf
                                            <!-- Honeypot -->
                                            <input type="text" name="extra_name" style="display: none !important;" tabindex="-1" autocomplete="off" />

                                            <input type="text" placeholder="Nama Lengkap" autocomplete="name" style="border: 2px solid #1D1D1D; padding: 12px; font-size: 14px; outline: none; background: white; font-family: Montserrat, sans-serif;" />
                                            <input type="email" name="email" placeholder="Email Anda" autocomplete="email" required style="border: 2px solid #1D1D1D; padding: 12px; font-size: 14px; outline: none; background: white; font-family: Montserrat, sans-serif;" />
                                            <x-turnstile size="compact" />
                                            <button type="submit" style="height: 48px; background: #1D1D1D; color: #F4F1EA; border: 2px solid #1D1D1D; font-weight: 700; font-size: 13px; text-transform: uppercase; cursor: pointer; font-family: Aspekta, sans-serif;" class="btn-action shadow-[2px_2px_0px_0px_#1D1D1D]">Berlangganan</button>
                                        </form>
                                    </div>

                                    <!-- Latest/Trending News Box -->
                                    <div style="background: white; border: 4px solid #1D1D1D;" class="shadow-[4px_4px_0px_0px_#1D1D1D]">
                                        <div style="background: linear-gradient(135deg, #256D4A 0%, #5C8D59 100%); border-bottom: 4px solid #1D1D1D; padding: 12px 16px;">
                                            <h4 style="font-family: Aspekta, sans-serif; font-weight: 800; font-size: 18px; color: white; text-transform: uppercase; margin: 0; letter-spacing: 0.5px;">Berita Terbaru</h4>
                                        </div>
                                        <div style="display: flex; flex-direction: column; gap: 16px;" class="p-4 md:p-5">
                                            @forelse($sidebarNews as $sideItem)
                                                <div style="display: flex; gap: 12px; align-items: center; border-bottom: 2px solid #f0ede8; padding-bottom: 16px; last-border: none;" class="last:border-0 last:pb-0">
                                                    <a href="{{ route('content.show', $sideItem->slug) }}" style="width: 72px; height: 72px; flex-shrink: 0; border: 2px solid #1D1D1D; overflow: hidden; background: #ddd; display: block;">
                                                        <img src="{{ $sideItem->image_url ?: asset('assets/images/blog/news-4-1.jpg') }}" alt="{{ $sideItem->title }}" style="width: 100%; height: 100%; object-fit: cover;" />
                                                    </a>
                                                    <div style="display: flex; flex-direction: column; gap: 4px; min-w: 0; flex: 1;">
                                                        <a href="{{ route('content.show', $sideItem->slug) }}" style="font-family: Aspekta, sans-serif; font-size: 14px; font-weight: 700; color: #1D1D1D; text-decoration: none; line-height: 1.3;" class="hover:text-[#256D4A] line-clamp-2">
                                                            {{ $sideItem->title }}
                                                        </a>
                                                        <span style="font-size: 11px; color: #888; font-weight: 600; font-family: Montserrat, sans-serif;">
                                                            {{ $sideItem->publish_date ? \Carbon\Carbon::parse($sideItem->publish_date)->translatedFormat('d M Y') : $sideItem->created_at->translatedFormat('d M Y') }}
                                                        </span>
                                                    </div>
                                                </div>
                                            @empty
                                                <div style="font-style: italic; color: #666; font-size: 13px; font-family: Montserrat, sans-serif;">Tidak ada berita terbaru.</div>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Related / Recommended News Section (at the bottom) -->
                            <div style="border-top: 4px solid #1D1D1D; margin-top: 56px; padding-top: 48px; display: flex; flex-direction: column; gap: 32px;" class="w-full max-w-5xl mx-auto">
                                <h3 style="font-family: Aspekta, sans-serif; font-weight: 800; font-size: 28px; text-transform: uppercase; letter-spacing: 0.5px; margin: 0; color: #1D1D1D;">
                                    Rekomendasi Berita Lainnya
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    @forelse($relatedNews as $rel)
                                        @php
                                            $relImage = $rel->image_url ?: asset('assets/images/blog/news-4-1.jpg');
                                            $relTag = array_map('trim', explode(',', $rel->tags ?? ''))[0] ?? 'Berita';
                                            $relDate = $rel->publish_date ? \Carbon\Carbon::parse($rel->publish_date)->translatedFormat('d M Y') : $rel->created_at->translatedFormat('d M Y');
                                            $relWordCount = str_word_count(strip_tags($rel->body));
                                            $relReadTime = ceil($relWordCount / 200) . ' menit';
                                        @endphp
                                        <div style="background: white; border: 4px solid #1D1D1D; display: flex; flex-direction: column; gap: 16px;" class="shadow-[4px_4px_0px_0px_#256D4A] hover:translate-x-1 hover:translate-y-1 transition-all">
                                            <div style="width: 100%; height: 180px; overflow: hidden; border-bottom: 4px solid #1D1D1D; position: relative; background: #eee;">
                                                <img src="{{ $relImage }}" alt="{{ $rel->title }}" style="width: 100%; height: 100%; object-fit: cover;" />
                                                <span style="position: absolute; left: 12px; top: 12px; background: #D95C3F; color: white; padding: 4px 8px; font-size: 10px; font-weight: 700; text-transform: uppercase; font-family: Montserrat, sans-serif;">{{ $relTag }}</span>
                                            </div>
                                            <div style="display: flex; flex-direction: column; justify-content: space-between; flex-grow: 1; gap: 16px;" class="p-4 md:p-5">
                                                <a href="{{ route('content.show', $rel->slug) }}" style="font-family: Aspekta, sans-serif; font-weight: 700; font-size: 16px; color: #1D1D1D; text-decoration: none; text-transform: uppercase; line-height: 1.3; letter-spacing: 0.3px;" class="hover:text-[#256D4A] line-clamp-2">
                                                    {{ $rel->title }}
                                                </a>
                                                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: #5C8D59; font-weight: 600; border-top: 2px solid #f0ede8; padding-top: 12px;">
                                                    <span>{{ $relDate }}</span>
                                                    <span>{{ $relReadTime }} Baca</span>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div style="font-style: italic; color: #666; font-size: 14px;">Tidak ada berita terkait lainnya.</div>
                                    @endforelse
                                </div>
                            </div>
                        @endif
 
                    </div>
                </section>
 
            </main>
 
            @include('partials.site-footer')
        </div>
        
        <script nonce="{{ Vite::cspNonce() }}">
            // Initialize Lucide icons on page load
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof lucide !== 'undefined' && lucide.createIcons) {
                    lucide.createIcons();
                }
            });

            function copyToClipboard(text) {
                navigator.clipboard.writeText(text).then(function() {
                    const msg = document.getElementById('copy-success-msg');
                    if (msg) {
                        msg.style.display = 'inline';
                        setTimeout(() => {
                            msg.style.display = 'none';
                        }, 2000);
                    }
                }, function(err) {
                    console.error('Gagal menyalin tautan: ', err);
                });
            }
        </script>
    </body>
</html>
