<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @include('partials.seo-meta', ['title' => 'Kebijakan Privasi - WALHI Jawa Barat'])

        @include('partials.head-assets', ['lucide' => true])

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
                                <span style="color: #5C8D59;">Kebijakan Privasi</span>
                            </div>
                            
                            <h1 style="margin: 0; color: #F4F1EA; font-size: clamp(36px, 5.5vw, 64px); font-family: Aspekta, sans-serif; font-weight: 800; line-height: 1.05; letter-spacing: 1.5px; text-transform: uppercase;">
                                KEBIJAKAN PRIVASI
                            </h1>
                            <div style="width: 128px; height: 8px; background: #D95C3F;"></div>
                            <p style="margin: 0; color: #5C8D59; font-size: 20px; line-height: 32px; font-family: Montserrat, sans-serif;">
                                Komitmen Perlindungan Data Pribadi Pengguna &amp; Donatur Publik
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Content Section -->
                <section style="background: #F4F1EA; color: #1D1D1D; border-bottom: 4px #1D1D1D solid;" class="py-16 md:py-20">
                    <div class="w-full max-w-4xl mx-auto px-4 sm:px-8">
                        <article class="bg-white border-4 border-[#1D1D1D] shadow-[8px_8px_0px_0px_#1D1D1D] p-8 md:p-12 flex flex-col gap-8 font-sans leading-relaxed text-[#1D1D1D]">
                            
                            <div style="border-bottom: 2px solid #1D1D1D; padding-bottom: 16px;">
                                <span style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: #8B6B4A; letter-spacing: 0.5px;">Terakhir Diperbarui: 19 September 2026</span>
                                <p class="mt-2 text-base md:text-lg font-medium text-gray-700">
                                    Wahana Lingkungan Hidup Indonesia (WALHI) Jawa Barat menghargai dan berkomitmen menjaga privasi setiap pengunjung situs, pelapor kasus lingkungan, pelanggan nawala (newsletter), dan donatur publik.
                                </p>
                            </div>

                            <div class="flex flex-col gap-4">
                                <h2 style="font-family: Aspekta, sans-serif; font-weight: 800; font-size: 22px; text-transform: uppercase; color: #256D4A;">
                                    1. Informasi yang Kami Kumpulkan
                                </h2>
                                <p>Kami mengumpulkan data secara terbatas dan proporsional untuk keperluan komunikasi advokasi dan interaksi publik:</p>
                                <ul class="list-disc list-inside flex flex-col gap-2 pl-2">
                                    <li><strong>Langganan Nawala:</strong> Alamat email yang Anda masukkan saat mendaftar buletin berkala.</li>
                                    <li><strong>Interaksi Komentar:</strong> Nama/alias dan isi komentar publik yang disubmit pada artikel blog/publikasi.</li>
                                    <li><strong>Komunikasi WhatsApp (Donasi &amp; Aduan):</strong> Informasi nama dan pesan yang secara sukarela Anda teruskan melalui tautan WhatsApp resmi kami.</li>
                                    <li><strong>Log Teknis &amp; Keamanan:</strong> Alamat IP tersamar dan User-Agent semata-mata untuk mencegah serangan bot, spam komentar, dan penyalahgunaan sistem melalui rate limiting.</li>
                                </ul>
                            </div>

                            <div class="flex flex-col gap-4">
                                <h2 style="font-family: Aspekta, sans-serif; font-weight: 800; font-size: 22px; text-transform: uppercase; color: #256D4A;">
                                    2. Penggunaan &amp; Kerahasiaan Data
                                </h2>
                                <p>Informasi yang Anda berikan <strong>tidak akan pernah</strong> dijual, disewakan, dialihkan, atau dibagikan kepada korporasi komersial, biro iklan, atau pihak ketiga mana pun.</p>
                                <p>Data semata-mata digunakan untuk:</p>
                                <ul class="list-disc list-inside flex flex-col gap-2 pl-2">
                                    <li>Mengirimkan informasi siaran pers, investigasi, dan kabar kampanye lingkungan hidup.</li>
                                    <li>Memverifikasi dan menindaklanjuti laporan aduan kasus lingkungan hidup di wilayah Jawa Barat.</li>
                                    <li>Mengonfirmasi dan mencatat transparansi dukungan donasi publik.</li>
                                </ul>
                            </div>

                            <div class="flex flex-col gap-4">
                                <h2 style="font-family: Aspekta, sans-serif; font-weight: 800; font-size: 22px; text-transform: uppercase; color: #256D4A;">
                                    3. Keamanan Teknis Platform
                                </h2>
                                <p>Website ini menerapkan standar keamanan terpadu:</p>
                                <ul class="list-disc list-inside flex flex-col gap-2 pl-2">
                                    <li>Enkripsi transmisi data menggunakan protokol HTTPS / TLS standar industri.</li>
                                    <li>Penerapan <em>Content Security Policy</em> (CSP) dengan nonce kriptografis dinamis guna mencegah serangan Cross-Site Scripting (XSS).</li>
                                    <li>Perlindungan Cross-Site Request Forgery (CSRF) pada seluruh formulir pengiriman data.</li>
                                    <li>Penyimpanan dokumen internal secara terisolasi (private disk) untuk mencegah akses publik tanpa otorisasi.</li>
                                </ul>
                            </div>

                            <div class="flex flex-col gap-4">
                                <h2 style="font-family: Aspekta, sans-serif; font-weight: 800; font-size: 22px; text-transform: uppercase; color: #256D4A;">
                                    4. Hak Pengguna &amp; Kontak Privasi
                                </h2>
                                <p>Anda berhak untuk setiap saat meminta penghapusan alamat email dari daftar nawala, atau mengajukan permohonan klarifikasi atas data komunikasi Anda dengan menghubungi kami di:</p>
                                <div style="background: #F4F1EA; border-left: 4px solid #256D4A; padding: 16px; font-weight: 600;">
                                    Sekretariat WALHI Jawa Barat<br>
                                    Jl. Simponi No. 29, Turangga, Lengkong, Bandung 40264<br>
                                    Email: <a href="mailto:walhijabar@gmail.com" style="color: #256D4A; text-decoration: underline;">walhijabar@gmail.com</a> | WA: +62-82-1982-1159
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
