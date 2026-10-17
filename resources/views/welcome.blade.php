<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @include('partials.seo-meta')

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,600&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="{{ asset('assets/fonts/webfonts/font-face.css') }}">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-brand-cream antialiased text-brand-dark overflow-x-clip" style="font-family: Montserrat, sans-serif; overflow-x: clip;">
        @php
            $iqon = function (string $name): string {
                return asset('iqon/'.$name);
            };

            $socialLinks = [
                ['label' => 'Facebook', 'icon' => 'Icon-1.svg'],
                ['label' => 'Twitter', 'icon' => 'Icon-2.svg'],
                ['label' => 'Instagram', 'icon' => 'Icon-3.svg'],
            ];

            $mainNav = [
                ['label' => 'Beranda', 'href' => route('home'), 'active' => true],
                ['label' => 'Blog', 'href' => route('home').'#kabar'],
                ['label' => 'Regulasi', 'href' => route('home').'#isu'],
                ['label' => 'Publikasi', 'href' => route('home').'#kabar', 'dropdown' => true],
                ['label' => 'Dukung Kami', 'href' => route('home').'#donasi', 'dropdown' => true],
                ['label' => 'Tentang Kami', 'href' => route('about'), 'dropdown' => true],
            ];

            $fallbackIssues = [
                ['title' => 'Pertambangan Ilegal', 'slug' => 'pertambangan-ilegal-isu', 'image' => 'causes-1-1.jpg', 'icon' => 'Icon-4.svg', 'badge' => '200+ Titik', 'badgeColor' => '#D95C3F', 'copy' => 'Ratusan titik tambang ilegal di Jawa Barat merusak hutan, air, dan ruang hidup masyarakat.'],
                ['title' => 'Deforestasi', 'slug' => 'deforestasi-isu', 'image' => 'causes-1-2.jpg', 'icon' => 'Icon-5.svg', 'badge' => '15.000 Ha/Tahun', 'badgeColor' => '#8B6B4A', 'copy' => 'Alih fungsi hutan untuk perkebunan dan properti menggerus tutupan hijau dan memicu bencana ekologis.'],
                ['title' => 'Pencemaran Sungai', 'slug' => 'pencemaran-sungai-isu', 'image' => 'causes-1-3.jpg', 'icon' => 'Icon-6.svg', 'badge' => '75% Tercemar', 'badgeColor' => '#256D4A', 'copy' => 'Limbah industri dan rumah tangga mencemari sungai-sungai utama serta mengancam kesehatan warga.'],
                ['title' => 'Konflik Agraria', 'slug' => 'konflik-agraria-isu', 'image' => 'causes-1-4.jpg', 'icon' => 'Icon-7.svg', 'badge' => '2.500+ Kasus', 'badgeColor' => '#5C8D59', 'copy' => 'Ribuan keluarga petani kehilangan tanah akibat perampasan lahan dan proyek skala besar.'],
                ['title' => 'Krisis Iklim', 'slug' => 'krisis-iklim-isu', 'image' => 'causes-1-5.jpg', 'icon' => 'Icon-8.svg', 'badge' => '+2.5°C Target', 'badgeColor' => '#D95C3F', 'copy' => 'Banjir bandang, kekeringan ekstrem, dan cuaca tak menentu semakin sering melanda Jawa Barat.'],
                ['title' => 'Energi Kotor & PLTU', 'slug' => 'energi-kotor-pltu-isu', 'image' => 'causes-1-6.jpg', 'icon' => 'Icon-9.svg', 'badge' => '5 PLTU Raksasa', 'badgeColor' => '#8B6B4A', 'copy' => 'Ekspansi pembangkit batubara di pesisir Jawa Barat memicu polusi udara kronis dan merusak mata pencaharian nelayan.'],
            ];

            $fallbackStats = [
                ['value' => '21', 'label' => 'Wilayah Advokasi Kab/Kota', 'icon' => 'Icon-10.svg', 'color' => '#256D4A'],
                ['value' => '2.800+', 'label' => 'Kasus Dampingan (Akumulatif)', 'icon' => 'Icon-11.svg', 'color' => '#D95C3F'],
                ['value' => '15.000+', 'label' => 'Wilayah Kelola Rakyat (Ha)', 'icon' => 'Icon-12.svg', 'color' => '#5C8D59'],
                ['value' => '9', 'label' => 'Isu Mandat Strategis', 'icon' => 'Icon-13.svg', 'color' => '#8B6B4A'],
            ];

            $fallbackReports = [
                ['year' => '2025', 'title' => 'Laporan Tahunan: Krisis Lingkungan Jawa Barat', 'copy' => 'Analisis komprehensif kondisi lingkungan, kasus-kasus yang ditangani, dan rekomendasi kebijakan untuk tahun 2025.', 'meta' => ['124 Halaman', '2.4K Downloads'], 'button' => 'Download PDF', 'icon' => 'Icon-16.svg'],
                ['year' => '2025', 'title' => 'Investigasi: Jejak Tambang Ilegal di Kawasan Conservasi', 'copy' => 'Dokumentasi investigasi mendalam terhadap jaringan pertambangan ilegal yang merusak kawasan hutan lindung.', 'meta' => ['68 Halaman', '1.8K Downloads'], 'button' => 'Download PDF', 'icon' => 'Icon-16.svg'],
                ['year' => '2024', 'title' => 'Monitoring Kualitas Air Sungai Citarum', 'copy' => 'Data hasil monitoring bulanan kualitas air Sungai Citarum sepanjang 2024 dengan analisis dampak kesehatan.', 'meta' => ['42 Halaman', '3.1K Downloads'], 'button' => 'Download PDF', 'icon' => 'Icon-16.svg'],
            ];

            $fallbackFeaturedNews = ['image' => 'news-4-1.jpg', 'tag' => 'Investigasi', 'title' => 'Penelusuran Jejak Modal di Balik Tambang Ilegal Gunung Halimun', 'copy' => 'Investigasi mendalam mengungkap jaringan korporasi dan pejabat yang memfasilitasi pertambangan ilegal. Dokumen internal bocor, saksi kunci berbicara.', 'date' => '18 Mei 2026', 'read' => '12 menit'];

            $fallbackNewsCards = [
                ['image' => 'news-1-1.jpg', 'tag' => 'Advokasi', 'title' => 'Petani Garut Menang: Tanah Dikembalikan Setelah 3 Tahun Gugatan', 'copy' => 'Putusan pengadilan memenangkan gugatan 300 keluarga petani. Ini kemenangan hukum penting untuk kasus-kasus agraria serupa di seluruh Jawa Barat.', 'date' => '15 Mei 2026', 'read' => '8 menit'],
                ['image' => 'news-1-2.jpg', 'tag' => 'Laporan', 'title' => 'Data Baru: Tingkat Pencemaran Citarum Naik 23% dalam 6 Bulan', 'copy' => 'Monitoring terbaru menunjukkan peningkatan drastis polusi industri. Pemerintah diminta bertindak cepat sebelum kondisi semakin memburuk.', 'date' => '10 Mei 2026', 'read' => '6 menit'],
                ['image' => 'news-1-3.jpg', 'tag' => 'Kampanye', 'title' => 'Ratusan Aktivis Turun ke Jalan Tolak Pembangunan Pabrik di DAS', 'copy' => 'Aksi bersama menolak izin pembangunan pabrik semen di kawasan penyangga Daerah Aliran Sungai. Massa menuntut pencabutan izin lingkungan.', 'date' => '5 Mei 2026', 'read' => '5 menit'],
                ['image' => 'news-4-2.jpg', 'tag' => 'Pendidikan', 'title' => 'Peluncuran Sekolah Lapang: Petani Belajar Pertanian Ekologis', 'copy' => 'Program pendampingan petani untuk transisi dari metode konvensional ke pertanian ramah lingkungan. Hasil panen meningkat tanpa merusak tanah.', 'date' => '1 Mei 2026', 'read' => '7 menit'],
                ['image' => 'news-4-3.jpg', 'tag' => 'Opini', 'title' => "Mengapa Kita Harus Menolak 'Green Capitalism' dalam Krisis Iklim", 'copy' => 'Analisis kritis terhadap solusi pasar dalam menghadapi krisis ekologis. Apa yang dibutuhkan adalah transformasi sistem, bukan sekadar greenwashing.', 'date' => '28 Apr 2026', 'read' => '10 menit'],
                ['image' => 'news-2-1.jpg', 'tag' => 'Catatan Kritis', 'title' => 'Refleksi Kerusakan Ekologis Jawa Barat: Menolak Normalisasi Bencana', 'copy' => 'Tinjauan kritis WALHI Jawa Barat atas rentetan bencana banjir bandang dan longsor yang dipicu alih fungsi lahan dan lemahnya penegakan tata ruang.', 'date' => '20 Apr 2026', 'read' => '8 menit'],
            ];

            // Use database values if present, otherwise fallback
            $issues = (isset($issues) && $issues->isNotEmpty()) ? $issues : $fallbackIssues;
            $stats = (isset($stats) && $stats->isNotEmpty()) ? $stats : $fallbackStats;
            $reports = (isset($reports) && $reports->isNotEmpty()) ? $reports : $fallbackReports;
            $featuredNews = (isset($featuredNews) && $featuredNews) ? $featuredNews : $fallbackFeaturedNews;
            $newsCards = (isset($newsCards) && $newsCards->isNotEmpty()) ? $newsCards : $fallbackNewsCards;

            $fallbackBanners = [
                [
                    'title' => 'PULIHKAN JAWA BARAT',
                    'subtitle' => '#Sehari Menjadi Lebih Peduli',
                    'image' => asset('iqon/Container.png'),
                    'btn1_text' => 'Isu Strategis',
                    'btn1_url' => '#isu',
                    'btn2_text' => 'Lihat Publikasi',
                    'btn2_url' => route('siaran-pers'),
                ],
                [
                    'title' => 'SELAMATKAN HUTAN & AIR',
                    'subtitle' => 'Hentikan Tambang Ilegal & Perusakan Sumber Kehidupan',
                    'image' => asset('iqon/causes-1-1.jpg'),
                    'btn1_text' => 'Isu Lingkungan',
                    'btn1_url' => '#isu',
                    'btn2_text' => 'Dukung Donasi',
                    'btn2_url' => route('donasi'),
                ],
                [
                    'title' => 'KEADILAN RUANG HIDUP',
                    'subtitle' => 'Bersama Komunitas Melindungi Wilayah Kelola Rakyat',
                    'image' => asset('iqon/causes-1-2.jpg'),
                    'btn1_text' => 'Tentang Kami',
                    'btn1_url' => route('about'),
                    'btn2_text' => 'Laporan Tahunan',
                    'btn2_url' => route('laporan-tahunan'),
                ],
            ];

            $bannerSlides = [];
            if (isset($banners) && $banners->isNotEmpty()) {
                foreach ($banners as $b) {
                    $parts = explode('|', $b->tags ?? '');
                    $btn1Text = !empty($parts[0]) ? $parts[0] : 'Isu Strategis';
                    $btn1Url  = !empty($parts[1]) ? \App\Rules\SafeUrl::sanitize($parts[1]) : '#isu';
                    $btn2Text = !empty($parts[2]) ? $parts[2] : 'Lihat Publikasi';
                    $btn2Url  = !empty($parts[3]) ? \App\Rules\SafeUrl::sanitize($parts[3]) : route('siaran-pers');

                    $img = asset('iqon/Container.png');
                    if ($b->image_url) {
                        $img = str_starts_with($b->image_url, 'http') ? $b->image_url : asset(ltrim($b->image_url, '/'));
                    }

                    $subtitle = trim(strip_tags($b->body ?? ''));
                    if (empty($subtitle)) {
                        $subtitle = '#Sehari Menjadi Lebih Peduli';
                    }

                    $bannerSlides[] = [
                        'title' => $b->title,
                        'subtitle' => $subtitle,
                        'image' => $img,
                        'btn1_text' => $btn1Text,
                        'btn1_url' => $btn1Url,
                        'btn2_text' => $btn2Text,
                        'btn2_url' => $btn2Url,
                    ];
                }
            } else {
                $bannerSlides = $fallbackBanners;
            }
        @endphp

        @include('partials.site-header')

        <main class="w-full">
            <!-- Hero Banner Slider Section -->
            <section 
                x-data="heroSlider({{ count($bannerSlides) }})"
                @mouseenter="stop"
                @mouseleave="autoplay"
                @touchstart="handleTouchStart"
                @touchend="handleTouchEnd"
                class="relative min-h-[600px] md:h-[836px] w-full overflow-hidden bg-brand-dark text-brand-cream select-none"
                aria-label="Hero Banner Slider"
            >
                <!-- Sliding Track -->
                <div 
                    class="flex w-full h-full transition-transform duration-700 ease-in-out"
                    :style="trackStyle"
                >
                    @foreach($bannerSlides as $index => $slide)
                        <div 
                            class="w-full h-full min-h-[600px] md:h-[836px] flex-shrink-0 relative flex items-center py-20 px-6 sm:px-12 md:px-24"
                        >
                            <!-- Background Image -->
                            <div 
                                class="absolute inset-0 bg-cover bg-center"
                                style="background-image: url('{{ $slide['image'] }}');"
                            ></div>

                            <!-- High-Contrast Gradient Overlay -->
                            <div class="absolute inset-0 bg-[linear-gradient(180deg,rgba(29,29,29,0.75)_0%,rgba(29,29,29,0.55)_50%,rgba(29,29,29,0.85)_100%)]"></div>

                            <!-- Content Container -->
                            <div class="relative w-full max-w-6xl mx-auto z-10 flex flex-col gap-6 md:gap-8 items-start">
                                <!-- Main Heading -->
                                <h1 class="text-3xl sm:text-5xl md:text-7xl lg:text-8xl font-heading font-extrabold uppercase leading-none tracking-wide text-brand-cream drop-shadow-sm">
                                    {{ $slide['title'] }}
                                </h1>

                                <!-- Divider Orange -->
                                <div class="w-24 sm:w-48 md:w-[480px] h-2 bg-brand-orange"></div>

                                <!-- Subheading -->
                                <h2 class="text-xl sm:text-2xl md:text-3xl lg:text-4xl font-heading font-bold uppercase leading-tight tracking-wider text-brand-green-light max-w-3xl">
                                    {{ $slide['subtitle'] }}
                                </h2>

                                <!-- Action Buttons -->
                                <div class="flex flex-col sm:flex-row gap-4 w-full sm:w-auto pt-2">
                                    @if(!empty($slide['btn1_text']))
                                        <a 
                                            href="{{ $slide['btn1_url'] }}" 
                                            class="flex h-[56px] md:h-[60px] sm:w-[208px] px-6 items-center justify-center border-2 border-brand-cream bg-brand-dark/80 text-[15px] md:text-[16px] font-bold uppercase tracking-[0.40px] text-brand-cream hover:bg-brand-cream hover:text-brand-dark transition-all duration-200"
                                        >
                                            {{ $slide['btn1_text'] }}
                                        </a>
                                    @endif

                                    @if(!empty($slide['btn2_text']))
                                        <a 
                                            href="{{ $slide['btn2_url'] }}" 
                                            class="flex h-[56px] md:h-[60px] sm:w-[227px] px-6 items-center justify-center border-2 border-brand-orange bg-brand-orange text-[15px] md:text-[16px] font-bold uppercase tracking-[0.40px] text-brand-cream hover:bg-transparent hover:text-brand-orange transition-all duration-200"
                                        >
                                            {{ $slide['btn2_text'] }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Sleek Circular Prev & Next Controls (No kotak-kotak) -->
                @if(count($bannerSlides) > 1)
                    <div class="absolute inset-y-0 left-4 sm:left-8 flex items-center z-20 pointer-events-none">
                        <button 
                            type="button"
                            @click="prevAndRestart" 
                            class="pointer-events-auto w-11 h-11 md:w-13 md:h-13 rounded-full bg-black/40 hover:bg-brand-orange text-brand-cream border border-white/20 flex items-center justify-center backdrop-blur-md transition-all duration-300 hover:scale-105 cursor-pointer"
                            aria-label="Slide Sebelumnya"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                        </button>
                    </div>
                    <div class="absolute inset-y-0 right-4 sm:right-8 flex items-center z-20 pointer-events-none">
                        <button 
                            type="button"
                            @click="nextAndRestart" 
                            class="pointer-events-auto w-11 h-11 md:w-13 md:h-13 rounded-full bg-black/40 hover:bg-brand-orange text-brand-cream border border-white/20 flex items-center justify-center backdrop-blur-md transition-all duration-300 hover:scale-105 cursor-pointer"
                            aria-label="Slide Selanjutnya"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                        </button>
                    </div>

                    <!-- Clean Rounded Bullet Indicators (No kotak-kotak) -->
                    <div class="absolute bottom-8 left-1/2 -translate-x-1/2 z-20 flex items-center gap-2.5">
                        @foreach($bannerSlides as $idx => $s)
                            <button 
                                type="button"
                                @click="goToAndRestart({{ $idx }})"
                                class="transition-all duration-300 cursor-pointer rounded-full"
                                :class="bulletClass({{ $idx }})"
                                aria-label="Menuju Slide {{ $idx + 1 }}"
                            ></button>
                        @endforeach
                    </div>
                @endif

                <!-- Scroll Indicator (Center Bottom) -->
                <div class="absolute bottom-8 left-12 hidden md:flex flex-col items-center gap-2 text-[12px] font-semibold uppercase tracking-[0.1em] text-brand-cream z-10 pointer-events-none opacity-70">
                    <span>Scroll</span>
                    <span class="h-8 w-px bg-brand-cream"></span>
                </div>
            </section>

            <!-- Tentang Kami Section -->
            <section id="tentang" class="w-full border-b-4 border-brand-dark bg-brand-cream py-16 md:py-24 px-6 sm:px-12 md:px-24">
                <div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-12 items-start">
                    <!-- Left Side: Deskripsi -->
                    <div class="lg:col-span-6 flex flex-col items-start gap-6">
                        <div class="flex h-[26px] items-center bg-brand-green px-4 text-[12px] font-bold uppercase tracking-[0.06em] text-brand-cream">Tentang Kami</div>
                        <h2 class="text-4xl sm:text-5xl lg:text-6xl font-heading font-extrabold uppercase leading-none tracking-wide text-brand-dark">
                            Melawan untuk<br>
                            Keadilan Ekologis
                        </h2>
                        <div class="h-1 w-24 bg-brand-orange"></div>
                        <div class="flex flex-col gap-4 text-base md:text-lg leading-relaxed text-brand-dark font-sans">
                            <p><strong>WALHI Jawa Barat adalah</strong> simpul gerakan lingkungan hidup independen yang berjuang sejak tahun 1980 untuk menegakkan kedaulatan rakyat atas sumber-sumber kehidupan. Kami mendampingi komunitas terdampak, mengadvokasi kebijakan yang adil, serta mendorong pemulihan ekosistem di Jawa Barat lewat prinsip keadilan ekologis dan kemanusiaan.</p>
                            <p>Kami adalah bagian dari jaringan WALHI Nasional dan Friends of the Earth (FoE) yang berfokus di lingkup daerah Jawa Barat. Tujuan kami adalah <strong>mendorong terwujudnya pengakuan atas lingkungan hidup dan dilindungi serta dipenuhinya hak asasi manusia sebagai bentuk dari tanggung jawab negara atas pemenuhan sumber-sumber kehidupan rakyat.</strong></p>
                        </div>
                    </div>

                    <!-- Right Side: Misi & Nilai Cards -->
                    <div class="lg:col-span-6 flex flex-col gap-6 w-full">
                        <!-- Card 1: Misi -->
                        <div class="flex flex-col gap-3 border-2 border-brand-dark bg-brand-dark p-6 md:p-8 text-brand-cream shadow-[4px_4px_0px_0px_#256D4A]">
                            <h3 class="text-xl sm:text-2xl font-heading font-bold uppercase leading-tight tracking-wide text-brand-green-light">Misi Transformasi Sosial</h3>
                            <ol class="flex flex-col gap-2 text-xs md:text-sm leading-relaxed text-brand-cream/90 list-decimal list-inside font-sans">
                                <li>Mengembangkan potensi kekuatan dan ketahanan rakyat;</li>
                                <li>Mengembalikan mandat negara untuk menegakkan dan melindungi kedaulatan rakyat;</li>
                                <li>Mendekonstruksikan tatanan ekonomi kapitalistik global yang menindas dan eksploitatif menuju ke arah ekonomi kerakyatan;</li>
                                <li>Membangun alternatif tata ekonomi dunia baru; serta</li>
                                <li>Mendesakkan kebijakan pengelolaan sumber-sumber kehidupan rakyat yang adil dan berkelanjutan.</li>
                            </ol>
                            <a href="{{ route('about') }}" class="mt-2 inline-flex items-center gap-2 text-[14px] font-bold uppercase tracking-[0.04em] text-brand-orange hover:text-brand-cream transition-colors">
                                <span>Selengkapnya visi & misi</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                            </a>
                        </div>
                        <!-- Card 2: Nilai-Nilai -->
                        <div class="flex flex-col gap-3 border-2 border-brand-dark bg-brand-green p-6 md:p-8 text-brand-cream shadow-[4px_4px_0px_0px_#1D1D1D]">
                            <h3 class="text-xl sm:text-2xl font-heading font-bold uppercase leading-tight tracking-wide text-brand-cream">Nilai-Nilai Pokok</h3>
                            <div class="flex flex-wrap gap-2 pt-1 font-sans">
                                <span class="bg-[#1D1D1D] text-brand-cream px-2.5 py-1 text-xs font-semibold uppercase">Hak Asasi Manusia</span>
                                <span class="bg-[#1D1D1D] text-brand-cream px-2.5 py-1 text-xs font-semibold uppercase">Demokrasi</span>
                                <span class="bg-[#1D1D1D] text-brand-cream px-2.5 py-1 text-xs font-semibold uppercase">Keadilan Gender</span>
                                <span class="bg-[#1D1D1D] text-brand-cream px-2.5 py-1 text-xs font-semibold uppercase">Keadilan Ekologis</span>
                                <span class="bg-[#1D1D1D] text-brand-cream px-2.5 py-1 text-xs font-semibold uppercase">Keadilan Antara Generasi</span>
                                <span class="bg-[#1D1D1D] text-brand-cream px-2.5 py-1 text-xs font-semibold uppercase">Persaudaraan Sosial</span>
                                <span class="bg-[#1D1D1D] text-brand-cream px-2.5 py-1 text-xs font-semibold uppercase">Anti Kekerasan</span>
                                <span class="bg-[#1D1D1D] text-brand-cream px-2.5 py-1 text-xs font-semibold uppercase">Keberagaman</span>
                            </div>
                            <a href="{{ route('about') }}" class="mt-2 inline-flex items-center gap-2 text-[14px] font-bold uppercase tracking-[0.04em] text-brand-orange hover:text-brand-cream transition-colors">
                                <span>Lihat struktur kepengurusan</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Isu Kritis Section -->
            <section id="isu" class="w-full bg-brand-dark py-16 md:py-24 px-6 sm:px-12 md:px-24 text-brand-cream">
                <div class="max-w-6xl mx-auto flex flex-col gap-16">
                    <div class="flex flex-col items-center text-center gap-4 w-full">
                        <div class="bg-brand-orange px-4 py-1.5 text-[12px] font-bold uppercase tracking-[0.06em] text-brand-cream">Isu Kritis</div>
                        <h2 class="text-4xl sm:text-5xl lg:text-6xl font-heading font-extrabold uppercase leading-tight tracking-[0.02em] text-brand-cream text-center">
                            Krisis Lingkungan<br>
                            Jawa Barat
                        </h2>
                        <div class="h-1 w-24 bg-brand-green-light"></div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 w-full">
                        @foreach ($issues as $issue)
                            @php
                                if ($issue instanceof \App\Models\Content) {
                                    $issueTitle = $issue->title;
                                    $issueCopy = $issue->body;
                                    
                                    $colors = ['#D95C3F', '#8B6B4A', '#256D4A', '#5C8D59', '#D95C3F', '#8B6B4A'];
                                    $idx = $loop->index % 6;
                                    $issueBadgeColor = $colors[$idx];
                                    
                                    if (strpos($issue->tags, '|') !== false) {
                                        list($issueIcon, $issueBadge) = explode('|', $issue->tags, 2);
                                    } else {
                                        $issueBadge = $issue->tags;
                                        $icons = ['Icon-4.svg', 'Icon-5.svg', 'Icon-6.svg', 'Icon-7.svg', 'Icon-8.svg', 'Icon-9.svg'];
                                        $issueIcon = $icons[$idx];
                                    }
                                    
                                    if ($issue->image_url) {
                                        if (str_starts_with($issue->image_url, 'http') || str_starts_with($issue->image_url, '/')) {
                                            $issueImage = asset($issue->image_url);
                                        } else {
                                            $issueImage = asset('assets/images/resources/' . $issue->image_url);
                                        }
                                    } else {
                                        $issueImage = asset('assets/images/resources/causes-1-' . ($idx + 1) . '.jpg');
                                    }
                                    $issueUrl = route('content.show', $issue->slug);
                                } else {
                                    $issueTitle = $issue['title'];
                                    $issueCopy = $issue['copy'];
                                    $issueBadge = $issue['badge'];
                                    $issueBadgeColor = $issue['badgeColor'];
                                    $issueIcon = $issue['icon'];
                                    $issueImage = asset('assets/images/resources/' . $issue['image']);
                                    $issueUrl = !empty($issue['slug']) ? route('content.show', $issue['slug']) : '#';
                                }

                                $cleanBody = strip_tags($issueCopy);
                                $wordsArray = preg_split('/\s+/', trim($cleanBody));
                                $hasMore = count($wordsArray) > 13;
                                if ($hasMore) {
                                    $issueCopyLimited = implode(' ', array_slice($wordsArray, 0, 13)) . '...';
                                } else {
                                    $issueCopyLimited = $cleanBody;
                                }
                            @endphp
                            <article class="flex flex-col overflow-hidden border-4 border-brand-cream bg-white text-brand-dark shadow-[4px_4px_0px_0px_#D95C3F]">
                                <div class="relative h-[192px] overflow-hidden bg-cover bg-center" style="background-image: url('{{ $issueImage }}');">
                                    <div class="absolute left-4 top-4 flex h-[44px] items-center border-2 border-brand-cream bg-brand-dark px-3.5 text-xs font-bold uppercase tracking-wider font-sans" style="color: {{ $issueBadgeColor }};">
                                        {{ $issueBadge }}
                                    </div>
                                </div>
                                <div class="flex flex-grow flex-col gap-4 px-6 py-6 justify-between">
                                    <div class="flex flex-col gap-3">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-10 w-10 items-center justify-center bg-brand-dark p-2 flex-shrink-0">
                                                <img src="{{ $iqon($issueIcon) }}" alt="{{ $issueTitle }}" class="h-6 w-6 object-contain">
                                            </div>
                                            <h3 class="text-xl sm:text-2xl font-heading font-bold uppercase leading-snug tracking-wide text-brand-dark">
                                                <a href="{{ $issueUrl }}" class="hover:text-brand-green transition-colors">{{ $issueTitle }}</a>
                                            </h3>
                                        </div>
                                        <p class="text-sm md:text-base leading-relaxed text-brand-dark/95 font-sans">
                                            {{ $issueCopyLimited }}
                                            @if($hasMore)
                                                <a href="{{ $issueUrl }}" class="text-brand-green font-semibold hover:underline text-xs ml-1 whitespace-nowrap">Baca Selengkapnya</a>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <!-- Provenance Disclaimer Note -->
                    <div class="border-t border-brand-cream/20 pt-6 text-center text-xs md:text-sm font-sans text-brand-cream/80 max-w-3xl mx-auto leading-relaxed">
                        <strong class="text-brand-green-light uppercase tracking-wider">Sumber &amp; Metodologi Data:</strong> Data dihimpun dari laporan advokasi &amp; investigasi WALHI Jawa Barat (2020–2025), konsolidasi Konsorsium Pembaruan Agraria (KPA), analisis Forest Watch Indonesia (FWI), serta pemantauan baku mutu air sungai DLH/KLHK.
                    </div>
                </div>
            </section>

            <!-- Data & Laporan Section -->
            <section class="w-full bg-brand-dark py-16 md:py-24 px-6 sm:px-12 md:px-24 text-brand-cream border-t border-brand-cream/10">
                <div class="max-w-6xl mx-auto flex flex-col gap-16">
                    <div class="flex flex-col items-center text-center gap-4 w-full">
                        <div class="bg-brand-green px-4 py-1.5 text-[12px] font-bold uppercase tracking-[0.06em] text-brand-cream">Data & Laporan</div>
                        <h2 class="text-4xl sm:text-5xl lg:text-6xl font-heading font-extrabold uppercase leading-tight tracking-[0.02em] text-brand-cream text-center">
                            Fakta dan Bukti<br>
                            Kerusakan Lingkungan
                        </h2>
                        <div class="h-1 w-24 bg-brand-orange"></div>
                    </div>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 w-full">
                        @foreach ($stats as $stat)
                            @php
                                if ($stat instanceof \App\Models\Content) {
                                    $statLabel = $stat->title;
                                    $statValue = $stat->body;
                                    
                                    $icons = ['Icon-10.svg', 'Icon-11.svg', 'Icon-12.svg', 'Icon-13.svg'];
                                    $colors = ['#256D4A', '#D95C3F', '#5C8D59', '#8B6B4A'];
                                    $idx = $loop->index % 4;
                                    $statIcon = $icons[$idx];
                                    $statColor = $colors[$idx];
                                } else {
                                    $statLabel = $stat['label'];
                                    $statValue = $stat['value'];
                                    $statIcon = $stat['icon'];
                                    $statColor = $stat['color'];
                                }
                            @endphp
                            <div class="bg-white border-4 border-brand-cream p-6 flex flex-col items-center justify-center gap-4 text-center shadow-[4px_4px_0px_0px_#256D4A]">
                                <div class="flex h-14 w-14 items-center justify-center bg-brand-dark p-3">
                                    <img src="{{ $iqon($statIcon) }}" alt="{{ $statLabel }}" class="h-8 w-8 object-contain">
                                </div>
                                <div class="text-4xl sm:text-5xl font-heading font-extrabold leading-none" style="color: {{ $statColor }};">{{ $statValue }}</div>
                                <div class="text-sm font-semibold uppercase tracking-wider text-brand-dark font-sans">{{ $statLabel }}</div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Clarification Note on Historical Cumulative Cases vs Annual Reports -->
                    <div class="text-xs md:text-sm text-brand-cream/80 font-sans text-center -mt-8 max-w-3xl mx-auto leading-relaxed">
                        <span class="font-bold text-brand-orange uppercase tracking-wide">* Catatan Periode Kasus:</span> Angka <strong>2.800+ Kasus</strong> merupakan akumulasi historis pendampingan rakyat sejak berdirinya WALHI Jawa Barat (1980–sekarang), sedangkan Laporan Tahunan menyajikan data kasus spesifik tahun berjalan (e.g. 289 kasus kerusakan lingkungan yang ditangani pada tahun 2025).
                    </div>

                    <!-- Reports list -->
                    <div class="flex w-full flex-col gap-6">
                        @foreach ($reports as $report)
                            @php
                                if ($report instanceof \App\Models\Content) {
                                    $bodyData = json_decode($report->body, true);
                                    $reportYear = $report->publish_date ? \Carbon\Carbon::parse($report->publish_date)->format('Y') : '2025';
                                    $reportTitle = $report->title;
                                    $reportCopy = $bodyData['subtitle'] ?? $report->body;
                                    $reportPages = $bodyData['pages'] ?? '156 Halaman';
                                    $reportDownloads = $report->views . ' Kali Dibaca';
                                    $reportUrl = route('content.show', $report->slug);
                                } else {
                                    $reportYear = $report['year'];
                                    $reportTitle = $report['title'];
                                    $reportCopy = $report['copy'];
                                    $reportPages = $report['meta'][0];
                                    $reportDownloads = $report['meta'][1];
                                    $reportUrl = '#';
                                }
                            @endphp
                            <article class="flex flex-col md:flex-row overflow-hidden border-4 border-brand-cream bg-brand-cream text-brand-dark shadow-[4px_4px_0px_0px_#1D1D1D]">
                                <!-- Year Block -->
                                <div class="flex w-full md:w-32 md:flex-col justify-between md:justify-start items-center md:items-start bg-brand-green p-4 md:p-6 text-brand-cream">
                                    <div class="text-[12px] font-bold uppercase tracking-[0.06em] font-sans">Tahun</div>
                                    <div class="text-2xl md:text-4xl font-heading font-extrabold leading-none tracking-wider md:pt-1">{{ $reportYear }}</div>
                                </div>
                                <!-- Content Block -->
                                <div class="flex flex-col md:flex-row flex-1 items-start md:items-center justify-between gap-6 p-6">
                                    <div class="flex flex-col gap-3 max-w-2xl">
                                        <h3 class="text-xl md:text-2xl font-heading font-bold uppercase tracking-wide text-brand-dark leading-snug">{{ $reportTitle }}</h3>
                                        <p class="text-sm md:text-base leading-relaxed text-brand-dark font-sans">{{ $reportCopy }}</p>
                                        <div class="flex items-center gap-4 text-xs md:text-sm font-semibold text-brand-green-light">
                                            <span>▪ {{ $reportPages }}</span>
                                            <span>▪ {{ $reportDownloads }}</span>
                                        </div>
                                    </div>
                                    <a href="{{ $reportUrl }}" class="flex h-[49px] items-center justify-center gap-2 border-2 border-brand-dark bg-brand-dark px-6 text-sm font-bold uppercase tracking-wider text-brand-cream w-full md:w-auto hover:bg-brand-cream hover:text-brand-dark transition-colors whitespace-nowrap">
                                        <img src="{{ $iqon('Icon-16.svg') }}" alt="Download" class="h-[18px] w-[18px] object-contain">
                                        <span>Lihat Laporan</span>
                                    </a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>

            <!-- Kabar (Liputan & Investigasi) Section -->
            <section id="kabar" class="w-full border-b-4 border-brand-dark bg-brand-cream py-16 md:py-24 px-6 sm:px-12 md:px-24 text-brand-dark">
                <div class="max-w-6xl mx-auto flex flex-col gap-12">
                    <!-- Header Block -->
                    <div class="w-full flex flex-col md:flex-row md:items-end justify-between gap-6 pb-8 border-b-2 border-brand-green">
                        <div class="flex flex-col items-start gap-4">
                            <div class="bg-brand-orange px-4 py-1.5 text-[12px] font-bold uppercase tracking-[0.06em] text-brand-cream">Siaran Pers</div>
                            <h2 class="text-4xl sm:text-5xl lg:text-6xl font-heading font-extrabold uppercase leading-tight tracking-[0.02em] text-brand-dark">Siaran Pers dan<br>Hasil Investigasi</h2>
                        </div>
                        <a href="{{ route('blog') }}" class="flex h-[49px] items-center justify-center gap-2 border-2 border-brand-dark bg-brand-dark px-6 text-sm font-bold uppercase tracking-wider text-brand-cream hover:bg-transparent hover:text-brand-dark transition-colors whitespace-nowrap">
                            <span>Lihat Semua Artikel</span>
                            <img src="{{ $iqon('Icon-17.svg') }}" alt="Arrow" class="h-[18px] w-[18px] object-contain">
                        </a>
                    </div>

                    <!-- Featured Article -->
                    @if($featuredNews)
                        @php
                            if ($featuredNews instanceof \App\Models\Content) {
                                $featImage = $featuredNews->image_url ?: asset('assets/images/blog/news-4-1.jpg');
                                $featTag = array_map('trim', explode(',', $featuredNews->tags ?? ''))[0] ?? 'Liputan';
                                $featTitle = $featuredNews->title;
                                $featCopy = $featuredNews->body;
                                $wordCount = str_word_count(strip_tags($featuredNews->body));
                                $featRead = ceil($wordCount / 200) . ' menit';
                                $featUrl = route('content.show', $featuredNews->slug);
                                if ($featuredNews->publish_date) {
                                    $featDate = \Carbon\Carbon::parse($featuredNews->publish_date)->translatedFormat('d M Y');
                                } else {
                                    $featDate = $featuredNews->created_at->translatedFormat('d M Y');
                                }
                            } else {
                                $featImage = asset('assets/images/blog/' . $featuredNews['image']);
                                $featTag = $featuredNews['tag'];
                                $featTitle = $featuredNews['title'];
                                $featCopy = $featuredNews['copy'];
                                $featDate = $featuredNews['date'];
                                $featRead = $featuredNews['read'];
                                $featUrl = '#';
                            }

                            $cleanFeat = strip_tags($featCopy);
                            $wordsArray = preg_split('/\s+/', trim($cleanFeat));
                            $hasMoreFeat = count($wordsArray) > 13;
                            if ($hasMoreFeat) {
                                $featCopyLimited = implode(' ', array_slice($wordsArray, 0, 13)) . '...';
                            } else {
                                $featCopyLimited = $cleanFeat;
                            }
                        @endphp
                        <article class="flex flex-col lg:flex-row w-full overflow-hidden border-4 border-brand-dark bg-white shadow-[8px_8px_0px_0px_#256D4A]">
                            <!-- Featured Image -->
                            <div class="relative min-h-[280px] lg:w-1/2 overflow-hidden bg-cover bg-center" style="background-image: url('{{ $featImage }}');">
                                <div class="absolute left-4 top-4 bg-brand-orange px-4 py-2 text-[12px] font-bold uppercase tracking-[0.06em] text-brand-cream">{{ $featTag }}</div>
                            </div>
                            <!-- Featured Content -->
                            <div class="flex lg:w-1/2 flex-col justify-between p-6 md:p-8 text-brand-dark gap-6">
                                <div class="flex flex-col gap-4">
                                    <h3 class="text-2xl md:text-3xl lg:text-4xl font-heading font-bold uppercase leading-tight tracking-wide text-brand-dark">{{ $featTitle }}</h3>
                                    <p class="text-sm md:text-base leading-relaxed text-brand-dark/80 font-sans">
                                        {{ $featCopyLimited }}
                                        @if($hasMoreFeat)
                                            <a href="{{ $featUrl }}" class="text-brand-green font-semibold hover:underline text-xs ml-1 whitespace-nowrap">Baca Selengkapnya</a>
                                        @endif
                                    </p>
                                </div>
                                <div class="flex flex-wrap items-center justify-between border-t-2 border-brand-dark pt-6 text-[14px] font-semibold leading-[20px] text-brand-green-light gap-4">
                                    <div class="flex items-center gap-4">
                                        <span>{{ $featDate }}</span>
                                        <span>▪ {{ $featRead }}</span>
                                    </div>
                                    <a href="{{ $featUrl }}" class="flex items-center gap-2 text-brand-dark font-bold hover:text-brand-green transition-colors">
                                        <span>Baca Selengkapnya</span>
                                        <img src="{{ $iqon('Icon-18.svg') }}" alt="Detail" class="h-[18px] w-[18px] object-contain">
                                    </a>
                                </div>
                            </div>
                        </article>
                    @endif

                    <!-- News Cards Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 w-full mt-6">
                        @foreach ($newsCards as $news)
                            @php
                                if ($news instanceof \App\Models\Content) {
                                    $newsImage = $news->image_url ?: asset('assets/images/blog/news-1-1.jpg');
                                    $newsTag = array_map('trim', explode(',', $news->tags ?? ''))[0] ?? 'Advokasi';
                                    $newsTitle = $news->title;
                                    $newsCopy = $news->body;
                                    $newsDate = $news->publish_date ? \Carbon\Carbon::parse($news->publish_date)->translatedFormat('d M Y') : $news->created_at->translatedFormat('d M Y');
                                    $wordCount = str_word_count(strip_tags($news->body));
                                    $newsRead = ceil($wordCount / 200) . ' menit';
                                    $newsUrl = route('content.show', $news->slug);
                                } else {
                                    $newsImage = asset('assets/images/blog/' . $news['image']);
                                    $newsTag = $news['tag'];
                                    $newsTitle = $news['title'];
                                    $newsCopy = $news['copy'];
                                    $newsDate = $news['date'];
                                    $newsRead = $news['read'];
                                    $newsUrl = '#';
                                }

                                $cleanNews = strip_tags($newsCopy);
                                $wordsArray = preg_split('/\s+/', trim($cleanNews));
                                $hasMoreNews = count($wordsArray) > 13;
                                if ($hasMoreNews) {
                                    $newsCopyLimited = implode(' ', array_slice($wordsArray, 0, 13)) . '...';
                                } else {
                                    $newsCopyLimited = $cleanNews;
                                }
                            @endphp
                            <article class="flex flex-col overflow-hidden border-4 border-brand-dark bg-white shadow-[4px_4px_0px_0px_#1D1D1D]">
                                <div class="relative h-[192px] overflow-hidden bg-cover bg-center" style="background-image: url('{{ $newsImage }}');">
                                    <div class="absolute left-4 top-4 px-3 py-1 text-[12px] font-bold uppercase tracking-[0.06em] text-brand-cream" style="background-color: {{ $loop->index % 3 === 2 ? '#8B6B4A' : ($loop->index % 3 === 1 ? '#5C8D59' : '#256D4A') }};">{{ $newsTag }}</div>
                                </div>
                                <div class="flex flex-1 flex-col justify-between px-6 py-6 text-brand-dark gap-6">
                                    <div class="flex flex-col gap-4">
                                        <a href="{{ $newsUrl }}" class="hover:text-brand-green transition-colors">
                                            <h4 class="text-lg md:text-xl font-heading font-bold uppercase leading-snug tracking-normal text-brand-dark hover:underline">{{ $newsTitle }}</h4>
                                        </a>
                                        <p class="text-sm leading-relaxed text-brand-dark/90 font-sans">
                                            {{ $newsCopyLimited }}
                                            @if($hasMoreNews)
                                                <a href="{{ $newsUrl }}" class="text-brand-green font-semibold hover:underline text-xs ml-1 whitespace-nowrap">Baca Selengkapnya</a>
                                            @endif
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-3 border-t border-brand-dark/20 pt-4 text-[12px] font-semibold leading-[16px] text-brand-green-light font-sans">
                                        <span>{{ $newsDate }}</span>
                                        <span>▪</span>
                                        <span>{{ $newsRead }}</span>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>

            <!-- Donasi Section -->
            <section id="donasi" class="relative w-full overflow-hidden bg-brand-green py-16 md:py-24 px-6 sm:px-12 md:px-24">
                <div class="absolute inset-0 bg-cover bg-center opacity-10" style="background-image: url('{{ asset('assets/images/backgrounds/call-to-action-bg.jpg') }}');"></div>
                <div class="relative max-w-6xl mx-auto flex flex-col gap-16 z-10">
                    <div class="flex flex-col items-center text-center gap-4 w-full">
                        <h2 class="text-4xl sm:text-6xl md:text-7xl font-heading font-extrabold uppercase leading-tight tracking-[0.02em] text-brand-cream">
                            Gerakan Ini<br>
                            Butuh Kamu
                        </h2>
                        <div class="mx-auto h-2 w-32 bg-brand-orange"></div>
                        <p class="mx-auto max-w-[768px] text-base md:text-xl leading-relaxed text-brand-cream font-sans">Perubahan tidak terjadi dengan sendirinya. Setiap gerakan dimulai dari keberanian untuk bertindak. Bergabunglah dengan ribuan aktivis lain yang memperjuangkan keadilan ekologis.</p>
                        <div class="text-xs md:text-sm font-bold uppercase tracking-[0.07em] text-brand-green-light font-sans">Wujudkan Keadilan Hak Atas Lingkungan Hidup Antar Generasi</div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 w-full">
                        <!-- Card Relawan -->
                        <section class="flex flex-col justify-between border-4 border-brand-cream bg-brand-dark p-6 md:p-8 text-brand-cream shadow-[8px_8px_0px_0px_#1D1D1D]">
                            <div class="flex flex-col gap-6">
                                <div class="flex items-center gap-4">
                                    <div class="flex h-14 w-14 items-center justify-center bg-brand-green p-3 flex-shrink-0">
                                        <img src="{{ $iqon('Icon-19.svg') }}" alt="Relawan" class="h-8 w-8 object-contain">
                                    </div>
                                    <h3 class="text-2xl md:text-3xl font-heading font-bold uppercase leading-none tracking-[0.05em]">Gabung #SahabatWALHI</h3>
                                </div>
                                <p class="text-sm md:text-base leading-relaxed font-sans">Bergabunglah dalam barisan pejuang lingkungan hidup untuk mengawal kelestarian alam dan membela hak-hak ruang hidup masyarakat Jawa Barat.</p>
                                <ul class="flex flex-col gap-2 text-sm md:text-base font-semibold font-sans">
                                    <li>▪ Ikut Pendidikan <em>Green Student Movement</em> (GSM)</li>
                                    <li>▪ Bergabung bersama lembaga anggota di berbagai daerah</li>
                                    <li>▪ Terlibat dalam Kampanye bersama</li>
                                </ul>
                            </div>
                            <a href="https://wa.me/6282119821159?text=Halo%20WALHI%20Jawa%20Barat,%20saya%20ingin%20bergabung%20dengan%20%23SahabatWALHI" target="_blank" class="mt-8 flex h-[60px] items-center justify-center gap-2 bg-brand-green hover:bg-brand-green-light transition-colors px-6 text-[16px] font-bold uppercase tracking-[0.04em] text-brand-cream">
                                <img src="{{ $iqon('Icon-20.svg') }}" alt="Relawan" class="h-5 w-5 object-contain">
                                <span>Gabung #SahabatWALHI</span>
                            </a>
                        </section>

                        <!-- Card Dukung Gerakan -->
                        <section class="flex flex-col justify-between border-4 border-brand-cream bg-brand-cream p-6 md:p-8 text-brand-dark shadow-[8px_8px_0px_0px_#256D4A]">
                            <div class="flex flex-col gap-6">
                                <div class="flex items-center gap-4">
                                    <div class="flex h-14 w-14 items-center justify-center bg-brand-orange p-3 flex-shrink-0">
                                        <img src="{{ $iqon('Icon-21.svg') }}" alt="Dukungan" class="h-8 w-8 object-contain">
                                    </div>
                                    <h3 class="text-2xl md:text-3xl font-heading font-bold uppercase leading-none tracking-[0.05em] text-brand-dark">Dukung Gerakan</h3>
                                </div>
                                <p class="text-sm md:text-base leading-relaxed font-sans">Setiap kontribusi membantu mendanai proses pendampingan masyarakat, kampanye publik, advokasi kebijakan, edukasi lingkungan, serta operasional organisasi. Kami tidak menerima setiap pendanaan dari lembaga pemerintah, serta bersikap independen.</p>
                                <div class="flex flex-col gap-2 border-2 border-brand-dark bg-brand-cream p-4">
                                    <div class="text-[12px] font-bold uppercase tracking-[0.06em] text-brand-green">Donasi Digunakan Untuk:</div>
                                    <ul class="flex flex-col gap-1 text-xs md:text-sm font-semibold font-sans">
                                        <li>▪ Pendampingan Hak atas lingkungan Hidup Masyarakat</li>
                                        <li>▪ Advokasi Kebijakan Lingkungan</li>
                                        <li>▪ Kampanye publik, dan lainnya</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="mt-8 flex flex-col gap-3">
                                <a href="https://wa.me/6282119821159?text=Halo%20Tim%20WALHI%20Jawa%20Barat,%20saya%20ingin%20berdonasi%20untuk%20mendukung%20advokasi%20lingkungan%20hidup%20dan%20keadilan%20ekologis%20di%20Jawa%20Barat.%20Mohon%20informasi%20rekening%20resmi%20atau%20QRIS.%20Terima%20kasih!%20🙏🌿" target="_blank" class="flex h-[60px] items-center justify-center gap-2 bg-brand-orange hover:bg-brand-orange/80 transition-colors px-6 text-[16px] font-bold uppercase tracking-[0.04em] text-brand-cream">
                                    <img src="{{ $iqon('Icon-22.svg') }}" alt="Donasi" class="h-5 w-5 object-contain">
                                    <span>Donasi via WhatsApp</span>
                                </a>
                                <a href="{{ route('donasi') }}" class="text-center text-xs font-bold uppercase tracking-wider text-brand-green hover:underline">
                                    Info Detail Donasi &amp; Transparansi &rarr;
                                </a>
                            </div>
                        </section>
                    </div>

                    <!-- Kontak & Form Pengaduan Kasus -->
                    <div id="pengaduan" class="w-full flex flex-col gap-8 border-4 border-brand-cream bg-brand-dark p-6 md:p-8 text-brand-cream shadow-[8px_8px_0px_0px_#1D1D1D] mt-8">
                        <div class="flex flex-col gap-4 text-center max-w-3xl mx-auto">
                            <div class="inline-block mx-auto bg-brand-orange text-brand-cream px-4 py-1 text-xs font-bold uppercase tracking-wider">Kanal Pengaduan & Kontak</div>
                            <h3 class="text-2xl md:text-4xl font-heading font-extrabold uppercase tracking-[0.05em]">Ingin Menyampaikan Aduan?</h3>
                            <p class="text-sm md:text-base leading-relaxed font-sans text-brand-cream/90">Punya pertanyaan? Ingin menyampaikan aduan atau laporan kasus kerusakan lingkungan? Atau sekadar berdiskusi tentang isu lingkungan hidup di Jawa Barat? Kontak kami langsung atau gunakan form pengaduan online.</p>
                            
                            <!-- Form Pengaduan Online Button -->
                            <div class="flex justify-center mt-2">
                                <a href="https://wa.me/6282119821159?text=Halo%20WALHI%20Jawa%20Barat,%20saya%20ingin%20menyampaikan%20aduan%20kasus%20lingkungan" target="_blank" class="inline-flex items-center gap-3 bg-[#D95C3F] hover:bg-[#c44e32] text-white px-8 py-4 font-bold uppercase tracking-wider text-sm md:text-base border-2 border-brand-cream shadow-[4px_4px_0px_0px_#F4F1EA] transition-all">
                                    <span>📢 Sampaikan Aduan (Form / Chat Langsung)</span>
                                </a>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 w-full max-w-5xl mx-auto mt-4">
                            <!-- Email -->
                            <div class="flex items-center gap-4 border-2 border-brand-green bg-brand-green p-5 shadow-[4px_4px_0px_0px_#1D1D1D]">
                                <div class="flex h-10 w-10 items-center justify-center bg-brand-dark p-2 flex-shrink-0">
                                    <img src="{{ $iqon('Icon-23.svg') }}" alt="Email" class="h-6 w-6 object-contain">
                                </div>
                                <div class="text-left overflow-hidden">
                                    <div class="text-[12px] font-bold uppercase tracking-[0.06em] text-brand-green-light">Email</div>
                                    <div class="text-sm font-semibold text-brand-cream font-sans truncate">walhijabar@gmail.com</div>
                                </div>
                            </div>
                            <!-- WhatsApp -->
                            <div class="flex items-center gap-4 border-2 border-brand-green bg-brand-green p-5 shadow-[4px_4px_0px_0px_#1D1D1D]">
                                <div class="flex h-10 w-10 items-center justify-center bg-brand-dark p-2 flex-shrink-0">
                                    <img src="{{ $iqon('Icon-24.svg') }}" alt="WhatsApp" class="h-6 w-6 object-contain">
                                </div>
                                <div class="text-left overflow-hidden">
                                    <div class="text-[12px] font-bold uppercase tracking-[0.06em] text-brand-green-light">WhatsApp</div>
                                    <div class="text-sm font-semibold text-brand-cream font-sans truncate">+62-82-1982-1159</div>
                                </div>
                            </div>
                            <!-- X (Twitter) -->
                            <div class="flex items-center gap-4 border-2 border-brand-green bg-brand-green p-5 shadow-[4px_4px_0px_0px_#1D1D1D]">
                                <div class="flex h-10 w-10 items-center justify-center bg-brand-dark p-2 flex-shrink-0 text-white">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                                </div>
                                <div class="text-left overflow-hidden">
                                    <div class="text-[12px] font-bold uppercase tracking-[0.06em] text-brand-green-light">X / Twitter</div>
                                    <div class="text-sm font-semibold text-brand-cream font-sans truncate">@walhijabar</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            @include('partials.site-footer')
        </main>
    </body>
</html>