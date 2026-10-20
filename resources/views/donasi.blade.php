<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @include('partials.seo-meta', ['title' => 'Dukung Gerakan - WALHI Jawa Barat'])

        @include('partials.head-assets', ['lucide' => true])

        <meta name="csrf-token" content="{{ csrf_token() }}">

        <style>
            .hover-pillar-card {
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }
            .hover-pillar-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 12px 28px rgba(0, 0, 0, 0.15);
            }
            
            .amount-btn {
                transition: all 0.2s ease;
                background: white;
                color: #1D1D1D;
                border: 2px solid #1D1D1D;
                cursor: pointer;
            }
            .amount-btn:hover {
                background: #F4F1EA;
            }
            .amount-btn.active {
                background: #1D1D1D !important;
                color: #F4F1EA !important;
                border-color: #1D1D1D !important;
            }
            
            .input-field {
                width: 100%;
                height: 60px;
                padding: 16px 24px;
                border: 2px solid #1D1D1D;
                font-family: Montserrat, sans-serif;
                font-size: 16px;
                background: white;
                box-sizing: border-box;
                outline: none;
                color: #1D1D1D;
                transition: border-color 0.2s;
            }
            .input-field:focus {
                border-color: #256D4A;
            }
            
            .submit-btn {
                background: #D95C3F;
                color: #F4F1EA;
                border: none;
                outline: 2px solid #D95C3F;
                outline-offset: -2px;
                cursor: pointer;
                transition: background 0.2s;
            }
            .submit-btn:hover {
                background: #256D4A;
                outline-color: #256D4A;
            }
            
            /* Responsive Utilities */
            @media (max-width: 900px) {
                .why-donate-section {
                    padding: 48px 32px !important;
                }
                .why-donate-grid {
                    grid-template-columns: 1fr !important;
                    gap: 24px !important;
                }
                .form-outer-container {
                    padding: 48px 24px !important;
                }
            }
            
            @media (max-width: 640px) {
                .amount-grid {
                    grid-template-columns: repeat(2, 1fr) !important;
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
                                <span style="color: #F4F1EA; opacity: 0.8;">Dukung Kami</span>
                                <span style="color: #256D4A; font-weight: 400; font-size: 16px;">/</span>
                                <span style="color: #5C8D59;">Donasi Publik</span>
                            </div>
                            
                            <h1 style="margin: 0; color: #F4F1EA; font-size: clamp(48px, 7vw, 76px); font-family: Aspekta, sans-serif; font-weight: 800; line-height: 1.05; letter-spacing: 1px; text-transform: uppercase;">
                                DONASI PUBLIK
                            </h1>
                            <div style="width: 128px; height: 8px; background: #D95C3F;"></div>
                            <p style="margin: 0; color: #5C8D59; font-size: 20px; line-height: 32px; font-family: Montserrat, sans-serif; font-weight: 500;">
                                Dukung Perjuangan Keadilan Ekologis
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Why Donate Section -->
                <section style="background: #F4F1EA; color: #1D1D1D;" class="py-16 md:py-20">
                    <div class="w-full max-w-5xl mx-auto px-4 sm:px-8 flex flex-col gap-10">
                        
                        <!-- Green Callout Block -->
                        <div style="background: #256D4A; border: 4px solid #256D4A; outline: 4px #256D4A solid; outline-offset: -4px; padding: 52px; display: flex; flex-direction: column; align-items: center; gap: 36px; box-sizing: border-box; color: #F4F1EA;" class="why-donate-section">
                            <div style="text-align: center; max-width: 720px; display: flex; flex-direction: column; gap: 16px;">
                                <h2 style="margin: 0; font-family: Aspekta, sans-serif; font-size: clamp(32px, 5vw, 44px); font-weight: 800; text-transform: uppercase; letter-spacing: 1px; line-height: 1.2;">
                                    MENGAPA DONASI KE WALHI?
                                </h2>
                                <p style="margin: 0; font-family: Montserrat, sans-serif; font-size: 18px; line-height: 1.7; opacity: 0.95;">
                                    WALHI Jawa Barat adalah organisasi independen yang tidak menerima dana dari korporasi atau pemerintah. Kami 100% didanai oleh masyarakat. Setiap rupiah yang Anda berikan langsung mendukung perjuangan keadilan ekologis.
                                </p>
                            </div>
                            
                            <!-- Pillars Grid -->
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 32px; width: 100%;" class="why-donate-grid">
                                <!-- Pillar 1 -->
                                <div style="background: white; border: 4px solid white; outline: 4px #F4F1EA solid; outline-offset: -4px; padding: 32px; display: flex; flex-direction: column; align-items: center; gap: 16px; box-sizing: border-box; color: #1D1D1D; text-align: center;" class="hover-pillar-card">
                                    <div style="width: 72px; height: 72px; background: #1D1D1D; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #256D4A;">
                                        <i data-lucide="shield-alert" style="width: 32px; height: 32px;"></i>
                                    </div>
                                    <h3 style="margin: 0; font-family: Aspekta, sans-serif; font-weight: 700; font-size: 20px; letter-spacing: 0.5px; text-transform: uppercase;">Pendampingan Rakyat</h3>
                                    <p style="margin: 0; font-family: Montserrat, sans-serif; font-size: 14px; line-height: 1.6; color: #555;">
                                        Donasi membantu kami mendampingi komunitas yang terdampak kerusakan lingkungan, memberikan bantuan hukum dan advokasi.
                                    </p>
                                </div>
                                
                                <!-- Pillar 2 -->
                                <div style="background: white; border: 4px solid white; outline: 4px #F4F1EA solid; outline-offset: -4px; padding: 32px; display: flex; flex-direction: column; align-items: center; gap: 16px; box-sizing: border-box; color: #1D1D1D; text-align: center;" class="hover-pillar-card">
                                    <div style="width: 72px; height: 72px; background: #1D1D1D; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #5C8D59;">
                                        <i data-lucide="bar-chart-3" style="width: 32px; height: 32px;"></i>
                                    </div>
                                    <h3 style="margin: 0; font-family: Aspekta, sans-serif; font-weight: 700; font-size: 20px; letter-spacing: 0.5px; text-transform: uppercase;">Investigasi & Riset</h3>
                                    <p style="margin: 0; font-family: Montserrat, sans-serif; font-size: 14px; line-height: 1.6; color: #555;">
                                        Dana untuk investigasi mendalam kerusakan lingkungan, pengumpulan data, dan produksi laporan berkualitas.
                                    </p>
                                </div>
                                
                                <!-- Pillar 3 -->
                                <div style="background: white; border: 4px solid white; outline: 4px #F4F1EA solid; outline-offset: -4px; padding: 32px; display: flex; flex-direction: column; align-items: center; gap: 16px; box-sizing: border-box; color: #1D1D1D; text-align: center;" class="hover-pillar-card">
                                    <div style="width: 72px; height: 72px; background: #1D1D1D; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #8B6B4A;">
                                        <i data-lucide="megaphone" style="width: 32px; height: 32px;"></i>
                                    </div>
                                    <h3 style="margin: 0; font-family: Aspekta, sans-serif; font-weight: 700; font-size: 20px; letter-spacing: 0.5px; text-transform: uppercase;">Kampanye Publik</h3>
                                    <p style="margin: 0; font-family: Montserrat, sans-serif; font-size: 14px; line-height: 1.6; color: #555;">
                                        Membiayai kampanye kesadaran lingkungan, aksi lapangan, dan gerakan massa untuk perubahan kebijakan.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- WhatsApp Donation Block -->
                        <div style="background: white; border: 4px solid #1D1D1D; outline: 4px #1D1D1D solid; outline-offset: -4px; padding: 48px 64px; display: flex; flex-direction: column; gap: 32px; box-sizing: border-box;" class="form-outer-container">
                            
                            <!-- WhatsApp Narrative Hero Card -->
                            <div style="background: #256D4A; border: 3px solid #1D1D1D; padding: 32px 24px; color: #F4F1EA; display: flex; flex-direction: column; gap: 16px; text-align: center; box-shadow: 4px 4px 0px 0px #1D1D1D;">
                                <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #1D1D1D; background: #F4F1EA; display: inline-block; padding: 4px 14px; margin: 0 auto;">Layanan Donasi Langsung</div>
                                <h2 style="margin: 0; font-family: Aspekta, sans-serif; font-weight: 800; font-size: clamp(24px, 4vw, 32px); letter-spacing: 0.5px; text-transform: uppercase; color: #F4F1EA;">
                                    Donasi Publik via WhatsApp
                                </h2>
                                <p style="margin: 0; font-size: 17px; font-weight: 500; line-height: 1.6; font-family: Montserrat, sans-serif; max-width: 680px; margin: 0 auto;">
                                    “Ingin berdonasi untuk lingkungan hidup? Hubungi WA dibawah ini untuk mengetahui manfaat apa saja yang akan kamu terima :)”
                                </p>
                                <div style="margin-top: 8px; display: flex; justify-content: center;">
                                    <a href="https://wa.me/6282119821159?text=Halo%20WALHI%20Jawa%20Barat,%20saya%20ingin%20berdonasi%20untuk%20lingkungan%20hidup.%20Mohon%20informasi%20manfaat%20apa%20saja%20yang%20akan%20saya%20terima%20:)" target="_blank" style="display: inline-flex; align-items: center; justify-content: center; gap: 10px; background: #D95C3F; color: #FFFFFF; padding: 16px 32px; font-weight: 700; font-size: 16px; text-transform: uppercase; text-decoration: none; border: 2px solid #FFFFFF; box-shadow: 3px 3px 0px 0px #1D1D1D; transition: all 0.2s;" data-hover-style="background:#c44e32" data-rest-style="background:#D95C3F">
                                        <i data-lucide="message-circle" style="width: 22px; height: 22px;"></i>
                                        <span>Hubungi WA WALHI Jabar (+62-821-1982-1159)</span>
                                    </a>
                                </div>
                            </div>

                            <!-- Fast Nominal Selection -->
                            <div style="display: flex; flex-direction: column; gap: 24px; width: 100%;">
                                <div style="display: flex; flex-direction: column; gap: 8px;">
                                    <h3 style="margin: 0; color: #1D1D1D; font-family: Aspekta, sans-serif; font-size: 22px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;">
                                        Pilih Nominal Donasi
                                    </h3>
                                    <p style="margin: 0; color: #666; font-family: Montserrat, sans-serif; font-size: 14px; line-height: 1.5;">
                                        Pilih nominal cepat di bawah ini atau tentukan sendiri. Pesan konfirmasi donasi akan otomatis disiapkan untuk WhatsApp resmi WALHI Jawa Barat:
                                    </p>
                                </div>
                                
                                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; width: 100%;" class="amount-grid">
                                    <button type="button" class="amount-btn" data-on-click="selectPresetAmount" data-args='[10000]' style="height: 60px; font-family: Montserrat, sans-serif; font-weight: 700; font-size: 16px;">Rp 10.000</button>
                                    <button type="button" class="amount-btn" data-on-click="selectPresetAmount" data-args='[25000]' style="height: 60px; font-family: Montserrat, sans-serif; font-weight: 700; font-size: 16px;">Rp 25.000</button>
                                    <button type="button" class="amount-btn" data-on-click="selectPresetAmount" data-args='[30000]' style="height: 60px; font-family: Montserrat, sans-serif; font-weight: 700; font-size: 16px;">Rp 30.000</button>
                                    <button type="button" class="amount-btn" data-on-click="selectPresetAmount" data-args='[50000]' style="height: 60px; font-family: Montserrat, sans-serif; font-weight: 700; font-size: 16px;">Rp 50.000</button>
                                    <button type="button" class="amount-btn" data-on-click="selectPresetAmount" data-args='[100000]' style="height: 60px; font-family: Montserrat, sans-serif; font-weight: 700; font-size: 16px;">Rp 100.000</button>
                                    <button type="button" class="amount-btn" data-on-click="selectPresetAmount" data-args='[150000]' style="height: 60px; font-family: Montserrat, sans-serif; font-weight: 700; font-size: 16px;">Rp 150.000</button>
                                </div>
                                
                                <!-- Custom Amount Field -->
                                <div style="position: relative; width: 100%;">
                                    <input type="number" id="custom-amount" placeholder="Atau masukkan nominal lain (Rp)" autocomplete="off" data-on-input="handleCustomAmountInput" class="input-field" />
                                </div>

                                <!-- Optional Donor Info -->
                                <div style="display: flex; flex-direction: column; gap: 12px;">
                                    <label style="color: #1D1D1D; font-family: Montserrat, sans-serif; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.35px;">
                                        Catatan / Identitas Donatur (Opsional)
                                    </label>
                                    <input type="text" id="donor-name" placeholder="Nama Anda (Opsional, kosongkan jika anonim)" autocomplete="name" class="input-field" />
                                    <textarea id="donor-notes" rows="2" placeholder="Pesan atau doa untuk gerakan keadilan ekologis (opsional)" class="input-field" style="resize: vertical;"></textarea>
                                </div>
                                
                                <!-- Action Button: Direct WhatsApp Donation -->
                                <div style="display: flex; flex-direction: column; gap: 12px; width: 100%;">
                                    <button type="button" data-on-click="handleWhatsAppDonationSubmit" style="height: 64px; background: #256D4A; color: white; border: 2px solid #1D1D1D; font-family: Montserrat, sans-serif; font-weight: 700; font-size: 16px; letter-spacing: 0.45px; text-transform: uppercase; display: flex; align-items: center; justify-content: center; gap: 10px; cursor: pointer; transition: background 0.2s; box-shadow: 4px 4px 0px 0px #1D1D1D;" data-hover-style="background:#1f5a3d" data-rest-style="background:#256D4A">
                                        <i data-lucide="message-circle" style="width: 24px; height: 24px;"></i>
                                        <span>Lanjutkan Donasi via WhatsApp</span>
                                    </button>
                                </div>
                            </div>

                            <!-- 3-Step Transparent Process Guide -->
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; border-top: 2px solid #1D1D1D; padding-top: 28px;" class="why-donate-grid">
                                <div style="display: flex; flex-direction: column; gap: 8px;">
                                    <div style="display: flex; align-items: center; gap: 8px; color: #256D4A; font-family: Aspekta, sans-serif; font-weight: 800; font-size: 16px;">
                                        <span style="background: #256D4A; color: white; width: 28px; height: 28px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 13px;">1</span>
                                        <span>Chat WhatsApp</span>
                                    </div>
                                    <p style="margin: 0; font-size: 13px; line-height: 1.5; color: #555;">
                                        Klik tombol untuk terhubung langsung dengan WhatsApp resmi WALHI Jabar (+62-821-1982-1159).
                                    </p>
                                </div>
                                <div style="display: flex; flex-direction: column; gap: 8px;">
                                    <div style="display: flex; align-items: center; gap: 8px; color: #256D4A; font-family: Aspekta, sans-serif; font-weight: 800; font-size: 16px;">
                                        <span style="background: #256D4A; color: white; width: 28px; height: 28px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 13px;">2</span>
                                        <span>Rekening &amp; QRIS</span>
                                    </div>
                                    <p style="margin: 0; font-size: 13px; line-height: 1.5; color: #555;">
                                        Admin akan memberikan nomor rekening giro resmi atau QRIS resmi atas nama WALHI Jawa Barat.
                                    </p>
                                </div>
                                <div style="display: flex; flex-direction: column; gap: 8px;">
                                    <div style="display: flex; align-items: center; gap: 8px; color: #256D4A; font-family: Aspekta, sans-serif; font-weight: 800; font-size: 16px;">
                                        <span style="background: #256D4A; color: white; width: 28px; height: 28px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 13px;">3</span>
                                        <span>Konfirmasi Donasi</span>
                                    </div>
                                    <p style="margin: 0; font-size: 13px; line-height: 1.5; color: #555;">
                                        Kirim bukti transfer via chat untuk pencatatan dan penerbitan bukti donasi publik yang transparan.
                                    </p>
                                </div>
                            </div>

                            <!-- Transparency Notice -->
                            <div style="background: #F4F1EA; border-left: 4px solid #256D4A; padding: 20px 24px; display: flex; gap: 16px; box-sizing: border-box; align-items: flex-start;">
                                <i data-lucide="shield-check" style="width: 24px; height: 24px; color: #256D4A; flex-shrink: 0; margin-top: 2px;"></i>
                                <div style="display: flex; flex-direction: column; gap: 6px;">
                                    <h4 style="margin: 0; color: #1D1D1D; font-family: Montserrat, sans-serif; font-size: 15px; font-weight: 700; line-height: 1.2;">
                                        Transparansi &amp; Rekening Resmi
                                    </h4>
                                    <p style="margin: 0; color: #444; font-family: Montserrat, sans-serif; font-size: 13px; line-height: 1.6;">
                                        Seluruh donasi disalurkan langsung untuk advokasi dan pendampingan masyarakat korban krisis iklim. Admin resmi WALHI Jawa Barat akan memverifikasi dan memberikan nomor rekening resmi / QRIS langsung melalui WhatsApp.
                                    </p>
                                </div>
                            </div>

                        </div>
                    </div>
                </section>
            </main>
            @include('partials.site-footer')
        </div>

        <script nonce="{{ Vite::cspNonce() }}">
            // Select Preset Amount
            function selectPresetAmount(amount, buttonElement) {
                var buttons = document.getElementsByClassName('amount-btn');
                for (var i = 0; i < buttons.length; i++) {
                    buttons[i].classList.remove('active');
                }
                buttonElement.classList.add('active');
                var customInput = document.getElementById('custom-amount');
                if (customInput) customInput.value = amount;
            }
            
            // Custom Amount Input Handler
            function handleCustomAmountInput(inputElement) {
                var buttons = document.getElementsByClassName('amount-btn');
                var val = parseInt(inputElement.value);
                for (var i = 0; i < buttons.length; i++) {
                    buttons[i].classList.remove('active');
                    var btnText = buttons[i].textContent.replace(/[^0-9]/g, '');
                    if (val === parseInt(btnText)) {
                        buttons[i].classList.add('active');
                    }
                }
            }
            
            // Format Currency
            function formatRupiah(amount) {
                return 'Rp ' + parseInt(amount).toLocaleString('id-ID');
            }
            
            // Direct WhatsApp Donation Handler
            function handleWhatsAppDonationSubmit(event) {
                if (event && event.preventDefault) event.preventDefault();

                var nameInput = document.getElementById('donor-name');
                var amountInput = document.getElementById('custom-amount');
                var notesInput = document.getElementById('donor-notes');
                
                var name = (nameInput && nameInput.value) ? nameInput.value.trim() : '';
                var rawAmount = (amountInput && amountInput.value) ? amountInput.value.toString().trim().replace(/[^0-9]/g, '') : '';
                var notes = (notesInput && notesInput.value) ? notesInput.value.trim() : '';
                
                var amount = rawAmount ? parseInt(rawAmount, 10) : 0;

                // Validation checks (TC-DON-006, TC-DON-007, TC-DON-008)
                if (amountInput && amountInput.value.trim() !== '' && (isNaN(amount) || amount <= 0)) {
                    alert('Mohon masukkan nominal donasi yang valid (minimal Rp 1.000).');
                    amountInput.focus();
                    return;
                }

                if (amount > 1000000000) {
                    alert('Untuk donasi atau hibah institusional di atas Rp 1.000.000.000, silakan hubungi tim kemitraan kami secara langsung melalui kontak resmi.');
                    return;
                }
                
                var waNumber = "{{ preg_replace('/[^0-9]/', '', $globalContact->whatsapp ?? '6282119821159') }}";
                var message = "Halo Tim WALHI Jawa Barat,\n\n" +
                    "Saya ingin berdonasi untuk mendukung advokasi lingkungan hidup dan gerakan keadilan ekologis di Jawa Barat.\n\n";
                
                if (amount > 0) {
                    message += "📌 *Rencana Donasi:*\n";
                    if (name) {
                        message += "• Nama: " + name + "\n";
                    } else {
                        message += "• Nama: Anonim (Hamba Allah / Sahabat Lingkungan)\n";
                    }
                    message += "• Nominal Donasi: " + formatRupiah(amount) + "\n";
                    if (notes) message += "• Pesan/Doa: " + notes + "\n";
                    message += "\n";
                } else if (name) {
                    message += "Saya atas nama: " + name + "\n\n";
                }
                
                message += "Mohon informasi rekening resmi atau QRIS WALHI Jawa Barat untuk penyaluran donasi ini. Terima kasih! 🙏🌿";
                
                var url = "https://wa.me/" + waNumber + "?text=" + encodeURIComponent(message);
                window.open(url, '_blank');
            }
            
            // Backward-compatible alias
            function donateViaWhatsApp() {
                handleWhatsAppDonationSubmit(null);
            }
            
            // Initialize Lucide icons on page load
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof lucide !== 'undefined' && lucide.createIcons) {
                    lucide.createIcons();
                }
            });
        </script>
    </body>
</html>
