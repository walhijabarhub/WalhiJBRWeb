<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @include('partials.seo-meta', ['title' => 'Profil & Sejarah - WALHI Jawa Barat'])

        <!-- Google Fonts & Local Fonts -->
        @include('partials.head-assets')
        
        <style>
            [x-cloak] { display: none !important; }
        </style>

    </head>
    <body class="bg-[#F4F1EA] text-[#1D1D1D] font-sans antialiased overflow-x-clip" style="overflow-x: clip;">
        @include('partials.site-header')

        <!-- Hero Section -->
        <section class="bg-[#1D1D1D] border-b-4 border-[#256D4A] py-16 px-4 md:px-8 lg:px-16">
            <div class="max-w-6xl mx-auto">
                <nav class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-[#F4F1EA] mb-6">
                    <a href="{{ route('home') }}" class="hover:text-[#5C8D59]">Beranda</a>
                    <span class="text-[#256D4A]">/</span>
                    <span class="text-[#5C8D59]">Tentang Kami</span>
                </nav>
                
                <h1 class="text-5xl md:text-7xl lg:text-8xl font-heading font-extrabold text-[#F4F1EA] uppercase tracking-wider mb-4">
                    PROFIL & SEJARAH
                </h1>
                <p class="text-[#5C8D59] text-lg md:text-xl font-semibold max-w-2xl mb-6 font-sans">
                    Wahana Lingkungan Hidup Indonesia — Jawa Barat
                </p>
                <div class="w-32 h-2 bg-[#D95C3F]"></div>
            </div>
        </section>

        <!-- Main Content Area -->
        <main class="max-w-6xl mx-auto py-12 px-4 md:px-8 lg:px-16">
            
            <!-- Navigation Tabs (Neo-Brutalist Style) -->
            <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 justify-center mb-12">
                <button type="button" 
                        data-on-click="setTentangTab" data-args='["profil"]'
                        id="tab-btn-profil" 
                        style="background-color: #256D4A; color: #F4F1EA;"
                        class="tab-btn px-6 py-3 border-4 border-[#1D1D1D] font-heading font-bold text-base md:text-lg uppercase tracking-wider transition-all shadow-[4px_4px_0px_0px_#1D1D1D] active:translate-x-[2px] active:translate-y-[2px] cursor-pointer outline-none select-none">
                    Profil & Anggota
                </button>
                <button type="button" 
                        data-on-click="setTentangTab" data-args='["visi-misi"]'
                        id="tab-btn-visi-misi" 
                        style="background-color: #FFFFFF; color: #1D1D1D;"
                        class="tab-btn px-6 py-3 border-4 border-[#1D1D1D] font-heading font-bold text-base md:text-lg uppercase tracking-wider transition-all shadow-[4px_4px_0px_0px_#1D1D1D] hover:bg-[#F4F1EA] active:translate-x-[2px] active:translate-y-[2px] cursor-pointer outline-none select-none">
                    Visi, Misi & Nilai
                </button>
                <button type="button" 
                        data-on-click="setTentangTab" data-args='["pengurus"]'
                        id="tab-btn-pengurus" 
                        style="background-color: #FFFFFF; color: #1D1D1D;"
                        class="tab-btn px-6 py-3 border-4 border-[#1D1D1D] font-heading font-bold text-base md:text-lg uppercase tracking-wider transition-all shadow-[4px_4px_0px_0px_#1D1D1D] hover:bg-[#F4F1EA] active:translate-x-[2px] active:translate-y-[2px] cursor-pointer outline-none select-none">
                    Kepengurusan & Sejarah
                </button>
            </div>

            <!-- TAB 1: PROFIL & ANGGOTA -->
            <div id="tab-content-profil" class="tentang-tab-panel flex flex-col gap-12" style="display: flex;">
                <!-- Tentang WALHI -->
                <section class="bg-white border-4 border-[#1D1D1D] shadow-[8px_8px_0px_0px_#1D1D1D] p-8 md:p-12">
                    <div class="flex items-center bg-[#256D4A] text-white px-4 py-1.5 text-xs font-bold uppercase tracking-[0.06em] w-fit mb-6 font-sans">
                        Tentang WALHI Jawa Barat
                    </div>
                    <h2 class="text-3xl md:text-4xl font-heading font-extrabold text-[#1D1D1D] uppercase tracking-wide mb-6">
                        Simpul Gerakan Lingkungan Hidup Independen Sejak 1980
                    </h2>
                    <div class="flex flex-col gap-4 text-base md:text-lg leading-relaxed text-[#1D1D1D]/90 font-sans">
                        <p>
                            <strong>WALHI Jawa Barat adalah</strong> simpul gerakan lingkungan hidup independen yang berjuang sejak tahun 1980 untuk menegakkan kedaulatan rakyat atas sumber-sumber kehidupan. Kami mendampingi komunitas terdampak, mengadvokasi kebijakan yang adil, serta mendorong pemulihan ekosistem di Jawa Barat lewat prinsip keadilan ekologis dan kemanusiaan.
                        </p>
                        <p>
                            Kami adalah bagian dari jaringan WALHI Nasional dan Friends of the Earth (FoE) yang berfokus di lingkup daerah Jawa Barat. Tujuan kami adalah <strong>mendorong terwujudnya pengakuan atas lingkungan hidup dan dilindungi serta dipenuhinya hak asasi manusia sebagai bentuk dari tanggung jawab negara atas pemenuhan sumber-sumber kehidupan rakyat.</strong>
                        </p>
                    </div>
                </section>

                <!-- Latar Belakang -->
                <section class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    <div class="lg:col-span-7 bg-[#1D1D1D] text-[#F4F1EA] border-4 border-[#1D1D1D] shadow-[8px_8px_0px_0px_#256D4A] p-8 md:p-10">
                        <h3 class="font-heading font-bold text-2xl md:text-3xl uppercase text-[#5C8D59] mb-6">Latar Belakang</h3>
                        <div class="space-y-4 text-sm md:text-base leading-relaxed text-[#F4F1EA]/90 font-sans">
                            <p>
                                WALHI Jawa Barat sadar kecenderungan kerusakan lingkungan hidup dan ekosistem semakin masif dan kompleks baik di pedesaan maupun perkotaan. Memburuknya kondisi lingkungan hidup secara terbuka diakui mempengaruhi dinamika sosial politik dan sosial ekonomi masyarakat baik di tingkat komunitas, regional, maupun nasional.
                            </p>
                            <p>
                                Pada gilirannya krisis lingkungan hidup secara langsung mengancam kenyamanan, keselamatan dan meningkatkan kerentanan kehidupan setiap warga negara. Kerusakan lingkungan hidup telah hadir di rumah-rumah kita, seperti kelangkaan air bersih, pencemaran air dan udara, banjir dan kekeringan, serta energi yang semakin mahal. Siapa yang bertanggung jawab atas kerusakan lingkungan hidup kian sulit dipastikan karena penyebabnya sendiri saling bertautan baik antarsektor, antaraktor, antarinstitusi, antarwilayah dan bahkan antarnegara.
                            </p>
                        </div>
                    </div>

                    <div class="lg:col-span-5 bg-[#D95C3F] text-white border-4 border-[#1D1D1D] shadow-[8px_8px_0px_0px_#1D1D1D] p-8 md:p-10 flex flex-col justify-between">
                        <div>
                            <h3 class="font-heading font-bold text-2xl md:text-3xl uppercase text-white mb-6">Gerakan Rakyat</h3>
                            <p class="text-sm md:text-base leading-relaxed mb-6">
                                Untuk menjamin keberlanjutan kehidupan generasi mendatang dibutuhkan gerakan sosial yang kuat dan meluas. Generasi mendatang berhak atas lingkungan hidup yang baik dan sehat. Untuk itu generasi sekarang bertanggungjawab mempertahankan dan meningkatkan kualitas lingkungan yang lebih baik.
                            </p>
                        </div>
                        <div class="border-t border-white/30 pt-4 text-xs font-semibold leading-relaxed">
                            Kampanye penyelamatan lingkungan WALHI Jawa Barat dibantu oleh <strong>SAHABAT WALHI Jawa Barat</strong>, simpul sayap gerakan yang terintegrasi secara langsung dalam kerja advokasi di tapak.
                        </div>
                    </div>
                </section>

                <!-- Jangkauan Wilayah & 29 Organisasi Anggota -->
                <section class="bg-white border-4 border-[#1D1D1D] shadow-[8px_8px_0px_0px_#1D1D1D] p-8 md:p-12">
                    <div class="mb-8">
                        <div class="bg-[#8B6B4A] text-white px-4 py-1.5 text-xs font-bold uppercase tracking-[0.06em] w-fit mb-4 font-sans">
                            Jaringan Organisasi
                        </div>
                        <h2 class="text-3xl font-heading font-extrabold text-[#1D1D1D] uppercase tracking-wide">
                            Hadir di 13 Kabupaten/Kota dengan 29 Lembaga Anggota
                        </h2>
                        <p class="text-base text-[#1D1D1D]/80 mt-2 font-sans">
                            Anggota-anggota resmi WALHI Jawa Barat yang secara aktif mengorganisir gerakan lingkungan di tingkat lokal:
                        </p>
                    </div>

                    @php
                        $members = [
                            ['name' => 'YPBB', 'city' => 'Kota Bandung'],
                            ['name' => 'Argawilis ISBI', 'city' => 'Kota Bandung'],
                            ['name' => 'FK3I Jawa Barat', 'city' => 'Kota Bandung'],
                            ['name' => 'Mapenta Unisba', 'city' => 'Kota Bandung'],
                            ['name' => 'HMTL Unpas', 'city' => 'Kota Bandung'],
                            ['name' => 'Pakuan', 'city' => 'Kota Bandung'],
                            ['name' => 'Poklan', 'city' => 'Kota Bandung'],
                            ['name' => 'LPTT', 'city' => 'Kota Bandung'],
                            ['name' => 'Katurnagari', 'city' => 'Kota Bandung'],
                            ['name' => 'PPMK-SA', 'city' => 'Kota Bandung'],
                            ['name' => 'Swadaya Muda', 'city' => 'Kota Bandung'],
                            ['name' => 'LKrapin', 'city' => 'Kota Bandung'],
                            ['name' => 'PSDK', 'city' => 'Kab. Bandung'],
                            ['name' => 'Lencana', 'city' => 'Kab. Bandung'],
                            ['name' => 'MPSA', 'city' => 'Kab. Bandung'],
                            ['name' => 'Siklus', 'city' => 'Kab. Indramayu'],
                            ['name' => 'Paguyuban Bale Rahayat', 'city' => 'Kab. Banjar'],
                            ['name' => 'Rekapala', 'city' => 'Kab. Garut'],
                            ['name' => 'SPP', 'city' => 'Kab. Garut'],
                            ['name' => 'FPPMG', 'city' => 'Kab. Garut'],
                            ['name' => 'UKL Fapet Unpad', 'city' => 'Kab. Sumedang'],
                            ['name' => 'Himikan Unpad', 'city' => 'Kab. Sumedang'],
                            ['name' => 'Palamus', 'city' => 'Kab. Subang'],
                            ['name' => 'Forum Akar', 'city' => 'Kab. Banjar'],
                            ['name' => 'FMPL', 'city' => 'Kab. Bogor'],
                            ['name' => 'Farmaci', 'city' => 'Kab. Ciamis'],
                            ['name' => 'Mahakupala', 'city' => 'Kab. Kuningan'],
                            ['name' => 'Blue Ocean', 'city' => 'Kab. Cirebon'],
                            ['name' => 'FMPR', 'city' => 'Kab. Tasikmalaya'],
                        ];
                    @endphp

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                        @foreach($members as $member)
                            <div class="p-4 bg-[#F4F1EA] border-2 border-[#1D1D1D] shadow-[4px_4px_0px_0px_#1D1D1D] hover:translate-x-[-2px] hover:translate-y-[-2px] hover:shadow-[6px_6px_0px_0px_#256D4A] transition-all">
                                <div class="font-heading text-lg text-[#1D1D1D] uppercase">{{ $member['name'] }}</div>
                                <div class="text-xs font-semibold text-[#256D4A] uppercase mt-1 tracking-wider">{{ $member['city'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>

            <!-- TAB 2: VISI, MISI & NILAI -->
            <div id="tab-content-visi-misi" class="tentang-tab-panel flex flex-col gap-12" style="display: none;">
                <!-- Visi & Motto -->
                <section class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    <!-- Visi -->
                    <div class="lg:col-span-7 bg-[#256D4A] text-[#F4F1EA] border-4 border-[#1D1D1D] shadow-[8px_8px_0px_0px_#1D1D1D] p-8 md:p-12 relative flex flex-col justify-center">
                        <span class="absolute -top-4 left-6 bg-[#1D1D1D] text-[#5C8D59] font-bold text-xs uppercase tracking-widest px-4 py-2 border-2 border-[#F4F1EA] font-sans">
                            Visi Kami
                        </span>
                        <h2 class="text-2xl sm:text-3xl md:text-4xl font-heading font-bold uppercase tracking-wide leading-relaxed mt-4">
                            “Terwujudnya pengakuan atas lingkungan hidup dan dilindungi serta dipenuhinya hak asasi manusia sebagai bentuk dari tanggung jawab negara atas pemenuhan sumber-sumber kehidupan rakyat”
                        </h2>
                    </div>

                    <!-- Motto -->
                    <div class="lg:col-span-5 bg-[#D95C3F] text-white border-4 border-[#1D1D1D] shadow-[8px_8px_0px_0px_#256D4A] p-8 md:p-12 flex flex-col justify-center text-center">
                        <span class="bg-[#1D1D1D] text-white font-bold text-xs uppercase tracking-widest px-4 py-1.5 w-fit mx-auto mb-4 border border-white font-sans">
                            Motto Perjuangan
                        </span>
                        <h3 class="text-4xl md:text-5xl font-heading font-extrabold uppercase tracking-wider leading-tight">
                            PULIHKAN EKOLOGI JAWA BARAT,
                        </h3>
                        <h3 class="text-3xl md:text-4xl font-heading font-extrabold uppercase tracking-wide text-[#1D1D1D] mt-2">
                            UTAMAKAN KESELAMATAN RAKYAT!
                        </h3>
                    </div>
                </section>

                <!-- Misi -->
                <section class="bg-white border-4 border-[#1D1D1D] shadow-[8px_8px_0px_0px_#1D1D1D] p-8 md:p-12">
                    <div class="text-center mb-10">
                        <span class="inline-block bg-[#256D4A] text-white font-bold text-xs uppercase tracking-widest px-4 py-2 mb-4 font-sans">
                            Misi Organisasi
                        </span>
                        <h3 class="text-3xl md:text-4xl font-heading font-extrabold text-[#1D1D1D] uppercase tracking-wide">
                            5 Misi Transformasi Sosial
                        </h3>
                        <p class="text-sm text-[#1D1D1D]/70 mt-2 max-w-2xl mx-auto font-sans">
                            Misi WALHI Jawa Barat adalah mendorong proses transformasi sosial dengan cara:
                        </p>
                    </div>
                    
                    @php
                        $misiItems = [
                            'Mengembangkan potensi kekuatan dan ketahanan rakyat;',
                            'Mengembalikan mandat negara untuk menegakkan dan melindungi kedaulatan rakyat;',
                            'Mendekonstruksikan tatanan ekonomi kapitalistik global yang menindas dan eksploitatif menuju ke arah ekonomi kerakyatan;',
                            'Membangun alternatif tata ekonomi dunia baru; serta',
                            'Mendesakkan kebijakan pengelolaan sumber-sumber kehidupan rakyat yang adil dan berkelanjutan.'
                        ];
                    @endphp

                    <div class="grid md:grid-cols-2 gap-6">
                        @foreach ($misiItems as $index => $misi)
                            <div class="flex gap-4 p-6 bg-[#F4F1EA] border-2 border-[#1D1D1D] shadow-[4px_4px_0px_0px_#1D1D1D] {{ $loop->last ? 'md:col-span-2' : '' }}">
                                <div class="flex-shrink-0 w-12 h-12 bg-[#1D1D1D] flex justify-center items-center text-[#256D4A] text-2xl font-heading font-bold">
                                    {{ $index + 1 }}
                                </div>
                                <div class="flex-1 text-[#1D1D1D] text-base font-semibold leading-relaxed font-sans flex items-center">
                                    {{ $misi }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>

                <!-- Nilai Perjuangan WALHI -->
                <section class="bg-[#1D1D1D] text-[#F4F1EA] border-4 border-[#1D1D1D] shadow-[8px_8px_0px_0px_#256D4A] p-8 md:p-12">
                    <div class="text-center mb-12">
                        <span class="inline-block bg-[#D95C3F] text-white font-bold text-xs uppercase tracking-widest px-4 py-2 mb-4 font-sans">
                            Nilai-Nilai Perjuangan
                        </span>
                        <h3 class="text-3xl md:text-4xl font-heading font-extrabold text-white uppercase tracking-wide">
                            8 Nilai Pokok Gerakan WALHI
                        </h3>
                        <p class="text-sm text-[#F4F1EA]/70 mt-2 max-w-2xl mx-auto font-sans">
                            Dalam memperjuangkan keadilan ekologis dan kedaulatan rakyat, WALHI Jawa Barat berpegang teguh pada nilai-nilai pokok:
                        </p>
                    </div>

                    @php
                        $nilai = [
                            ['title' => 'Hak Asasi Manusia', 'desc' => 'Menghormati, melindungi, dan memenuhi hak asasi manusia atas lingkungan hidup yang bersih, sehat, dan berkelanjutan.'],
                            ['title' => 'Demokrasi', 'desc' => 'Seluruh rakyat berhak terlibat secara bermakna dalam proses pengambilan keputusan atas tata kelola sumber-sumber kehidupan.'],
                            ['title' => 'Keadilan Gender', 'desc' => 'Menjamin kesetaraan hak, partisipasi, dan kepemimpinan perempuan dalam pengelolaan lingkungan hidup.'],
                            ['title' => 'Keadilan Ekologis', 'desc' => 'Pengelolaan alam yang menghormati daya dukung ekosistem dan keutuhan fungsi keselamatan lingkungan.'],
                            ['title' => 'Keadilan Antara Generasi', 'desc' => 'Menjaga kelestarian alam hari ini agar hak generasi penerus dan anak cucu kita tetap terjamin.'],
                            ['title' => 'Persaudaraan Sosial', 'desc' => 'Solidaritas gotong-royong antar komunitas dan rakyat tertindas dalam mempertahankan ruang hidup.'],
                            ['title' => 'Anti Kekerasan', 'desc' => 'Menolak segala bentuk intimidasi, kriminalisasi, dan kekerasan fisik maupun non-fisik terhadap pejuang lingkungan.'],
                            ['title' => 'Keberagaman', 'desc' => 'Menghargai keragaman hayati, kearifan lokal, dan kemajemukan budaya dalam relasi manusia dengan alam.']
                        ];
                    @endphp

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 text-brand-dark">
                        @foreach ($nilai as $index => $item)
                            <div class="bg-white border-2 border-[#1D1D1D] p-6 shadow-[4px_4px_0px_0px_#D95C3F] hover:translate-x-[-2px] hover:translate-y-[-2px] hover:shadow-[6px_6px_0px_0px_#D95C3F] transition-all flex flex-col justify-between">
                                <div>
                                    <div class="flex items-center gap-3 mb-3">
                                        <div class="w-8 h-8 bg-[#1D1D1D] text-white flex items-center justify-center font-heading text-sm shrink-0">
                                            {{ sprintf("%02d", $index + 1) }}
                                        </div>
                                        <h4 class="font-heading font-bold text-base uppercase tracking-wide leading-tight">{{ $item['title'] }}</h4>
                                    </div>
                                    <p class="text-xs text-[#1D1D1D]/80 leading-relaxed font-sans">
                                        {{ $item['desc'] }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>

                <!-- Program Strategis -->
                <section class="bg-white border-4 border-[#1D1D1D] shadow-[8px_8px_0px_0px_#1D1D1D] p-8 md:p-12">
                    <div class="text-center mb-10">
                        <span class="inline-block bg-[#8B6B4A] text-white font-bold text-xs uppercase tracking-widest px-4 py-2 mb-4">
                            Strategi Perjuangan
                        </span>
                        <h3 class="text-3xl md:text-4xl font-heading text-[#1D1D1D] uppercase tracking-wide">
                            6 Program Strategis Organisasi
                        </h3>
                    </div>

                    @php
                        $programs = [
                            'Mendorong Tata Kelola DAS yang berkelanjutan dan berkeadilan.',
                            'Mendorong Kebijakan untuk menciptakan tata kelola energi yang terdesentralisasi.',
                            'Mendorong energi baru yang terbarukan yang tidak merampas hak ruang hidup.',
                            'Meningkatkan kesadaran publik terkait dampak energi kotor.',
                            'Mengkonsolidasikan posisi organisasi dalam menyikapi kebijakan-kebijakan infrastruktur yang berdampak terhadap alih fungsi lahan (Hutan, Urban, Pesisir Kelautan, Pertanian Pedesaan).',
                            'Mewujudkan WALHI Jawa Barat sebagai lembaga yang mandiri dan kompeten dalam melakukan kerja-kerja advokasi.'
                        ];
                    @endphp

                    <div class="space-y-4">
                        @foreach ($programs as $index => $program)
                            <div class="flex gap-4 p-5 bg-[#F4F1EA] border-2 border-[#1D1D1D] shadow-[4px_4px_0px_0px_#1D1D1D]">
                                <div class="flex-shrink-0 w-8 h-8 bg-[#256D4A] text-white flex items-center justify-center font-bold text-sm">
                                    {{ $index + 1 }}
                                </div>
                                <p class="text-[#1D1D1D] text-sm md:text-base font-semibold leading-relaxed">
                                    {{ $program }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>

            <!-- TAB 3: KEPENGURUSAN & SEJARAH -->
            <div id="tab-content-pengurus" class="tentang-tab-panel flex flex-col gap-12" style="display: none;">
                <!-- Sejarah Capaian -->
                <section class="bg-white border-4 border-[#1D1D1D] shadow-[8px_8px_0px_0px_#1D1D1D] p-8 md:p-12">
                    <div class="mb-8">
                        <span class="inline-block bg-[#256D4A] text-white font-bold text-xs uppercase tracking-widest px-4 py-2 mb-4">
                            Sejarah Gerakan
                        </span>
                        <h3 class="text-3xl font-heading text-[#1D1D1D] uppercase tracking-wide">
                            Perjalanan Gerakan Sejak 15 Oktober 1980
                        </h3>
                        <p class="text-base text-[#1D1D1D]/80 mt-2">
                            Semenjak dibentuk pada 15 Oktober 1980, WALHI bersama kelompok masyarakat sipil lainnya telah:
                        </p>
                    </div>

                    @php
                        $achievements = [
                            'Menumbuhkan kesadaran lingkungan hidup dan mempromosikan kedaulatan rakyat dalam pengelolaan sumber-sumber kehidupan.',
                            'Memelopori gerakan lingkungan hidup di Indonesia dan menjadi bagian integral dari gerakan lingkungan hidup global.',
                            'Mengangkat masalah dari tingkat rakyat paling bawah sampai ke proses pembuatan kebijakan di tingkat lokal, regional dan nasional.',
                            'Mendukung perjuangan puluhan kelompok masyarakat untuk menegaskan hak mereka atas lingkungan dan pengelolaan sumber-sumber kehidupan.',
                            'Menjadi narasumber krusial untuk persoalan lingkungan hidup di Indonesia bagi media, industri dan lembaga pemerintah.'
                        ];
                    @endphp

                    <div class="space-y-4">
                        @foreach ($achievements as $ach)
                            <div class="flex gap-4 items-start p-4 bg-[#F4F1EA] border border-[#1D1D1D]">
                                <div class="w-6 h-6 bg-[#D95C3F] text-white flex items-center justify-center shrink-0 mt-0.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                </div>
                                <p class="text-sm md:text-base text-[#1D1D1D] leading-relaxed">{{ $ach }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>

                <!-- Struktur Kepengurusan -->
                <section class="bg-white border-4 border-[#1D1D1D] shadow-[8px_8px_0px_0px_#1D1D1D] p-8 md:p-12">
                    <div class="text-center mb-10">
                        <span class="inline-block bg-[#D95C3F] text-white font-bold text-xs uppercase tracking-widest px-4 py-2 mb-4 font-sans">
                            Struktur Organisasi
                        </span>
                        <h2 class="text-3xl md:text-4xl font-heading font-extrabold text-[#1D1D1D] uppercase tracking-wide">
                            Kepengurusan WALHI Jawa Barat
                        </h2>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                        <!-- Eksekutif Daerah (7 Kolom) -->
                        <div class="lg:col-span-7 bg-[#F4F1EA] border-4 border-[#1D1D1D] p-6 shadow-[4px_4px_0px_0px_#1D1D1D]">
                            <h3 class="font-heading font-bold text-2xl uppercase border-b-2 border-[#1D1D1D] pb-2 mb-6 text-[#256D4A]">
                                1. Eksekutif Daerah
                            </h3>
                            
                            <div class="space-y-4">
                                <!-- Direktur Eksekutif Daerah -->
                                <div class="p-4 bg-white border-2 border-[#1D1D1D] shadow-[3px_3px_0px_0px_#1D1D1D]">
                                    <div class="text-xs uppercase font-bold text-[#D95C3F] tracking-wide font-sans">Direktur Eksekutif Daerah</div>
                                    <div class="font-heading font-bold text-2xl text-[#1D1D1D] mt-1">Wahyudin</div>
                                </div>

                                <!-- Manajer & Staf Grid -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 font-sans">
                                    <!-- Kesekretariatan -->
                                    <div class="p-4 bg-white border border-[#1D1D1D]">
                                        <div class="text-[10px] uppercase font-bold text-[#256D4A] tracking-wider">Manajer Kesekretariatan</div>
                                        <div class="font-bold text-base mt-1 text-[#1D1D1D]">Hari Kristanto</div>
                                    </div>

                                    <!-- Advokasi -->
                                    <div class="p-4 bg-white border border-[#1D1D1D]">
                                        <div class="text-[10px] uppercase font-bold text-[#256D4A] tracking-wider">Manajer Advokasi</div>
                                        <div class="font-bold text-base mt-1 text-[#1D1D1D]">Ajeng Pramudya</div>
                                    </div>

                                    <!-- Kampanye -->
                                    <div class="p-4 bg-white border border-[#1D1D1D]">
                                        <div class="text-[10px] uppercase font-bold text-[#256D4A] tracking-wider">Manajer Kampanye</div>
                                        <div class="font-bold text-base mt-1 text-[#1D1D1D]">Fauqi M</div>
                                    </div>

                                    <!-- Keuangan -->
                                    <div class="p-4 bg-white border border-[#1D1D1D] flex flex-col justify-between">
                                        <div>
                                            <div class="text-[10px] uppercase font-bold text-[#256D4A] tracking-wider">Manajer Keuangan</div>
                                            <div class="font-bold text-base mt-1 text-[#1D1D1D]">Tegar Jiwa Raksa</div>
                                        </div>
                                        <div class="text-xs text-gray-600 mt-2 border-t border-gray-100 pt-1 font-medium">Staf Keuangan: Nisfy Hardiani</div>
                                    </div>

                                    <!-- Pendidikan dan Kaderisasi -->
                                    <div class="p-4 bg-white border border-[#1D1D1D] flex flex-col justify-between">
                                        <div>
                                            <div class="text-[10px] uppercase font-bold text-[#256D4A] tracking-wider">Manajer Pendidikan & Kaderisasi</div>
                                            <div class="font-bold text-base mt-1 text-[#1D1D1D]">Jeffry Rohman</div>
                                        </div>
                                        <div class="text-xs text-gray-600 mt-2 border-t border-gray-100 pt-1 font-medium">Staf: M Ihsan</div>
                                    </div>

                                    <!-- Pengelolaan Sumber Daya dan WKR -->
                                    <div class="p-4 bg-white border border-[#1D1D1D]">
                                        <div class="text-[10px] uppercase font-bold text-[#256D4A] tracking-wider">Staf Pengelolaan Sumber Daya & WKR</div>
                                        <div class="font-bold text-base mt-1 text-[#1D1D1D]">Fariz abiyyu Putra W</div>
                                    </div>

                                    <!-- Desk Disaster -->
                                    <div class="p-4 bg-white border border-[#1D1D1D]">
                                        <div class="text-[10px] uppercase font-bold text-[#256D4A] tracking-wider">Staf Desk Disaster</div>
                                        <div class="font-bold text-base mt-1 text-[#1D1D1D]">Aldi Maulana</div>
                                    </div>

                                    <!-- Staff Perbantuan -->
                                    <div class="p-4 bg-white border border-[#1D1D1D]">
                                        <div class="text-[10px] uppercase font-bold text-[#256D4A] tracking-wider">Staff Perbantuan</div>
                                        <div class="font-bold text-base mt-1 text-[#1D1D1D]">Siti Hannah</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Dewan Daerah (5 Kolom) -->
                        <div class="lg:col-span-5 bg-white border-4 border-[#1D1D1D] p-6 shadow-[4px_4px_0px_0px_#256D4A]">
                            <h3 class="font-heading font-bold text-2xl uppercase border-b-2 border-[#1D1D1D] pb-2 mb-6 text-[#D95C3F]">
                                2. Dewan Daerah
                            </h3>

                            <div class="space-y-4 font-sans">
                                <!-- Ketua -->
                                <div class="p-4 bg-[#F4F1EA] border-2 border-[#1D1D1D] shadow-[3px_3px_0px_0px_#1D1D1D]">
                                    <div class="text-xs uppercase font-bold text-[#256D4A] tracking-wide">Ketua Dewan Daerah</div>
                                    <div class="font-heading font-bold text-2xl text-[#1D1D1D] mt-1">Dedy Kurniawan</div>
                                </div>

                                <!-- Anggota -->
                                <div class="p-4 bg-[#F4F1EA] border border-[#1D1D1D]">
                                    <div class="text-xs uppercase font-bold text-[#1D1D1D]/60 tracking-wider mb-3">Anggota Dewan Daerah</div>
                                    <ul class="space-y-3 font-bold text-base text-[#1D1D1D]">
                                        <li class="flex items-center gap-3">
                                            <span class="w-2.5 h-2.5 bg-[#D95C3F]"></span> Turehan Ashuri
                                        </li>
                                        <li class="flex items-center gap-3">
                                            <span class="w-2.5 h-2.5 bg-[#D95C3F]"></span> Mira
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Kontak Section -->
            <section id="kontak" class="bg-white border-4 border-[#1D1D1D] shadow-[8px_8px_0px_0px_#256D4A] p-8 md:p-12 scroll-mt-24 mt-16">
                <div class="text-center mb-10">
                    <span class="inline-block bg-[#D95C3F] text-[#F4F1EA] font-bold text-xs uppercase tracking-widest px-4 py-2 mb-4 font-sans">
                        Hubungi Kami
                    </span>
                    <h2 class="text-3xl md:text-4xl font-heading font-extrabold text-[#1D1D1D] uppercase tracking-wide">
                        Kantor Eksekutif Daerah WALHI Jabar
                    </h2>
                </div>

                <div class="grid md:grid-cols-2 gap-8">
                    <!-- Info Kontak -->
                    <div class="flex flex-col gap-6">
                        <div class="flex gap-4 items-start">
                            <div class="w-10 h-10 bg-[#1D1D1D] text-[#5C8D59] flex items-center justify-center shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/></svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-[#1D1D1D] text-sm uppercase tracking-wider mb-1">Alamat Kantor</h4>
                                <p class="text-base text-[#1D1D1D]/80 leading-relaxed font-sans">Jl. Simponi No.29, Turangga, Kec. Lengkong, Kota Bandung, Jawa Barat 40264</p>
                            </div>
                        </div>

                        <div class="flex gap-4 items-start">
                            <div class="w-10 h-10 bg-[#1D1D1D] text-[#5C8D59] flex items-center justify-center shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-[#1D1D1D] text-sm uppercase tracking-wider mb-1">E-mail Resmi</h4>
                                <p class="text-base text-[#1D1D1D]/80 font-sans">
                                    <a href="mailto:walhijabar@gmail.com" class="hover:text-[#256D4A] underline font-semibold">walhijabar@gmail.com</a>
                                </p>
                            </div>
                        </div>

                        <div class="flex gap-4 items-start">
                            <div class="w-10 h-10 bg-[#1D1D1D] text-[#5C8D59] flex items-center justify-center shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-[#1D1D1D] text-sm uppercase tracking-wider mb-1">WhatsApp & Telepon</h4>
                                <p class="text-base text-[#1D1D1D]/80 font-sans">
                                    <a href="https://wa.me/6282119821159" target="_blank" class="hover:text-[#256D4A] underline font-semibold">+62-82-1982-1159</a>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Peta / Social Media -->
                    <div class="bg-[#F4F1EA] border-4 border-[#1D1D1D] p-6 flex flex-col justify-between gap-6">
                        <div>
                            <h3 class="font-heading font-bold text-xl md:text-2xl text-[#1D1D1D] uppercase tracking-wide mb-4">Media Sosial Resmi</h3>
                            <div class="grid grid-cols-2 gap-4 text-sm font-semibold">
                                <a href="https://instagram.com/walhi.jabar" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2 text-[#1D1D1D] hover:text-[#256D4A] transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#D95C3F]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/></svg>
                                    <span>Instagram</span>
                                </a>
                                <a href="https://www.youtube.com/@walhijabar" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2 text-[#1D1D1D] hover:text-[#256D4A] transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#D95C3F]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.5 17a24.12 24.12 0 0 1 0-10 2 2 0 0 1 1.4-1.4 49.56 49.56 0 0 1 16.2 0A2 2 0 0 1 21.5 7a24.12 24.12 0 0 1 0 10 2 2 0 0 1-1.4 1.4 49.55 49.55 0 0 1-16.2 0A2 2 0 0 1 2.5 17z"/><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"/></svg>
                                    <span>YouTube</span>
                                </a>
                                <a href="https://facebook.com/walhi.jabar" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2 text-[#1D1D1D] hover:text-[#256D4A] transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#256D4A]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                                    <span>Facebook</span>
                                </a>
                                <a href="https://x.com/walhijabar" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2 text-[#1D1D1D] hover:text-[#256D4A] transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                                    <span>@walhijabar</span>
                                </a>
                            </div>
                        </div>

                        <div class="border-t border-[#1D1D1D]/20 pt-4">
                            <p class="text-xs text-[#666] leading-relaxed">
                                Silakan hubungi kami untuk laporan pelanggaran lingkungan, advokasi bersama, atau dukungan donasi bagi gerakan lingkungan hidup Jawa Barat.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

        </main>

        @include('partials.site-footer')

        <!-- Tab Switcher Script -->
        <script nonce="{{ Vite::cspNonce() }}">
            function setTentangTab(tabId) {
                var panels = {
                    'profil': document.getElementById('tab-content-profil'),
                    'visi-misi': document.getElementById('tab-content-visi-misi'),
                    'pengurus': document.getElementById('tab-content-pengurus')
                };
                var buttons = {
                    'profil': document.getElementById('tab-btn-profil'),
                    'visi-misi': document.getElementById('tab-btn-visi-misi'),
                    'pengurus': document.getElementById('tab-btn-pengurus')
                };

                // Hide all panels & reset buttons
                for (var key in panels) {
                    if (panels[key]) {
                        panels[key].style.display = 'none';
                    }
                    if (buttons[key]) {
                        buttons[key].style.backgroundColor = '#FFFFFF';
                        buttons[key].style.color = '#1D1D1D';
                    }
                }

                // Show selected panel & activate button
                if (panels[tabId]) {
                    panels[tabId].style.display = 'flex';
                }
                if (buttons[tabId]) {
                    buttons[tabId].style.backgroundColor = '#256D4A';
                    buttons[tabId].style.color = '#F4F1EA';
                }

                if (window.history.pushState) {
                    window.history.pushState(null, null, '#' + tabId);
                }
            }

            // Global alias for compatibility
            window.switchTab = setTentangTab;
            window.manualSwitchTab = setTentangTab;

            document.addEventListener('DOMContentLoaded', function() {
                var hash = window.location.hash.replace('#', '');
                if (hash === 'visi-misi' || hash === 'pengurus' || hash === 'profil') {
                    setTentangTab(hash);
                } else if (hash === 'kontak') {
                    setTentangTab('profil');
                    var el = document.getElementById('kontak');
                    if (el) {
                        setTimeout(function() { el.scrollIntoView({ behavior: 'smooth' }); }, 150);
                    }
                } else {
                    setTentangTab('profil');
                }
            });

            window.addEventListener('hashchange', function() {
                var hash = window.location.hash.replace('#', '');
                if (hash === 'visi-misi' || hash === 'pengurus' || hash === 'profil') {
                    setTentangTab(hash);
                } else if (hash === 'kontak') {
                    var el = document.getElementById('kontak');
                    if (el) el.scrollIntoView({ behavior: 'smooth' });
                }
            });
        </script>
    </body>
</html>
