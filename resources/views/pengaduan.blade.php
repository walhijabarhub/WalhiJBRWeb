<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @include('partials.seo-meta', ['title' => 'Posko Pengaduan Kasus Lingkungan - WALHI Jawa Barat'])

        @include('partials.head-assets', ['lucide' => true])

    </head>
    <body style="width: 100%; background: #F4F1EA; margin: 0; overflow-x: clip; color: #1D1D1D; font-family: Montserrat, sans-serif;">
        <div style="position: relative; width: 100%; overflow-x: clip; background: #F4F1EA;">
            @include('partials.site-header')

            <main style="display: flex; flex-direction: column; align-items: stretch;">
                
                <!-- Hero Section -->
                <section style="background: #1D1D1D; border-bottom: 4px #D95C3F solid; color: #F4F1EA;" class="py-12 md:py-16">
                    <div class="w-full max-w-5xl mx-auto px-4 sm:px-8">
                        <div style="display: flex; flex-direction: column; gap: 24px; max-width: 860px; width: 100%;">
                            <!-- Breadcrumbs -->
                            <div style="display: flex; align-items: center; gap: 8px; font-size: 12px; font-family: Montserrat, sans-serif; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px;">
                                <a href="{{ route('home') }}" style="color: #F4F1EA; text-decoration: none; opacity: 0.8;">Beranda</a>
                                <span style="color: #D95C3F; font-weight: 400; font-size: 16px;">/</span>
                                <span style="color: #D95C3F;">Posko Pengaduan Kasus</span>
                            </div>
                            
                            <h1 style="margin: 0; color: #F4F1EA; font-size: clamp(36px, 5.5vw, 64px); font-family: Aspekta, sans-serif; font-weight: 800; line-height: 1.05; letter-spacing: 1.5px; text-transform: uppercase;">
                                POSKO PENGADUAN KASUS
                            </h1>
                            <div style="width: 128px; height: 8px; background: #D95C3F;"></div>
                            <p style="margin: 0; color: #F4F1EA; font-size: 18px; md:font-size: 20px; line-height: 32px; font-family: Montserrat, sans-serif; opacity: 0.9;">
                                Layanan Pendampingan dan Advokasi Hukum bagi Korban Kejahatan Lingkungan Hidup di Jawa Barat
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Content Section -->
                <section style="background: #F4F1EA; color: #1D1D1D; border-bottom: 4px #1D1D1D solid;" class="py-16 md:py-20">
                    <div class="w-full max-w-5xl mx-auto px-4 sm:px-8 flex flex-col gap-12">
                        
                        <!-- Alert Banner: Perlindungan Whistleblower -->
                        <div style="background: #256D4A; border: 4px solid #1D1D1D; box-shadow: 6px 6px 0px 0px #1D1D1D; padding: 20px 24px; color: #F4F1EA; display: flex; gap: 16px; align-items: flex-start;">
                            <i data-lucide="shield-alert" style="width: 28px; height: 28px; color: #F4F1EA; flex-shrink: 0; margin-top: 2px;"></i>
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                <h3 style="margin: 0; font-family: Aspekta, sans-serif; font-size: 18px; font-weight: 800; text-transform: uppercase;">
                                    Jaminan Kerahasiaan Identitas Pelapor (Anti-SLAPP)
                                </h3>
                                <p style="margin: 0; font-size: 14px; line-height: 1.6; font-family: Montserrat, sans-serif; opacity: 0.95;">
                                    WALHI Jawa Barat menjamin kerahasiaan identitas saksi, korban, dan pelapor kejahatan lingkungan sesuai mandat Pasal 66 UU No. 32 Tahun 2009 (Perlindungan Hukum terhadap Pejuang Lingkungan). Laporan Anda dilindungi secara penuh.
                                </p>
                            </div>
                        </div>

                        <!-- 4 Langkah Alur Pengaduan -->
                        <div>
                            <h2 style="margin: 0 0 24px; font-family: Aspekta, sans-serif; font-weight: 800; font-size: 28px; text-transform: uppercase; color: #1D1D1D;">
                                Alur Pendampingan Kasus
                            </h2>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                                <!-- Step 1 -->
                                <div class="bg-white border-4 border-[#1D1D1D] p-6 flex flex-col gap-3 shadow-[4px_4px_0px_0px_#1D1D1D]">
                                    <span style="width: 36px; height: 36px; background: #D95C3F; color: white; display: flex; align-items: center; justify-content: center; font-family: Aspekta, sans-serif; font-weight: 800; font-size: 18px;">1</span>
                                    <h4 style="margin: 0; font-family: Aspekta, sans-serif; font-weight: 800; font-size: 16px; text-transform: uppercase;">Lapor Kasus</h4>
                                    <p style="margin: 0; font-size: 13px; line-height: 1.6; color: #555;">Kirim informasi awal dugaan pelanggaran lingkungan melalui form atau kontak darurat WhatsApp.</p>
                                </div>
                                <!-- Step 2 -->
                                <div class="bg-white border-4 border-[#1D1D1D] p-6 flex flex-col gap-3 shadow-[4px_4px_0px_0px_#1D1D1D]">
                                    <span style="width: 36px; height: 36px; background: #256D4A; color: white; display: flex; align-items: center; justify-content: center; font-family: Aspekta, sans-serif; font-weight: 800; font-size: 18px;">2</span>
                                    <h4 style="margin: 0; font-family: Aspekta, sans-serif; font-weight: 800; font-size: 16px; text-transform: uppercase;">Verifikasi Fakta</h4>
                                    <p style="margin: 0; font-size: 13px; line-height: 1.6; color: #555;">Tim advokasi menelaah dokumen perizinan, RTRW, serta memverifikasi fakta dan bukti lapangan.</p>
                                </div>
                                <!-- Step 3 -->
                                <div class="bg-white border-4 border-[#1D1D1D] p-6 flex flex-col gap-3 shadow-[4px_4px_0px_0px_#1D1D1D]">
                                    <span style="width: 36px; height: 36px; background: #8B6B4A; color: white; display: flex; align-items: center; justify-content: center; font-family: Aspekta, sans-serif; font-weight: 800; font-size: 18px;">3</span>
                                    <h4 style="margin: 0; font-family: Aspekta, sans-serif; font-weight: 800; font-size: 16px; text-transform: uppercase;">Konsolidasi Warga</h4>
                                    <p style="margin: 0; font-size: 13px; line-height: 1.6; color: #555;">Membangun kesepakatan bersama komunitas terdampak mengenai strategi advokasi dan litigasi.</p>
                                </div>
                                <!-- Step 4 -->
                                <div class="bg-white border-4 border-[#1D1D1D] p-6 flex flex-col gap-3 shadow-[4px_4px_0px_0px_#1D1D1D]">
                                    <span style="width: 36px; height: 36px; background: #1D1D1D; color: white; display: flex; align-items: center; justify-content: center; font-family: Aspekta, sans-serif; font-weight: 800; font-size: 18px;">4</span>
                                    <h4 style="margin: 0; font-family: Aspekta, sans-serif; font-weight: 800; font-size: 16px; text-transform: uppercase;">Aksi &amp; Litigasi</h4>
                                    <p style="margin: 0; font-size: 13px; line-height: 1.6; color: #555;">Melakukan gugatan hukum (PTUN/Perdata), audiensi pemerintah, dan penggalangan solidaritas publik.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Form & Direct Contact Grid -->
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                            
                            <!-- Left: Interactive WhatsApp Case Generator -->
                            <div class="lg:col-span-7 bg-white border-4 border-[#1D1D1D] shadow-[8px_8px_0px_0px_#1D1D1D] p-6 sm:p-8 flex flex-col gap-6">
                                <div>
                                    <h3 style="margin: 0; font-family: Aspekta, sans-serif; font-weight: 800; font-size: 22px; text-transform: uppercase; color: #1D1D1D;">
                                        Formulir Pengaduan Cepat
                                    </h3>
                                    <p style="margin: 6px 0 0; font-size: 14px; color: #666; font-family: Montserrat, sans-serif;">
                                        Isi ringkasan informasi di bawah ini. Sistem akan menyiapkan format laporan resmi yang langsung terhubung ke Tim Advokasi Hukum WALHI Jawa Barat:
                                    </p>
                                </div>

                                <div class="flex flex-col gap-4 font-sans text-sm">
                                    <div>
                                        <label for="reporter-name" class="block font-bold text-xs uppercase text-[#1D1D1D] mb-1">Nama Pelapor / Inisial / Komunitas</label>
                                        <input type="text" id="reporter-name" placeholder="Contoh: Warga Desa Mekarsari / Anonim" class="w-full p-3 border-2 border-[#1D1D1D] outline-none focus:border-[#D95C3F] font-sans" />
                                    </div>

                                    <div>
                                        <label for="case-location" class="block font-bold text-xs uppercase text-[#1D1D1D] mb-1">Lokasi Kejadian (Kecamatan / Kabupaten / Kota di Jawa Barat)</label>
                                        <input type="text" id="case-location" placeholder="Contoh: Kec. Paseh, Kab. Bandung" class="w-full p-3 border-2 border-[#1D1D1D] outline-none focus:border-[#D95C3F] font-sans" />
                                    </div>

                                    <div>
                                        <label for="case-type" class="block font-bold text-xs uppercase text-[#1D1D1D] mb-1">Jenis Dugaan Kejahatan / Pelanggaran Lingkungan</label>
                                        <select id="case-type" class="w-full p-3 border-2 border-[#1D1D1D] outline-none focus:border-[#D95C3F] font-sans bg-white">
                                            <option value="Pertambangan Ilegal / Merusak Kawasan">Pertambangan Ilegal / Merusak Kawasan Lindung</option>
                                            <option value="Pencemaran Sungai / Pembuangan Limbah B3">Pencemaran Sungai / Pembuangan Limbah Industri (B3)</option>
                                            <option value="Perampasan Lahan / Konflik Agraria">Perampasan Lahan / Konflik Agraria</option>
                                            <option value="Alih Fungsi Hutan / Deforestasi">Alih Fungsi Hutan / Deforestasi</option>
                                            <option value="Kerusakan Pesisir / Proyek Reklamasi">Kerusakan Pesisir / Tambang Pasir Laut / Reklamasi</option>
                                            <option value="Kriminalisasi Pejuang Lingkungan">Kriminalisasi Warga / Pejuang Lingkungan</option>
                                            <option value="Lainnya">Pelanggaran Lingkungan Lainnya</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label for="case-desc" class="block font-bold text-xs uppercase text-[#1D1D1D] mb-1">Uraian Singkat Kejadian &amp; Dampak</label>
                                        <textarea id="case-desc" rows="4" placeholder="Ceritakan bagaimana kerusakan terjadi, siapa pihak yang diduga bertanggung jawab, dan apa dampaknya bagi warga sekitar..." class="w-full p-3 border-2 border-[#1D1D1D] outline-none focus:border-[#D95C3F] font-sans"></textarea>
                                    </div>

                                    <button type="button" data-on-click="submitCaseReport" style="width: 100%; height: 56px; background: #D95C3F; color: white; border: 2px solid #1D1D1D; font-family: Aspekta, sans-serif; font-weight: 800; font-size: 15px; letter-spacing: 0.5px; text-transform: uppercase; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; box-shadow: 4px 4px 0px 0px #1D1D1D; transition: background 0.2s;" data-hover-style="background:#c44e32" data-rest-style="background:#D95C3F">
                                        <i data-lucide="send" style="width: 18px; height: 18px;"></i>
                                        <span>Kirim Laporan Kasus via WhatsApp</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Right: Contact Hotline & Guidelines -->
                            <div class="lg:col-span-5 flex flex-col gap-6">
                                
                                <!-- Hotline Darurat Box -->
                                <div class="bg-[#1D1D1D] border-4 border-[#1D1D1D] text-[#F4F1EA] p-6 sm:p-8 flex flex-col gap-4 shadow-[8px_8px_0px_0px_#256D4A]">
                                    <div class="bg-[#D95C3F] text-white text-[11px] font-bold uppercase tracking-wider px-3 py-1 w-fit">Kanal Langsung 24 Jam</div>
                                    <h3 style="margin: 0; font-family: Aspekta, sans-serif; font-weight: 800; font-size: 20px; text-transform: uppercase;">
                                        Hotline Advokasi WALHI Jabar
                                    </h3>
                                    <p style="margin: 0; font-size: 14px; line-height: 1.6; opacity: 0.9;">
                                        Untuk situasi darurat (intimidasi, penangkapan warga, atau bencana ekologis mendadak), silakan hubungi saluran siaga di bawah ini:
                                    </p>
                                    <div class="flex flex-col gap-2 pt-2 border-t border-white/20">
                                        <div class="text-sm">
                                            <span class="text-[#5C8D59] font-bold uppercase text-xs">Hotline WhatsApp:</span>
                                            <div class="text-lg font-bold text-white">+62-821-1982-1159</div>
                                        </div>
                                        <div class="text-sm">
                                            <span class="text-[#5C8D59] font-bold uppercase text-xs">Email Pengaduan Kasus:</span>
                                            <div class="text-base font-bold text-white">walhijabar@gmail.com</div>
                                        </div>
                                    </div>
                                    <a href="https://wa.me/6282119821159?text=Halo%20Tim%20Advokasi%20WALHI%20Jawa%20Barat,%20saya%20ingin%20menyampaikan%20laporan%20kasus%20lingkungan%20hidup" target="_blank" style="display: flex; align-items: center; justify-content: center; gap: 8px; background: #256D4A; color: white; padding: 12px 20px; font-weight: 700; font-size: 14px; text-transform: uppercase; text-decoration: none; border: 2px solid white; margin-top: 8px;">
                                        <i data-lucide="message-circle" style="width: 18px; height: 18px;"></i>
                                        <span>Chat WhatsApp Langsung</span>
                                    </a>
                                </div>

                                <!-- Dokumen Pendukung Panduan -->
                                <div class="bg-white border-4 border-[#1D1D1D] shadow-[8px_8px_0px_0px_#1D1D1D] p-6 flex flex-col gap-4">
                                    <h4 style="margin: 0; font-family: Aspekta, sans-serif; font-weight: 800; font-size: 16px; text-transform: uppercase; color: #1D1D1D;">
                                        Data Pendukung yang Diperlukan:
                                    </h4>
                                    <ul class="flex flex-col gap-2 text-sm text-[#444] font-sans list-disc list-inside">
                                        <li>Foto atau video dokumentasi terkini di lokasi kejadian.</li>
                                        <li>Titik koordinat GPS / tautan Google Maps lokasi.</li>
                                        <li>Nama perusahaan / entitas / oknum pelaku terduga.</li>
                                        <li>Jumlah warga atau luas lahan/sungai yang terdampak.</li>
                                        <li>Salinan dokumen izin/surat peringatan (jika ada).</li>
                                    </ul>
                                </div>

                            </div>
                        </div>

                    </div>
                </section>
            </main>

            @include('partials.site-footer')
        </div>

        <script nonce="{{ Vite::cspNonce() }}">
            function submitCaseReport() {
                var name = document.getElementById('reporter-name').value.trim() || 'Anonim';
                var location = document.getElementById('case-location').value.trim() || 'Tidak disebutkan';
                var type = document.getElementById('case-type').value;
                var desc = document.getElementById('case-desc').value.trim() || '-';

                var message = "📢 *LAPORAN PENGADUAN KASUS LINGKUNGAN HIDUP*\n" +
                              "━━━━━━━━━━━━━━━━━━━━━━━━━━━\n" +
                              "*Pelapor / Komunitas:* " + name + "\n" +
                              "*Lokasi Kejadian:* " + location + "\n" +
                              "*Jenis Kasus:* " + type + "\n\n" +
                              "*Uraian & Dampak Kejadian:*\n" + desc + "\n\n" +
                              "━━━━━━━━━━━━━━━━━━━━━━━━━━━\n" +
                              "Mohon petunjuk dan arahan tindak lanjut advokasi dari Tim WALHI Jawa Barat. Terima kasih! 🌿";

                var waUrl = "https://wa.me/6282119821159?text=" + encodeURIComponent(message);
                window.open(waUrl, '_blank');
            }

            document.addEventListener('DOMContentLoaded', function() {
                if (typeof lucide !== 'undefined' && lucide.createIcons) {
                    lucide.createIcons();
                }
            });
        </script>
    </body>
</html>
