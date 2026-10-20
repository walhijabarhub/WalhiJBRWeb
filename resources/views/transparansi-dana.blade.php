<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @include('partials.seo-meta', ['title' => 'Transparansi Dana & Akuntabilitas - WALHI Jawa Barat'])

        @include('partials.head-assets', ['lucide' => true])

    </head>
    <body style="width: 100%; background: #F4F1EA; margin: 0; overflow-x: clip; color: #1D1D1D; font-family: Montserrat, sans-serif;">
        <div style="position: relative; width: 100%; overflow-x: clip; background: #F4F1EA;">
            @include('partials.site-header')

            <main style="display: flex; flex-direction: column; align-items: stretch;">
                
                <!-- Hero Section -->
                <section style="background: #1D1D1D; border-bottom: 4px #5C8D59 solid; color: #F4F1EA;" class="py-12 md:py-16">
                    <div class="w-full max-w-5xl mx-auto px-4 sm:px-8">
                        <div style="display: flex; flex-direction: column; gap: 24px; max-width: 860px; width: 100%;">
                            <!-- Breadcrumbs -->
                            <div style="display: flex; align-items: center; gap: 8px; font-size: 12px; font-family: Montserrat, sans-serif; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px;">
                                <a href="{{ route('home') }}" style="color: #F4F1EA; text-decoration: none; opacity: 0.8;">Beranda</a>
                                <span style="color: #5C8D59; font-weight: 400; font-size: 16px;">/</span>
                                <span style="color: #5C8D59;">Transparansi Dana</span>
                            </div>
                            
                            <h1 style="margin: 0; color: #F4F1EA; font-size: clamp(34px, 5vw, 60px); font-family: Aspekta, sans-serif; font-weight: 800; line-height: 1.05; letter-spacing: 1.5px; text-transform: uppercase;">
                                TRANSPARANSI DANA
                            </h1>
                            <div style="width: 128px; height: 8px; background: #D95C3F;"></div>
                            <p style="margin: 0; color: #5C8D59; font-size: 20px; line-height: 32px; font-family: Montserrat, sans-serif;">
                                Prinsip Independensi Finansial &amp; Akuntabilitas Publik
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Content Section -->
                <section style="background: #F4F1EA; color: #1D1D1D; border-bottom: 4px #1D1D1D solid;" class="py-16 md:py-20">
                    <div class="w-full max-w-4xl mx-auto px-4 sm:px-8">
                        <article class="bg-white border-4 border-[#1D1D1D] shadow-[8px_8px_0px_0px_#1D1D1D] p-8 md:p-12 flex flex-col gap-8 font-sans leading-relaxed text-[#1D1D1D]">
                            
                            <div style="border-bottom: 2px solid #1D1D1D; padding-bottom: 16px;">
                                <span style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: #8B6B4A; letter-spacing: 0.5px;">Komitmen Etik Finansial Gerakan</span>
                                <p class="mt-2 text-base md:text-lg font-medium text-gray-700">
                                    Sebagai organisasi masyarakat sipil independen terdepan di Jawa Barat, WALHI memegang teguh prinsip etis bahwa sumber pendanaan tidak boleh mengorbankan independensi moral dan daya kritis pembelaan lingkungan hidup.
                                </p>
                            </div>

                            <!-- 3 Prinsip Pokok Finansial -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="bg-[#F4F1EA] border-2 border-[#1D1D1D] p-5 flex flex-col gap-2">
                                    <div class="font-bold text-[#D95C3F] text-xs uppercase tracking-wider">Prinsip 1</div>
                                    <h3 class="font-heading font-extrabold text-lg text-[#1D1D1D] uppercase">Bebas Korporasi Perusak</h3>
                                    <p class="text-xs text-gray-600 leading-normal">Menolak mutlak sumbangan dari industri tambang, sawit skala besar, PLTU fosil, dan korporasi perusak ekosistem.</p>
                                </div>
                                <div class="bg-[#F4F1EA] border-2 border-[#1D1D1D] p-5 flex flex-col gap-2">
                                    <div class="font-bold text-[#256D4A] text-xs uppercase tracking-wider">Prinsip 2</div>
                                    <h3 class="font-heading font-extrabold text-lg text-[#1D1D1D] uppercase">Kemandirian Gerakan</h3>
                                    <p class="text-xs text-gray-600 leading-normal">Menjaga jarak independen dari anggaran pemerintah daerah yang dapat mengekang kebebasan bersuara rakyat.</p>
                                </div>
                                <div class="bg-[#F4F1EA] border-2 border-[#1D1D1D] p-5 flex flex-col gap-2">
                                    <div class="font-bold text-[#8B6B4A] text-xs uppercase tracking-wider">Prinsip 3</div>
                                    <h3 class="font-heading font-extrabold text-lg text-[#1D1D1D] uppercase">Audit Berkala Terbuka</h3>
                                    <p class="text-xs text-gray-600 leading-normal">Pengelolaan keuangan diaudit berkala oleh Kantor Akuntan Publik (KAP) independen dan dipublikasikan tahunan.</p>
                                </div>
                            </div>

                            <div class="flex flex-col gap-4">
                                <h2 style="font-family: Aspekta, sans-serif; font-weight: 800; font-size: 22px; text-transform: uppercase; color: #256D4A;">
                                    Alokasi Dukungan &amp; Donasi Publik
                                </h2>
                                <p>Seluruh dana publik yang dihimpun melalui donasi masyarakat disalurkan untuk:</p>
                                <ul class="list-disc list-inside flex flex-col gap-2 pl-2">
                                    <li><strong>Pendampingan Advokasi Warga:</strong> Biaya operasional tim hukum, pengajuan gugatan PTUN, dan investigasi lapangan kasus tambang serta limbah industri.</li>
                                    <li><strong>Penyelamatan &amp; Mitigasi Bencana:</strong> Posko tanggap darurat dan distribusi bantuan logistik warga korban bencana ekologis (banjir bandang, longsor Citarum/KBU).</li>
                                    <li><strong>Pendidikan &amp; Sekolah Lapang Rakyat:</strong> Pelatihan kader hukum lingkungan dan penguatan ketahanan pangan berbasis komunitas.</li>
                                    <li><strong>Kampanye Publik &amp; Penerbitan:</strong> Biaya riset, pengujian laboratorium sampel air sungai/tanah, serta penerbitan laporan investigasi.</li>
                                </ul>
                            </div>

                            <div class="flex flex-col gap-4">
                                <h2 style="font-family: Aspekta, sans-serif; font-weight: 800; font-size: 22px; text-transform: uppercase; color: #256D4A;">
                                    Akses Dokumen Laporan Tahunan
                                </h2>
                                <p>
                                    Capaian kerja dan rekapitulasi audit transparansi kami tuangkan secara lengkap pada setiap edisi Laporan Tahunan resmi. Anda dapat meninjau dan mengunduh dokumen laporan di halaman publikasi:
                                </p>
                                <div>
                                    <a href="{{ route('laporan-tahunan') }}" style="display: inline-flex; align-items: center; gap: 8px; background: #256D4A; color: white; padding: 14px 28px; font-weight: 700; font-size: 14px; text-transform: uppercase; text-decoration: none; border: 2px solid #1D1D1D; box-shadow: 4px 4px 0px 0px #1D1D1D;">
                                        <i data-lucide="file-text" style="width: 18px; height: 18px;"></i>
                                        <span>Buka Dokumen Laporan Tahunan</span>
                                    </a>
                                </div>
                            </div>

                        </article>
                    </div>
                </section>
            </main>

            @include('partials.site-footer')
        </div>

        <script nonce="{{ Vite::cspNonce() }}">
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof lucide !== 'undefined' && lucide.createIcons) {
                    lucide.createIcons();
                }
            });
        </script>
    </body>
</html>
