<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @include('partials.seo-meta', ['title' => 'Kontak Kami - WALHI Jawa Barat'])

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
                                <span style="color: #5C8D59;">Kontak Kami</span>
                            </div>
                            
                            <h1 style="margin: 0; color: #F4F1EA; font-size: clamp(40px, 6vw, 68px); font-family: Aspekta, sans-serif; font-weight: 800; line-height: 1.05; letter-spacing: 1.5px; text-transform: uppercase;">
                                KONTAK KAMI
                            </h1>
                            <div style="width: 128px; height: 8px; background: #D95C3F;"></div>
                            <p style="margin: 0; color: #5C8D59; font-size: 20px; line-height: 32px; font-family: Montserrat, sans-serif;">
                                Sekretariat Eksekutif Daerah WALHI Jawa Barat
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Content Section -->
                <section style="background: #F4F1EA; color: #1D1D1D; border-bottom: 4px #1D1D1D solid;" class="py-16 md:py-20">
                    <div class="w-full max-w-5xl mx-auto px-4 sm:px-8 flex flex-col gap-12">
                        
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-start">
                            
                            <!-- Left: Contact Details -->
                            <div class="md:col-span-7 flex flex-col gap-6">
                                <div class="bg-white border-4 border-[#1D1D1D] shadow-[8px_8px_0px_0px_#1D1D1D] p-6 sm:p-8 flex flex-col gap-6">
                                    <h2 style="margin: 0; font-family: Aspekta, sans-serif; font-weight: 800; font-size: 24px; text-transform: uppercase; color: #1D1D1D; border-bottom: 3px solid #256D4A; padding-bottom: 12px;">
                                        Kantor &amp; Kesekretariatan
                                    </h2>
                                    
                                    <!-- Alamat -->
                                    <div class="flex items-start gap-4">
                                        <div style="width: 44px; height: 44px; background: #256D4A; color: white; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 2px solid #1D1D1D;">
                                            <i data-lucide="map-pin" style="width: 22px; height: 22px;"></i>
                                        </div>
                                        <div>
                                            <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: #8B6B4A; letter-spacing: 0.5px;">Alamat Kantor</div>
                                            <div style="font-size: 16px; font-weight: 600; color: #1D1D1D; margin-top: 4px; line-height: 1.5;">
                                                Jl. Simponi No. 29, Kel. Turangga, Kec. Lengkong, Kota Bandung, Jawa Barat 40264, Indonesia
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Jam Operasional -->
                                    <div class="flex items-start gap-4">
                                        <div style="width: 44px; height: 44px; background: #8B6B4A; color: white; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 2px solid #1D1D1D;">
                                            <i data-lucide="clock" style="width: 22px; height: 22px;"></i>
                                        </div>
                                        <div>
                                            <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: #8B6B4A; letter-spacing: 0.5px;">Jam Pelayanan Advokasi</div>
                                            <div style="font-size: 16px; font-weight: 600; color: #1D1D1D; margin-top: 4px; line-height: 1.5;">
                                                Senin – Jumat: 09.00 – 17.00 WIB<br>
                                                <span style="font-size: 13px; color: #666; font-weight: 500;">(Hari Sabtu, Minggu, &amp; Libur Nasional tutup untuk konsultasi kantor)</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Telepon & WhatsApp -->
                                    <div class="flex items-start gap-4">
                                        <div style="width: 44px; height: 44px; background: #256D4A; color: white; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 2px solid #1D1D1D;">
                                            <i data-lucide="phone" style="width: 22px; height: 22px;"></i>
                                        </div>
                                        <div>
                                            <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: #8B6B4A; letter-spacing: 0.5px;">Telepon &amp; WhatsApp Resmi</div>
                                            <div style="font-size: 16px; font-weight: 600; color: #1D1D1D; margin-top: 4px; line-height: 1.5;">
                                                WhatsApp: <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $globalContact->whatsapp ?? '6282119821159') }}" target="_blank" style="color: #256D4A; text-decoration: underline; font-weight: 700;">+62 821-1982-1159</a><br>
                                                Telp Kantor: (022) 63175011
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Email -->
                                    <div class="flex items-start gap-4">
                                        <div style="width: 44px; height: 44px; background: #D95C3F; color: white; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 2px solid #1D1D1D;">
                                            <i data-lucide="mail" style="width: 22px; height: 22px;"></i>
                                        </div>
                                        <div>
                                            <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: #8B6B4A; letter-spacing: 0.5px;">Surat Elektronik (Email)</div>
                                            <div style="font-size: 16px; font-weight: 600; color: #1D1D1D; margin-top: 4px; line-height: 1.5;">
                                                <a href="mailto:walhijabar@gmail.com" style="color: #D95C3F; text-decoration: underline; font-weight: 700;">walhijabar@gmail.com</a>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Social Media Buttons -->
                                    <div style="border-top: 2px solid #1D1D1D; padding-top: 16px;">
                                        <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: #8B6B4A; margin-bottom: 12px; letter-spacing: 0.5px;">Kanal Media Sosial Resmi</div>
                                        <div class="flex flex-wrap gap-3">
                                            <a href="https://x.com/walhijabar" target="_blank" style="display: inline-flex; align-items: center; gap: 8px; background: #1D1D1D; color: white; padding: 8px 16px; font-weight: 700; font-size: 13px; text-decoration: none; border: 2px solid #1D1D1D;">
                                                <span>X / Twitter</span>
                                            </a>
                                            <a href="{{ $globalContact->instagram ?? 'https://instagram.com/walhi.jabar' }}" target="_blank" style="display: inline-flex; align-items: center; gap: 8px; background: #1D1D1D; color: white; padding: 8px 16px; font-weight: 700; font-size: 13px; text-decoration: none; border: 2px solid #1D1D1D;">
                                                <span>Instagram</span>
                                            </a>
                                            <a href="{{ $globalContact->facebook ?? 'https://facebook.com/walhi.jabar' }}" target="_blank" style="display: inline-flex; align-items: center; gap: 8px; background: #1D1D1D; color: white; padding: 8px 16px; font-weight: 700; font-size: 13px; text-decoration: none; border: 2px solid #1D1D1D;">
                                                <span>Facebook</span>
                                            </a>
                                            <a href="{{ $globalContact->youtube ?? 'https://www.youtube.com/@walhijabar' }}" target="_blank" style="display: inline-flex; align-items: center; gap: 8px; background: #1D1D1D; color: white; padding: 8px 16px; font-weight: 700; font-size: 13px; text-decoration: none; border: 2px solid #1D1D1D;">
                                                <span>YouTube</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Fast Actions & Notice -->
                            <div class="md:col-span-5 flex flex-col gap-6">
                                
                                <!-- Pengaduan Kasus Card -->
                                <div class="bg-[#256D4A] border-4 border-[#1D1D1D] shadow-[8px_8px_0px_0px_#1D1D1D] p-6 sm:p-8 text-[#F4F1EA] flex flex-col gap-4">
                                    <div class="bg-[#D95C3F] text-white text-[11px] font-bold uppercase tracking-wider px-3 py-1 w-fit">Kanal Darurat Kasus</div>
                                    <h3 style="margin: 0; font-family: Aspekta, sans-serif; font-weight: 800; font-size: 22px; text-transform: uppercase;">
                                        Pengaduan Kasus Lingkungan
                                    </h3>
                                    <p style="margin: 0; font-size: 14px; line-height: 1.6; opacity: 0.9;">
                                        Masyarakat yang ingin menyampaikan laporan dugaan perusakan lingkungan, pencemaran air/sungai, sengketa lahan, atau tambang ilegal dapat langsung mengakses Posko Pengaduan Kasus kami.
                                    </p>
                                    <a href="{{ route('pengaduan') }}" style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; background: #D95C3F; color: white; padding: 14px 24px; font-weight: 700; font-size: 14px; text-transform: uppercase; text-decoration: none; border: 2px solid white; box-shadow: 3px 3px 0px 0px #1D1D1D; margin-top: 8px; transition: background 0.2s;" data-hover-style="background:#c44e32" data-rest-style="background:#D95C3F">
                                        <i data-lucide="alert-triangle" style="width: 18px; height: 18px;"></i>
                                        <span>Buka Posko Pengaduan</span>
                                    </a>
                                </div>

                                <!-- Direktori Narahubung -->
                                <div class="bg-white border-4 border-[#1D1D1D] shadow-[8px_8px_0px_0px_#1D1D1D] p-6 sm:p-8 flex flex-col gap-4">
                                    <h3 style="margin: 0; font-family: Aspekta, sans-serif; font-weight: 800; font-size: 18px; text-transform: uppercase; color: #1D1D1D;">
                                        Narahubung Media &amp; Kampanye
                                    </h3>
                                    <div class="flex flex-col gap-3 font-sans text-sm text-[#1D1D1D]">
                                        <div style="border-bottom: 1px dashed #ccc; padding-bottom: 8px;">
                                            <div style="font-weight: 700;">Wahyudin Iwang</div>
                                            <div style="font-size: 12px; color: #666;">Direktur Eksekutif WALHI Jawa Barat</div>
                                            <div style="font-size: 13px; font-weight: 600; color: #256D4A; margin-top: 2px;">+62-813-9536-7883</div>
                                        </div>
                                        <div style="border-bottom: 1px dashed #ccc; padding-bottom: 8px;">
                                            <div style="font-weight: 700;">Tim Advokasi &amp; Kampanye</div>
                                            <div style="font-size: 12px; color: #666;">Divisi Advokasi Hukum &amp; Kebijakan</div>
                                            <div style="font-size: 13px; font-weight: 600; color: #256D4A; margin-top: 2px;">+62-821-1982-1159</div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

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
