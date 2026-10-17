<footer class="w-full bg-[#1D1D1D] border-t-4 border-[#F4F1EA] py-12 px-6 sm:px-12 md:px-24 text-[#F4F1EA] overflow-hidden">
    <div class="max-w-6xl mx-auto flex flex-col gap-12">
        <!-- Top Section: Grid 3 Kolom -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-8">
            <!-- Kolom Brand -->
            <div class="md:col-span-6 flex flex-col gap-6">
                <h3 class="text-2xl sm:text-3xl font-heading font-extrabold tracking-wider text-[#F4F1EA] uppercase">WALHI Jawa Barat</h3>
                <p class="text-sm md:text-base leading-relaxed text-[#F4F1EA]/80 max-w-xl">
                    Wahana Lingkungan Hidup Indonesia (WALHI) Jawa Barat adalah organisasi lingkungan hidup independen yang memperjuangkan keadilan ekologis dan kedaulatan rakyat atas sumber daya alam.
                </p>
                <div class="text-xs text-[#F4F1EA]/70 flex flex-col gap-1.5 font-sans">
                    <span class="font-bold uppercase tracking-wider text-[#5C8D59]">Alamat Kantor:</span>
                    <span>Jl. Simponi No.29, Turangga, Kec. Lengkong, Kota Bandung, Jawa Barat 40264</span>
                    <span>Email: walhijabar@gmail.com | WA: +62-82-1982-1159</span>
                </div>
                <!-- Icons Media Sosial -->
                <div class="flex flex-wrap gap-4">
                    <!-- X / Twitter -->
                    <a href="https://x.com/walhijabar" target="_blank" class="w-12 h-12 bg-[#256D4A] hover:bg-[#5C8D59] transition-colors flex items-center justify-center text-[#F4F1EA] border-2 border-transparent hover:border-[#1D1D1D]" aria-label="X (Twitter)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    </a>
                    <!-- Facebook -->
                    <a href="{{ $globalContact->facebook }}" target="_blank" class="w-12 h-12 bg-[#256D4A] hover:bg-[#5C8D59] transition-colors flex items-center justify-center text-[#F4F1EA] border-2 border-transparent hover:border-[#1D1D1D]" aria-label="Facebook">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                    </a>
                    <!-- Instagram -->
                    <a href="{{ $globalContact->instagram }}" target="_blank" class="w-12 h-12 bg-[#256D4A] hover:bg-[#5C8D59] transition-colors flex items-center justify-center text-[#F4F1EA] border-2 border-transparent hover:border-[#1D1D1D]" aria-label="Instagram">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/></svg>
                    </a>
                    <!-- YouTube -->
                    <a href="{{ $globalContact->youtube }}" target="_blank" class="w-12 h-12 bg-[#256D4A] hover:bg-[#5C8D59] transition-colors flex items-center justify-center text-[#F4F1EA] border-2 border-transparent hover:border-[#1D1D1D]" aria-label="YouTube">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.5 17a24.12 24.12 0 0 1 0-10 2 2 0 0 1 1.4-1.4 49.56 49.56 0 0 1 16.2 0A2 2 0 0 1 21.5 7a24.12 24.12 0 0 1 0 10 2 2 0 0 1-1.4 1.4 49.55 49.55 0 0 1-16.2 0A2 2 0 0 1 2.5 17z"/><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"/></svg>
                    </a>
                    <!-- WhatsApp -->
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $globalContact->whatsapp) }}" target="_blank" class="w-12 h-12 bg-[#256D4A] hover:bg-[#5C8D59] transition-colors flex items-center justify-center text-[#F4F1EA] border-2 border-transparent hover:border-[#1D1D1D]" aria-label="WhatsApp">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2zm.01 1.67c2.2 0 4.26.86 5.82 2.42a8.225 8.225 0 0 1 2.41 5.83c0 4.54-3.7 8.24-8.24 8.24-1.48 0-2.93-.4-4.2-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.196 8.196 0 0 1-1.26-4.36c0-4.54 3.7-8.24 8.24-8.24zm4.52 11.51c-.25-.13-1.47-.72-1.7-.81-.23-.08-.39-.13-.56.13-.17.25-.64.81-.79.97-.14.17-.29.19-.54.06-.25-.13-1.06-.39-2.03-1.25-.75-.67-1.26-1.5-1.41-1.75-.14-.25-.02-.39.11-.51.11-.11.25-.29.38-.44.12-.14.17-.25.25-.42.08-.17.04-.31-.02-.44-.06-.13-.56-1.35-.77-1.85-.2-.49-.41-.42-.56-.43h-.48c-.17 0-.44.06-.67.31-.23.25-.88.86-.88 2.09 0 1.23.9 2.42 1.02 2.59.13.17 1.77 2.7 4.29 3.78.6.26 1.07.41 1.43.53.6.19 1.15.16 1.58.1.48-.07 1.47-.6 1.68-1.18.21-.58.21-1.08.15-1.18-.07-.11-.23-.17-.48-.29z"/></svg>
                    </a>
                </div>
            </div>

            <!-- Kolom Navigasi -->
            <div class="md:col-span-3 flex flex-col gap-4">
                <div class="pb-2 border-[#256D4A] border-b-2">
                    <h4 class="text-lg sm:text-xl font-heading font-bold tracking-wider text-[#5C8D59] uppercase">Navigasi</h4>
                </div>
                <ul class="flex flex-col gap-3 text-sm md:text-base text-[#F4F1EA]/80 font-sans">
                    <li><a href="{{ route('about') }}" class="hover:text-[#5C8D59] transition-colors text-decoration-none">Tentang Kami</a></li>
                    <li><a href="{{ route('kontak') }}" class="hover:text-[#5C8D59] transition-colors text-decoration-none">Kontak Kami</a></li>
                    <li><a href="{{ route('home') }}#isu" class="hover:text-[#5C8D59] transition-colors text-decoration-none">Isu Lingkungan</a></li>
                    <li><a href="{{ route('home') }}#kabar" class="hover:text-[#5C8D59] transition-colors text-decoration-none">Siaran Pers & Investigasi</a></li>
                    <li><a href="{{ route('blog') }}" class="hover:text-[#5C8D59] transition-colors text-decoration-none">Berita & Artikel</a></li>
                    <li><a href="{{ route('laporan-tahunan') }}" class="hover:text-[#5C8D59] transition-colors text-decoration-none">Laporan Tahunan</a></li>
                    <li><a href="{{ route('kertas-posisi') }}" class="hover:text-[#5C8D59] transition-colors text-decoration-none">Kertas Posisi</a></li>
                    <li><a href="{{ route('pengaduan') }}" class="hover:text-[#5C8D59] transition-colors text-decoration-none font-bold text-[#D95C3F]">Pengaduan Kasus</a></li>
                </ul>
            </div>

            <!-- Kolom Jaringan -->
            <div class="md:col-span-3 flex flex-col gap-4">
                <div class="pb-2 border-[#256D4A] border-b-2">
                    <h4 class="text-lg sm:text-xl font-heading font-bold tracking-wider text-[#5C8D59] uppercase">Jaringan</h4>
                </div>
                <ul class="flex flex-col gap-3 text-sm md:text-base text-[#F4F1EA]/80 font-sans">
                    <li><a href="https://www.walhi.or.id" target="_blank" class="hover:text-[#5C8D59] transition-colors text-decoration-none">WALHI Nasional</a></li>
                    <li><a href="https://www.foei.org" target="_blank" class="hover:text-[#5C8D59] transition-colors text-decoration-none">Friends of The Earth (FoE)</a></li>
                </ul>
            </div>
        </div>

        <!-- Middle Section: Langganan Nawala -->
        <div class="bg-[#256D4A] p-4 sm:p-6 md:p-8 flex flex-col md:flex-row justify-between items-center gap-6 border-4 border-[#1D1D1D] shadow-[8px_8px_0px_0px_#F4F1EA]">
            <div class="flex flex-col gap-2 max-w-2xl w-full">
                <h4 class="text-2xl md:text-3xl font-heading font-extrabold uppercase tracking-wide text-[#F4F1EA]">Berlangganan Nawala</h4>
                <p class="text-sm md:text-base text-[#F4F1EA]/90 font-sans">Dapatkan update berkala tentang isu lingkungan hidup, investigasi, kampanye, dan aksi-aksi WALHI Jawa Barat.</p>
                @if(session('subscribe_success'))
                    <div class="text-xs font-semibold text-[#1D1D1D] bg-[#F4F1EA] py-1.5 px-3 border border-[#1D1D1D] inline-block mt-2 self-start w-fit">
                        {{ session('subscribe_success') }}
                    </div>
                @endif
            </div>
            <form action="{{ route('newsletter.subscribe') }}" method="POST" class="flex flex-col gap-3 w-full md:w-auto">
                @csrf
                <!-- Honeypot -->
                <input type="text" name="extra_name" style="display: none !important;" tabindex="-1" autocomplete="off" />

                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="relative flex-grow sm:w-64">
                        <input type="email" name="email" placeholder="Email kamu" autocomplete="email" class="w-full py-3 pl-12 pr-4 bg-white text-[#1D1D1D] border-2 border-transparent focus:border-[#D95C3F] outline-none font-sans" required />
                        <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-4 top-1/2 -translate-y-1/2 text-[#5C8D59] w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                    </div>
                    <button type="submit" class="bg-[#D95C3F] hover:bg-[#c44e32] transition-colors text-white font-bold uppercase tracking-wider px-8 py-3 whitespace-nowrap font-sans text-sm border-2 border-transparent hover:border-[#1D1D1D]">Berlangganan</button>
                </div>
                {{-- Own row so the widget never squeezes the input and button. --}}
                <x-turnstile />
            </form>
        </div>

        <!-- Bottom Section: Hak Cipta & Link Tambahan -->
        <div class="border-t-2 border-[#256D4A] pt-6 flex flex-col md:flex-row justify-between items-center gap-4 text-xs md:text-sm text-[#F4F1EA]/80 font-sans">
            <p class="text-center md:text-left">© 2026 WALHI Jawa Barat. Organisasi Independen. Tidak Berafiliasi dengan Korporasi atau Pemerintah.</p>
            <div class="flex gap-6 uppercase tracking-wider font-semibold">
                <a href="{{ route('privacy') }}" class="hover:text-[#5C8D59] transition-colors text-decoration-none">Kebijakan Privasi</a>
                <a href="{{ route('transparency') }}" class="hover:text-[#5C8D59] transition-colors text-decoration-none">Transparansi Dana</a>
            </div>
        </div>
    </div>
</footer>
