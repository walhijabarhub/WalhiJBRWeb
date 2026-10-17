<header style="width: 100%; z-index: 1000; position: -webkit-sticky; position: sticky; top: 0;">
    <style>
        @media (max-width: 1024px) {
            .desktop-nav {
                display: none !important;
            }
            .desktop-icons {
                display: none !important;
            }
            .mobile-menu-btn {
                display: flex !important;
            }
            .topbar-socials {
                display: none !important;
            }
            .topbar-wrapper {
                padding: 0 16px !important;
                justify-content: center !important;
            }
            .nav-container-inner {
                padding: 0 16px !important;
            }
        }
        @media (min-width: 1025px) {
            .mobile-menu-btn {
                display: none !important;
            }
            .mobile-menu-overlay {
                display: none !important;
            }
        }
    </style>
 
    @php
        $isHome = request()->routeIs('home');
        $isBlog = request()->routeIs('blog');
        $isAbout = request()->routeIs('about') || request()->routeIs('kontak');
        $isRegulasi = request()->routeIs('regulasi');
        $isPublikasi = request()->routeIs('siaran-pers') || request()->routeIs('infografis') || request()->routeIs('laporan-tahunan') || request()->routeIs('kertas-posisi') || request()->routeIs('catatan-kritis');
        $isDonasi = request()->routeIs('donasi');
        $isPengaduan = request()->routeIs('pengaduan');
 
        $navLinkStyle = function (bool $active): string {
            return 'color: '.($active ? '#256D4A' : '#1D1D1D').'; font-size: 14px; font-family: Aspekta, sans-serif; font-weight: 700; text-transform: uppercase; letter-spacing: 0.70px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: color 0.2s;';
        };
    @endphp
 
    <!-- Top Bar -->
    <div style="width: 100%; height: 41px; background: #1D1D1D; border-bottom: 1px #256D4A solid; display: flex; justify-content: center; overflow: hidden;">
        <div class="topbar-wrapper" style="width: 100%; max-width: 1280px; height: 100%; padding: 0 32px; box-sizing: border-box; display: flex; justify-content: space-between; align-items: center; overflow: hidden;">
            <!-- Left Side -->
            <div style="color: #F4F1EA; font-size: 12px; font-family: Montserrat, sans-serif; font-weight: 500; display: flex; align-items: center; gap: 10px; min-width: 0; max-width: 100%; overflow: hidden; white-space: nowrap;">
                <a href="{{ $globalCampaign->url }}" style="color: #F4F1EA; text-decoration: none; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" class="hover:text-[#5C8D59] transition-colors truncate">
                    {{ $globalCampaign->title }}
                </a>
                <span style="color: #256D4A; font-size: 14px; user-select: none; flex-shrink: 0;">•</span>
                <a href="{{ route('pengaduan') }}" style="color: #D95C3F; text-decoration: none; font-weight: 700; white-space: nowrap; flex-shrink: 0;" class="hover:text-white transition-colors">
                    📢 Pengaduan Kasus
                </a>
            </div>
            <!-- Right Side -->
            <div class="topbar-socials" style="display: flex; align-items: center; gap: 14px; flex-shrink: 0;">
                <a href="https://x.com/walhijabar" target="_blank" style="color: #F4F1EA; font-size: 12px; font-family: Montserrat, sans-serif; text-decoration: none; opacity: 0.8; transition: opacity 0.2s;" data-hover-style="opacity:1" data-rest-style="opacity:0.8">X (@walhijabar)</a>
                <span style="color: #256D4A; font-size: 16px; font-family: Montserrat, sans-serif; user-select: none;">|</span>
                <a href="{{ $globalContact->facebook }}" target="_blank" style="color: #F4F1EA; font-size: 12px; font-family: Montserrat, sans-serif; text-decoration: none; opacity: 0.8; transition: opacity 0.2s;" data-hover-style="opacity:1" data-rest-style="opacity:0.8">Facebook</a>
                <span style="color: #256D4A; font-size: 16px; font-family: Montserrat, sans-serif; user-select: none;">|</span>
                <a href="{{ $globalContact->instagram }}" target="_blank" style="color: #F4F1EA; font-size: 12px; font-family: Montserrat, sans-serif; text-decoration: none; opacity: 0.8; transition: opacity 0.2s;" data-hover-style="opacity:1" data-rest-style="opacity:0.8">Instagram</a>
                <span style="color: #256D4A; font-size: 16px; font-family: Montserrat, sans-serif; user-select: none;">|</span>
                <a href="{{ $globalContact->youtube }}" target="_blank" style="color: #F4F1EA; font-size: 12px; font-family: Montserrat, sans-serif; text-decoration: none; opacity: 0.8; transition: opacity 0.2s;" data-hover-style="opacity:1" data-rest-style="opacity:0.8">YouTube</a>
                <span style="color: #256D4A; font-size: 16px; font-family: Montserrat, sans-serif; user-select: none;">|</span>
                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $globalContact->whatsapp) }}" target="_blank" style="color: #F4F1EA; font-size: 12px; font-family: Montserrat, sans-serif; text-decoration: none; opacity: 0.8; transition: opacity 0.2s;" data-hover-style="opacity:1" data-rest-style="opacity:0.8">WA: +62 821-1982-1159</a>
            </div>
        </div>
    </div>
 
    <!-- Main Navigation Bar -->
    <div style="width: 100%; height: 80px; background: #F4F1EA; box-shadow: 0px 1px 2px -1px rgba(0, 0, 0, 0.10), 0px 1px 3px rgba(0, 0, 0, 0.10); display: flex; justify-content: center;">
        <div class="nav-container-inner" style="width: 100%; max-width: 1280px; height: 100%; padding: 0 32px; box-sizing: border-box; display: flex; justify-content: space-between; align-items: center;">
            <!-- Logo -->
            <div style="height: 48px; display: flex; align-items: center;">
                <a href="{{ route('home') }}" style="display: block; height: 48px;">
                    <img src="{{ asset('assets/images/resources/logo-2-walhi.png') }}" alt="WALHI Jawa Barat" style="height: 48px; object-fit: contain;" />
                </a>
            </div>

            <!-- Navigation Links (Desktop) -->
            <nav class="desktop-nav" style="display: flex; align-items: center; justify-content: space-between; width: 620px; height: 37px; position: relative;">
                <!-- BERANDA -->
                <div style="position: relative; height: 100%; display: inline-flex; align-items: center;">
                    <a href="{{ route('home') }}" style="{{ $navLinkStyle($isHome) }}">BERANDA</a>
                    @if ($isHome)
                        <div style="width: 100%; height: 2px; left: 0; bottom: 0; position: absolute; background: #256D4A;"></div>
                    @endif
                </div>

                <!-- BLOG -->
                <div style="position: relative; height: 100%; display: inline-flex; align-items: center;">
                    <a href="{{ route('blog') }}" style="{{ $navLinkStyle($isBlog) }}">BLOG</a>
                    @if ($isBlog)
                        <div style="width: 100%; height: 2px; left: 0; bottom: 0; position: absolute; background: #256D4A;"></div>
                    @endif
                </div>

                <!-- REGULASI -->
                <div style="position: relative; height: 100%; display: inline-flex; align-items: center;">
                    <a href="{{ route('regulasi') }}" style="{{ $navLinkStyle($isRegulasi) }}">REGULASI</a>
                    @if ($isRegulasi)
                        <div style="width: 100%; height: 2px; left: 0; bottom: 0; position: absolute; background: #256D4A;"></div>
                    @endif
                </div>

                <!-- PUBLIKASI (with details/summary dropdown) -->
                <details class="site-nav-dropdown" style="position: relative; height: 100%; display: inline-flex; align-items: center; cursor: pointer;">
                    <summary style="display: inline-flex; align-items: center; gap: 6px; position: relative !important; color: {{ $isPublikasi ? '#256D4A' : '#1D1D1D' }}; font-size: 14px; font-family: Aspekta, sans-serif; font-weight: 700; text-transform: uppercase; letter-spacing: 0.70px; list-style: none; outline: none;">
                        <span>PUBLIKASI</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" style="margin-top: 1px;"><path d="m6 9 6 6 6-6"/></svg>
                    </summary>
                    <div class="site-nav-dropdown-panel" style="width: 440px; left: -140px; top: 37px; position: absolute; background: #F4F1EA; border-top: 4px solid #256D4A; box-shadow: 0 18px 40px rgba(0, 0, 0, 0.14); z-index: 30;">
                        <a href="{{ route('siaran-pers') }}" style="display: flex; align-items: center; min-height: 56px; padding: 0 24px; color: #1D1D1D; font-size: 18px; font-family: Aspekta, sans-serif; font-weight: 700; line-height: 24px; text-decoration: none; border-bottom: 1px solid rgba(37, 109, 74, 0.18); background: {{ request()->routeIs('siaran-pers') ? '#FFFFFF' : '#F4F1EA' }}; transition: background 0.2s;" data-hover-style="background:#FFFFFF" data-rest-style="background:{{ request()->routeIs('siaran-pers') ? '#FFFFFF' : '#F4F1EA' }}">Siaran Pers</a>
                        <a href="{{ route('laporan-tahunan') }}" style="display: flex; align-items: center; min-height: 56px; padding: 0 24px; color: #1D1D1D; font-size: 18px; font-family: Aspekta, sans-serif; font-weight: 700; line-height: 24px; text-decoration: none; border-bottom: 1px solid rgba(37, 109, 74, 0.18); background: {{ request()->routeIs('laporan-tahunan') ? '#FFFFFF' : '#F4F1EA' }}; transition: background 0.2s;" data-hover-style="background:#FFFFFF" data-rest-style="background:{{ request()->routeIs('laporan-tahunan') ? '#FFFFFF' : '#F4F1EA' }}">Laporan Tahunan</a>
                        <a href="{{ route('infografis') }}" style="display: flex; align-items: center; min-height: 56px; padding: 0 24px; color: #1D1D1D; font-size: 18px; font-family: Aspekta, sans-serif; font-weight: 700; line-height: 24px; text-decoration: none; border-bottom: 1px solid rgba(37, 109, 74, 0.18); background: {{ request()->routeIs('infografis') ? '#FFFFFF' : '#F4F1EA' }}; transition: background 0.2s;" data-hover-style="background:#FFFFFF" data-rest-style="background:{{ request()->routeIs('infografis') ? '#FFFFFF' : '#F4F1EA' }}">Infografis</a>
                        <a href="{{ route('kertas-posisi') }}" style="display: flex; align-items: center; min-height: 56px; padding: 0 24px; color: #1D1D1D; font-size: 18px; font-family: Aspekta, sans-serif; font-weight: 700; line-height: 24px; text-decoration: none; border-bottom: 1px solid rgba(37, 109, 74, 0.18); background: {{ request()->routeIs('kertas-posisi') ? '#FFFFFF' : '#F4F1EA' }}; transition: background 0.2s;" data-hover-style="background:#FFFFFF" data-rest-style="background:{{ request()->routeIs('kertas-posisi') ? '#FFFFFF' : '#F4F1EA' }}">Kertas Posisi</a>
                        <a href="{{ route('catatan-kritis') }}" style="display: flex; align-items: center; min-height: 56px; padding: 0 24px; color: #1D1D1D; font-size: 18px; font-family: Aspekta, sans-serif; font-weight: 700; line-height: 24px; text-decoration: none; background: {{ request()->routeIs('catatan-kritis') ? '#FFFFFF' : '#F4F1EA' }}; transition: background 0.2s;" data-hover-style="background:#FFFFFF" data-rest-style="background:{{ request()->routeIs('catatan-kritis') ? '#FFFFFF' : '#F4F1EA' }}">Catatan Kritis</a>
                    </div>
                    @if ($isPublikasi)
                        <div style="width: 100%; height: 2px; left: 0; bottom: 0; position: absolute; background: #256D4A;"></div>
                    @endif
                </details>

                <!-- DUKUNG KAMI -->
                <div style="position: relative; height: 100%; display: inline-flex; align-items: center;">
                    <a href="{{ route('donasi') }}" style="{{ $navLinkStyle($isDonasi) }}">
                        <span>DUKUNG KAMI</span>
                    </a>
                    @if ($isDonasi)
                        <div style="width: 100%; height: 2px; left: 0; bottom: 0; position: absolute; background: #256D4A;"></div>
                    @endif
                </div>

                <!-- TENTANG KAMI (with details/summary dropdown) -->
                <details class="site-nav-dropdown" style="position: relative; height: 100%; display: inline-flex; align-items: center; cursor: pointer;">
                    <summary style="display: inline-flex; align-items: center; gap: 6px; position: relative !important; color: {{ $isAbout ? '#256D4A' : '#1D1D1D' }}; font-size: 14px; font-family: Aspekta, sans-serif; font-weight: 700; text-transform: uppercase; letter-spacing: 0.70px; list-style: none; outline: none;">
                        <span>TENTANG KAMI</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" style="margin-top: 1px;"><path d="m6 9 6 6 6-6"/></svg>
                    </summary>
                    <div class="site-nav-dropdown-panel" style="width: 492px; left: -340px; top: 37px; position: absolute; background: #F4F1EA; border-top: 4px solid #256D4A; box-shadow: 0 18px 40px rgba(0, 0, 0, 0.14); z-index: 30;">
                        <a href="{{ route('about') }}" style="display: flex; align-items: center; min-height: 74px; padding: 0 32px; color: #1D1D1D; font-size: 24px; font-family: Aspekta, sans-serif; font-weight: 700; line-height: 32px; text-decoration: none; border-bottom: 1px solid rgba(37, 109, 74, 0.18); background: {{ request()->routeIs('about') ? '#FFFFFF' : '#F4F1EA' }}; transition: background 0.2s;" data-hover-style="background:#FFFFFF" data-rest-style="background:{{ request()->routeIs('about') ? '#FFFFFF' : '#F4F1EA' }}">Profil & Sejarah</a>
                        <a href="{{ route('kontak') }}" style="display: flex; align-items: center; min-height: 74px; padding: 0 32px; color: #1D1D1D; font-size: 24px; font-family: Aspekta, sans-serif; font-weight: 700; line-height: 32px; text-decoration: none; background: {{ request()->routeIs('kontak') ? '#FFFFFF' : '#F4F1EA' }}; transition: background 0.2s;" data-hover-style="background:#FFFFFF" data-rest-style="background:{{ request()->routeIs('kontak') ? '#FFFFFF' : '#F4F1EA' }}">Kontak Kami</a>
                    </div>
                    @if ($isAbout)
                        <div style="width: 100%; height: 2px; left: 0; bottom: 0; position: absolute; background: #256D4A;"></div>
                    @endif
                </details>
            </nav>

            <!-- Icons (Globe, Search) (Desktop) -->
            <div class="desktop-icons" style="display: flex; align-items: center; gap: 20px; color: #1D1D1D; position: relative;">
                <!-- Language Selector / Globe (ID) -->
                <div style="position: relative;">
                    <button type="button" id="lang-toggle-btn" title="Pilih Bahasa / Language Selector" style="background: transparent; border: none; cursor: pointer; color: #1D1D1D; display: flex; align-items: center; justify-content: center; transition: color 0.2s; padding: 4px;" data-hover-style="color:#256D4A" data-rest-style="color:#1D1D1D">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/></svg>
                    </button>
                    <div id="lang-dropdown" style="display: none; position: absolute; right: 0; top: 32px; background: #FFFFFF; border: 2px solid #1D1D1D; box-shadow: 4px 4px 0px 0px #1D1D1D; z-index: 50; width: 175px; flex-direction: column;">
                        <span style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; font-size: 13px; font-weight: 700; font-family: Montserrat, sans-serif; color: #256D4A; background: #F4F1EA; border-bottom: 1px solid #1D1D1D;">
                            <span>🇮🇩 Indonesia</span>
                            <span style="font-size: 10px; background: #256D4A; color: white; padding: 2px 6px; font-weight: 700;">AKTIF</span>
                        </span>
                        <span style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; font-size: 13px; font-weight: 600; font-family: Montserrat, sans-serif; color: #888; cursor: not-allowed; opacity: 0.65;" title="Bahasa Inggris sedang disiapkan / In preparation">
                            <span>🇬🇧 English</span>
                            <span style="font-size: 9px; background: #e5e5e5; color: #666; padding: 2px 5px; font-weight: 700; text-transform: uppercase;">SEGERA</span>
                        </span>
                    </div>
                </div>

                <!-- Search Button -->
                <button type="button" id="search-toggle-btn" title="Cari Berita & Regulasi" style="background: transparent; border: none; cursor: pointer; color: #1D1D1D; display: flex; align-items: center; justify-content: center; transition: color 0.2s; padding: 4px;" data-hover-style="color:#256D4A" data-rest-style="color:#1D1D1D">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                </button>
            </div>

            <!-- Quick Search Bar Overlay (Desktop) -->
            <div id="desktop-search-bar" style="display: none; position: absolute; top: 100%; left: 0; width: 100%; background: #1D1D1D; border-bottom: 4px solid #256D4A; padding: 16px 32px; box-sizing: border-box; z-index: 100; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
                <div style="max-width: 1280px; margin: 0 auto; display: flex; align-items: center; gap: 16px;">
                    <form action="{{ route('blog') }}" method="GET" style="display: flex; flex: 1; align-items: center; gap: 12px;">
                        <input type="text" name="search" id="desktop-search-input" placeholder="Ketik kata kunci pencarian berita, advokasi, atau laporan..." autocomplete="off" style="flex: 1; height: 46px; background: #FFFFFF; border: 2px solid #256D4A; padding: 0 16px; font-family: Montserrat, sans-serif; font-size: 15px; color: #1D1D1D; outline: none;" />
                        <button type="submit" style="height: 46px; background: #256D4A; color: #F4F1EA; font-weight: 700; font-family: Aspekta, sans-serif; text-transform: uppercase; letter-spacing: 0.5px; border: none; padding: 0 24px; cursor: pointer;">CARI</button>
                    </form>
                    <button type="button" id="desktop-search-close" style="background: transparent; border: none; color: #F4F1EA; font-size: 24px; cursor: pointer; padding: 4px 8px; line-height: 1;">&times;</button>
                </div>
            </div>

            <!-- Hamburger menu button (Mobile only) -->
            <button id="mobile-menu-toggle" class="mobile-menu-btn" style="display: none; width: 40px; height: 40px; align-items: center; justify-content: center; background: transparent; border: none; cursor: pointer; color: #1D1D1D; outline: none;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
        </div>
    </div>
 
    <!-- Mobile Menu Overlay -->
    <div id="mobile-menu-overlay" class="mobile-menu-overlay" style="display: none; position: fixed; inset: 0; background: #F4F1EA; z-index: 9999; flex-direction: column; padding: 40px 32px; gap: 24px; box-sizing: border-box; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #1D1D1D; padding-bottom: 16px;">
            <img src="{{ asset('assets/images/resources/logo-2-walhi.png') }}" alt="WALHI Jawa Barat" style="height: 40px; object-fit: contain;" />
            <button id="mobile-menu-close" style="background: transparent; border: none; cursor: pointer; color: #1D1D1D; outline: none; padding: 0;">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <nav style="display: flex; flex-direction: column; gap: 20px; font-family: Aspekta, sans-serif; font-size: 20px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">
            <a href="{{ route('home') }}" class="mobile-nav-link" style="color: {{ $isHome ? '#256D4A' : '#1D1D1D' }}; text-decoration: none;">BERANDA</a>
            <a href="{{ route('blog') }}" class="mobile-nav-link" style="color: {{ $isBlog ? '#256D4A' : '#1D1D1D' }}; text-decoration: none;">BLOG</a>
            <a href="{{ route('regulasi') }}" class="mobile-nav-link" style="color: {{ $isRegulasi ? '#256D4A' : '#1D1D1D' }}; text-decoration: none;">REGULASI</a>
            
            <div style="display: flex; flex-direction: column; gap: 10px; border-top: 1px solid rgba(0,0,0,0.1); padding-top: 15px;">
                <span style="font-size: 13px; color: #666; font-family: Montserrat, sans-serif; font-weight: 700; letter-spacing: 0.5px;">PUBLIKASI</span>
                <a href="{{ route('siaran-pers') }}" class="mobile-nav-link" style="color: {{ request()->routeIs('siaran-pers') ? '#256D4A' : '#1D1D1D' }}; text-decoration: none; padding-left: 12px; font-size: 18px;">Siaran Pers</a>
                <a href="{{ route('laporan-tahunan') }}" class="mobile-nav-link" style="color: {{ request()->routeIs('laporan-tahunan') ? '#256D4A' : '#1D1D1D' }}; text-decoration: none; padding-left: 12px; font-size: 18px;">Laporan Tahunan</a>
                <a href="{{ route('infografis') }}" class="mobile-nav-link" style="color: {{ request()->routeIs('infografis') ? '#256D4A' : '#1D1D1D' }}; text-decoration: none; padding-left: 12px; font-size: 18px;">Infografis</a>
                <a href="{{ route('kertas-posisi') }}" class="mobile-nav-link" style="color: {{ request()->routeIs('kertas-posisi') ? '#256D4A' : '#1D1D1D' }}; text-decoration: none; padding-left: 12px; font-size: 18px;">Kertas Posisi</a>
                <a href="{{ route('catatan-kritis') }}" class="mobile-nav-link" style="color: {{ request()->routeIs('catatan-kritis') ? '#256D4A' : '#1D1D1D' }}; text-decoration: none; padding-left: 12px; font-size: 18px;">Catatan Kritis</a>
            </div>
 
            <a href="{{ route('donasi') }}" class="mobile-nav-link" style="color: {{ $isDonasi ? '#256D4A' : '#D95C3F' }}; text-decoration: none; border-top: 1px solid rgba(0,0,0,0.1); padding-top: 15px; font-weight: 700;">DUKUNG KAMI</a>
            <a href="{{ route('pengaduan') }}" class="mobile-nav-link" style="color: {{ request()->routeIs('pengaduan') ? '#256D4A' : '#D95C3F' }}; text-decoration: none; font-weight: 700;">📢 PENGADUAN KASUS</a>
            <div style="display: flex; flex-direction: column; gap: 10px; border-top: 1px solid rgba(0,0,0,0.1); padding-top: 15px;">
                <span style="font-size: 13px; color: #666; font-family: Montserrat, sans-serif; font-weight: 700; letter-spacing: 0.5px;">TENTANG KAMI</span>
                <a href="{{ route('about') }}" class="mobile-nav-link" style="color: {{ request()->routeIs('about') ? '#256D4A' : '#1D1D1D' }}; text-decoration: none; padding-left: 12px; font-size: 18px;">Profil & Sejarah</a>
                <a href="{{ route('kontak') }}" class="mobile-nav-link" style="color: {{ request()->routeIs('kontak') ? '#256D4A' : '#1D1D1D' }}; text-decoration: none; padding-left: 12px; font-size: 18px;">Kontak Kami</a>
            </div>
        </nav>
    </div>
 
    <script nonce="{{ Vite::cspNonce() }}">
        document.addEventListener('DOMContentLoaded', () => {
            // Mobile Menu
            const toggleBtn = document.getElementById('mobile-menu-toggle');
            const closeBtn = document.getElementById('mobile-menu-close');
            const overlay = document.getElementById('mobile-menu-overlay');
 
            if (toggleBtn && closeBtn && overlay) {
                toggleBtn.addEventListener('click', () => {
                    overlay.style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                });
 
                const closeMenu = () => {
                    overlay.style.display = 'none';
                    document.body.style.overflow = '';
                };

                closeBtn.addEventListener('click', closeMenu);
                overlay.querySelectorAll('.mobile-nav-link').forEach(link => {
                    link.addEventListener('click', closeMenu);
                });
            }

            // Desktop Details Dropdowns Auto-Close
            const dropdowns = document.querySelectorAll('details.site-nav-dropdown');
            dropdowns.forEach(dd => {
                dd.querySelectorAll('a').forEach(link => {
                    link.addEventListener('click', () => {
                        dd.removeAttribute('open');
                    });
                });
            });

            document.addEventListener('click', (e) => {
                dropdowns.forEach(dd => {
                    if (dd.hasAttribute('open') && !dd.contains(e.target)) {
                        dd.removeAttribute('open');
                    }
                });

                // Language dropdown close
                const langDropdown = document.getElementById('lang-dropdown');
                const langBtn = document.getElementById('lang-toggle-btn');
                if (langDropdown && langBtn && !langBtn.contains(e.target) && !langDropdown.contains(e.target)) {
                    langDropdown.style.display = 'none';
                }
            });

            // Language Toggle
            const langBtn = document.getElementById('lang-toggle-btn');
            const langDropdown = document.getElementById('lang-dropdown');
            if (langBtn && langDropdown) {
                langBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    langDropdown.style.display = langDropdown.style.display === 'flex' ? 'none' : 'flex';
                });
            }

            // Search Bar Toggle
            const searchBtn = document.getElementById('search-toggle-btn');
            const searchBar = document.getElementById('desktop-search-bar');
            const searchClose = document.getElementById('desktop-search-close');
            const searchInput = document.getElementById('desktop-search-input');

            if (searchBtn && searchBar) {
                searchBtn.addEventListener('click', () => {
                    searchBar.style.display = searchBar.style.display === 'block' ? 'none' : 'block';
                    if (searchBar.style.display === 'block' && searchInput) {
                        setTimeout(() => searchInput.focus(), 100);
                    }
                });

                if (searchClose) {
                    searchClose.addEventListener('click', () => {
                        searchBar.style.display = 'none';
                    });
                }
            }
        });
    </script>
</header>
