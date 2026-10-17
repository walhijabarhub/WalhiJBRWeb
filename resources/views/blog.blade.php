<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @include('partials.seo-meta', ['title' => 'Blog - WALHI Jawa Barat'])

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,600&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="{{ asset('assets/fonts/webfonts/font-face.css') }}">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body style="width: 100%; background: #F4F1EA; margin: 0; overflow-x: clip; color: #1D1D1D; font-family: Montserrat, sans-serif;">
        @php
            $blogCategories = ['Semua', 'Investigasi', 'Advokasi', 'Laporan', 'Kampanye', 'Pendidikan', 'Opini'];
            $featuredNews = ($items->currentPage() === 1) ? $items->first() : null;
            $newsCards = ($items->currentPage() === 1) ? $items->skip(1) : $items;
        @endphp
 
        <div style="position: relative; width: 100%; overflow-x: clip; background: #F4F1EA;">
            @include('partials.site-header')
 
            <main style="display: flex; flex-direction: column; align-items: stretch;">
                <!-- Hero Section -->
                <section style="background: #1D1D1D; border-bottom: 4px #256D4A solid; color: #F4F1EA;" class="py-12 md:py-16">
                    <div class="w-full max-w-5xl mx-auto px-4 sm:px-8">
                        <div style="display: flex; flex-direction: column; gap: 24px; max-width: 860px; width: 100%;">
                            <div style="display: inline-flex; width: fit-content; padding: 4px 16px; background: #256D4A; color: #F4F1EA; font-size: 12px; font-weight: 700; line-height: 18px; letter-spacing: 0.7px; text-transform: uppercase;">
                                Blog
                            </div>
                            <h1 style="margin: 0; max-width: 760px; color: #F4F1EA; font-size: clamp(48px, 6.5vw, 76px); font-family: Aspekta, sans-serif; font-weight: 800; line-height: 1.05; letter-spacing: 1px; text-transform: uppercase;">
                                Liputan, Advokasi,<br>
                                dan Catatan Lapangan
                            </h1>
                            <div style="width: 128px; height: 8px; background: #E56A43;"></div>
                            <p style="margin: 0; max-width: 760px; color: #F4F1EA; font-size: 20px; line-height: 32px;">
                                Kumpulan laporan, investigasi, dan pembaruan gerakan WALHI Jawa Barat untuk mengikuti isu lingkungan, keadilan ekologis, dan kerja-kerja advokasi di lapangan.
                            </p>
                        </div>
                    </div>
                </section>
 
                <!-- Content Section -->
                <section id="daftar-artikel" style="background: #F4F1EA; color: #1D1D1D; border-bottom: 4px #1D1D1D solid;" class="py-16 md:py-20 scroll-mt-12">
                    <div class="w-full max-w-5xl mx-auto px-4 sm:px-8 flex flex-col gap-10">
                        <!-- Archive Status / Information Banner (QA-016) -->
                        <div style="background: white; border: 2px solid #1D1D1D; box-shadow: 4px 4px 0px 0px #256D4A; padding: 14px 18px; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; font-family: Montserrat, sans-serif;">
                            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                                <span style="background: #256D4A; color: white; font-size: 11px; font-weight: 700; padding: 4px 10px; text-transform: uppercase; letter-spacing: 0.5px;">Arsip Dokumentasi</span>
                                <span style="font-size: 13px; color: #1D1D1D; font-weight: 500;">Kanal Blog memuat arsip opini, riset, dan analisis mendalam. Untuk rilis sikap dan pembaruan cepat terkini, buka <a href="{{ route('siaran-pers') }}" style="color: #256D4A; font-weight: 700; text-decoration: underline;">Siaran Pers Resmi &rarr;</a></span>
                            </div>
                        </div>

                        <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: flex-start;">
                            @foreach ($blogCategories as $category)
                                @php
                                    $isActive = ($categoryFilter ?? 'Semua') === $category;
                                    $categoryParams = $category === 'Semua' ? request()->except(['kategori', 'page']) : array_merge(request()->except('page'), ['kategori' => $category]);
                                    $url = route('blog', $categoryParams) . '#daftar-artikel';
                                @endphp
                                <a href="{{ $url }}" style="padding: 12px 18px; border: 2px solid {{ $isActive ? '#256D4A' : '#1D1D1D' }}; background: {{ $isActive ? '#256D4A' : '#F4F1EA' }}; color: {{ $isActive ? '#F4F1EA' : '#1D1D1D' }}; font-size: 12px; font-weight: 700; line-height: 18px; letter-spacing: 0.8px; text-transform: uppercase; cursor: pointer; text-decoration: none; display: inline-block;">
                                    {{ $category }}
                                </a>
                            @endforeach
                        </div>
 
                        @if($featuredNews)
                        @php
                            $featImage = $featuredNews->image_url ?: asset('assets/images/blog/news-4-1.jpg');
                            $featTag = array_map('trim', explode(',', $featuredNews->tags ?? ''))[0] ?? 'Liputan';
                            $wordCount = str_word_count(strip_tags($featuredNews->body));
                            $featRead = ceil($wordCount / 200) . ' menit';
                        @endphp
                        <article style="display: flex; flex-wrap: wrap; overflow: hidden; border: 4px solid #1D1D1D; background: #FFFFFF; min-height: 348px;">
                            <div style="position: relative; flex: 1 1 420px; min-height: 320px; background-image: url('{{ $featImage }}'); background-size: cover; background-position: center;">
                                <div style="position: absolute; left: 16px; top: 16px; padding: 8px 16px; background: #E56A43; color: #F4F1EA; font-size: 12px; font-weight: 700; line-height: 18px; letter-spacing: 0.6px; text-transform: uppercase;">
                                    {{ $featTag }}
                                </div>
                            </div>
                            <div style="flex: 1 1 420px; padding: 32px; display: flex; flex-direction: column; justify-content: space-between; gap: 24px;">
                                <div style="display: flex; flex-direction: column; gap: 16px;">
                                    <h2 style="margin: 0; color: #1D1D1D; font-size: 28px; font-family: Aspekta, sans-serif; font-weight: 800; line-height: 1.25; letter-spacing: 0.5px; text-transform: uppercase;">
                                        {{ $featuredNews->title }}
                                    </h2>
                                    @php
                                        $cleanFeat = strip_tags($featuredNews->body);
                                        $wordsArray = preg_split('/\s+/', trim($cleanFeat));
                                        $hasMoreFeat = count($wordsArray) > 13;
                                        if ($hasMoreFeat) {
                                            $featCopyLimited = implode(' ', array_slice($wordsArray, 0, 13)) . '...';
                                        } else {
                                            $featCopyLimited = $cleanFeat;
                                        }
                                    @endphp
                                    <p style="margin: 0; color: #1D1D1D; font-size: 18px; line-height: 28.8px;">
                                        {{ $featCopyLimited }}
                                        @if($hasMoreFeat)
                                            <a href="{{ route('content.show', $featuredNews->slug) }}" style="color: #256D4A; font-weight: 600; text-decoration: underline; margin-left: 4px;">Baca Selengkapnya</a>
                                        @endif
                                    </p>
                                </div>
                                <div style="display: flex; align-items: center; justify-content: space-between; gap: 24px; padding-top: 24px; border-top: 2px solid #1D1D1D; color: #5C8D59; font-size: 14px; font-weight: 600; line-height: 20px;">
                                    <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                                        <span>{{ $featuredNews->publish_date ? \Carbon\Carbon::parse($featuredNews->publish_date)->translatedFormat('d M Y') : $featuredNews->created_at->translatedFormat('d M Y') }}</span>
                                        <span>▪ {{ $featRead }} baca</span>
                                    </div>
                                    <a href="{{ route('content.show', $featuredNews->slug) }}" style="display: inline-flex; align-items: center; gap: 8px; color: #1D1D1D; text-decoration: none; font-weight: 700;">
                                        <span>Baca Selengkapnya</span>
                                        <span style="font-size: 18px; line-height: 1;">›</span>
                                    </a>
                                </div>
                            </div>
                        </article>
                        @endif
 
                        @if($newsCards->count() > 0)
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px;">
                            @foreach ($newsCards as $news)
                                @php
                                    $newsImage = $news->image_url ?: asset('assets/images/blog/news-1-1.jpg');
                                    $newsTag = array_map('trim', explode(',', $news->tags ?? ''))[0] ?? 'Advokasi';
                                    $wordCount = str_word_count(strip_tags($news->body));
                                    $newsRead = ceil($wordCount / 200) . ' menit';
                                @endphp
                                <article style="display: flex; flex-direction: column; overflow: hidden; border: 4px solid #1D1D1D; background: #FFFFFF; min-height: 454px;">
                                    <div style="position: relative; height: 192px; background-image: url('{{ $newsImage }}'); background-size: cover; background-position: center;">
                                        <div style="position: absolute; left: 16px; top: 16px; padding: 4px 12px; background: {{ $loop->index % 3 === 2 ? '#8B6B4A' : ($loop->index % 3 === 1 ? '#5C8D59' : '#256D4A') }}; color: #F4F1EA; font-size: 12px; font-weight: 700; line-height: 18px; letter-spacing: 0.6px; text-transform: uppercase;">
                                            {{ $newsTag }}
                                        </div>
                                    </div>
                                    <div style="display: flex; flex: 1 1 0%; flex-direction: column; justify-content: space-between; padding: 24px; gap: 24px;">
                                        <div style="display: flex; flex-direction: column; gap: 16px;">
                                            <h3 style="margin: 0; color: #1D1D1D; font-size: 19px; font-family: Aspekta, sans-serif; font-weight: 700; line-height: 1.3; letter-spacing: 0.3px; text-transform: uppercase;">
                                                <a href="{{ route('content.show', $news->slug) }}" style="color: #1D1D1D; text-decoration: none; transition: color 0.2s;" data-hover-style="color:#256D4A" data-rest-style="color:#1D1D1D">
                                                    {{ $news->title }}
                                                </a>
                                            </h3>
                                            @php
                                                $cleanNews = strip_tags($news->body);
                                                $wordsArray = preg_split('/\s+/', trim($cleanNews));
                                                $hasMoreNews = count($wordsArray) > 13;
                                                if ($hasMoreNews) {
                                                    $newsCopyLimited = implode(' ', array_slice($wordsArray, 0, 13)) . '...';
                                                } else {
                                                    $newsCopyLimited = $cleanNews;
                                                }
                                            @endphp
                                            <p style="margin: 0; color: #1D1D1D; font-size: 15px; line-height: 24px;">
                                                {{ $newsCopyLimited }}
                                                @if($hasMoreNews)
                                                    <a href="{{ route('content.show', $news->slug) }}" style="color: #256D4A; font-weight: 600; text-decoration: underline; margin-left: 4px;">Baca Selengkapnya</a>
                                                @endif
                                            </p>
                                        </div>
                                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; padding-top: 16px; border-top: 2px solid #1D1D1D; color: #5C8D59; font-size: 12px; font-weight: 600; line-height: 16px; flex-wrap: wrap;">
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <span>{{ $news->publish_date ? \Carbon\Carbon::parse($news->publish_date)->translatedFormat('d M Y') : $news->created_at->translatedFormat('d M Y') }}</span>
                                                <span>▪</span>
                                                <span>{{ $newsRead }}</span>
                                            </div>
                                            <a href="{{ route('content.show', $news->slug) }}" style="color: #1D1D1D; text-decoration: none; font-weight: 700;">Detail →</a>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                        @else
                            @if(!$featuredNews)
                            <div style="background: white; border: 4px solid #1D1D1D; padding: 48px; text-align: center; font-size: 18px; color: #888;">
                                Belum ada artikel blog yang diterbitkan dalam kategori ini.
                            </div>
                            @endif
                        @endif
 
                        <!-- Neobrutalist Pagination -->
                        @if($items->hasPages())
                            <div style="display: flex; justify-content: center; align-items: center; gap: 12px; margin-top: 48px;">
                                <a href="{{ $items->previousPageUrl() ? $items->previousPageUrl() . '#daftar-artikel' : '#' }}" style="display: inline-flex; align-items: center; justify-content: center; width: 48px; height: 48px; background: white; border: 2px solid #1D1D1D; color: #1D1D1D; font-weight: 700; text-decoration: none; cursor: pointer; {{ $items->onFirstPage() ? 'opacity: 0.5; pointer-events: none;' : '' }}">
                                    ‹
                                </a>
                                <span style="font-weight: 700; font-size: 16px; color: #1D1D1D;">
                                    Halaman {{ $items->currentPage() }} dari {{ $items->lastPage() }}
                                </span>
                                <a href="{{ $items->nextPageUrl() ? $items->nextPageUrl() . '#daftar-artikel' : '#' }}" style="display: inline-flex; align-items: center; justify-content: center; width: 48px; height: 48px; background: white; border: 2px solid #1D1D1D; color: #1D1D1D; font-weight: 700; text-decoration: none; cursor: pointer; {{ !$items->hasMorePages() ? 'opacity: 0.5; pointer-events: none;' : '' }}">
                                    ›
                                </a>
                            </div>
                        @endif

                    </div>
                </section>
            </main>

            @include('partials.site-footer')
        </div>

        <script nonce="{{ Vite::cspNonce() }}">
            document.addEventListener('DOMContentLoaded', () => {
                const section = document.getElementById('daftar-artikel');
                if (!section) return;

                document.addEventListener('click', (e) => {
                    const link = e.target.closest('#daftar-artikel a[href*="kategori="], #daftar-artikel a[href*="page="], #daftar-artikel a[href$="#daftar-artikel"]');
                    if (!link) return;

                    const url = new URL(link.href, window.location.origin);
                    if (url.origin !== window.location.origin) return;
                    if (url.pathname !== window.location.pathname) return;

                    e.preventDefault();
                    const currentScrollY = window.scrollY;

                    section.style.transition = 'opacity 0.2s ease';
                    section.style.opacity = '0.5';
                    section.style.pointerEvents = 'none';

                    fetch(url.href, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(res => res.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const newSection = doc.getElementById('daftar-artikel');
                        if (newSection) {
                            section.innerHTML = newSection.innerHTML;
                            history.pushState(null, doc.title || '', url.href);
                            if (doc.title) document.title = doc.title;
                        } else {
                            window.location.href = url.href;
                        }
                    })
                    .catch(() => {
                        window.location.href = url.href;
                    })
                    .finally(() => {
                        section.style.opacity = '1';
                        section.style.pointerEvents = 'auto';
                        window.scrollTo({ top: currentScrollY, behavior: 'instant' });
                    });
                });

                window.addEventListener('popstate', () => {
                    window.location.reload();
                });
            });
        </script>
    </body>
</html>