@extends('layouts.admin')

@section('content')
<div class="space-y-5">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-[#1D1D1D]">{{ $config['title'] }}</h1>
            @if($config['desc'])
                <p class="text-sm text-[#888] mt-1">{{ $config['desc'] }}</p>
            @endif
            <div class="flex gap-4 mt-3 text-xs text-[#888]">
                <span>Total: <strong class="text-[#1D1D1D]">{{ $counts['total'] }}</strong></span>
                <span>Terbit: <strong class="text-[#256D4A]">{{ $counts['published'] }}</strong></span>
                <span>Draf: <strong class="text-[#8B6B4A]">{{ $counts['draft'] }}</strong></span>
                <span>Arsip: <strong class="text-[#888]">{{ $counts['archived'] }}</strong></span>
            </div>
        </div>
    </div>

    <!-- Search & Add Actions -->
    <div>
        <div class="flex flex-col sm:flex-row gap-3 mb-4">
            <form action="{{ request()->url() }}" method="GET" class="relative flex-1">
                <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 text-[#888] w-3.5 h-3.5"></i>
                <input
                    type="text"
                    name="search"
                    placeholder="Cari judul atau tag..."
                    value="{{ request('search') }}"
                    class="w-full pl-8 pr-3 py-2 text-sm border border-[#ddd] rounded bg-white focus:outline-none focus:border-[#256D4A]"
                />
            </form>
            @if(!\App\Models\Content::isSensitiveCategory($category) || auth()->user()->isAdmin())
                <button data-on-click="openAddModal" class="flex items-center gap-2 px-4 py-2 bg-[#256D4A] text-white text-sm font-semibold rounded hover:bg-[#1e5a3d] transition-colors shrink-0">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    Tambah {{ $config['title'] }}
                </button>
            @endif
        </div>

        <!-- Table Container -->
        <div class="bg-white border border-[#ddd] rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-[#f9f8f5] border-b border-[#ddd]">
                            <th class="text-left px-4 py-3 text-xs font-semibold text-[#888] uppercase tracking-wide">Judul</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-[#888] uppercase tracking-wide hidden md:table-cell">
                                @if($category === 'kampanye-darurat')
                                    Tautan Aksi
                                @elseif($category === 'banner')
                                    Tombol Aksi
                                @elseif($category === 'isu-kritis' || $category === 'statistik')
                                    Badge/Ikon
                                @elseif($category === 'regulasi')
                                    Detail Regulasi
                                @else
                                    Tag
                                @endif
                            </th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-[#888] uppercase tracking-wide">Status</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-[#888] uppercase tracking-wide hidden sm:table-cell">Tanggal</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-[#888] uppercase tracking-wide">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#f0ede8]">
                        @if($items->isEmpty())
                            <tr>
                                <td colSpan="5" class="text-center py-10 text-[#888] text-sm">
                                    {{ request('search') ? 'Tidak ada hasil ditemukan.' : 'Belum ada konten. Klik Tambah untuk mulai.' }}
                                </td>
                            </tr>
                        @else
                            @foreach($items as $item)
                                <tr class="hover:bg-[#fafaf8] transition-colors">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            @if($category !== 'laporan-tahunan' && $category !== 'regulasi' && $category !== 'statistik' && $category !== 'isu-kritis' && $category !== 'kampanye-darurat')
                                                @if($item->image_url && !str_ends_with(strtolower($item->image_url), '.pdf') && !str_ends_with(strtolower($item->image_url), '.xls') && !str_ends_with(strtolower($item->image_url), '.xlsx'))
                                                    <div class="w-12 h-12 rounded border border-[#ddd] overflow-hidden flex-shrink-0 bg-gray-100 shadow-sm">
                                                        <img src="{{ $item->image_url }}" alt="" class="w-full h-full object-cover" loading="lazy" decoding="async" />
                                                    </div>
                                                @else
                                                    <div class="w-12 h-12 rounded border border-dashed border-[#ccc] bg-[#f5f3ef] flex items-center justify-center flex-shrink-0 text-[#aaa]" title="Belum ada foto cover">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[#bbb]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                                    </div>
                                                @endif
                                            @endif
                                            <div class="min-w-0 flex-1">
                                                <div class="font-medium text-[#1D1D1D] leading-snug line-clamp-2 max-w-xs">{{ $item->title }}</div>
                                                <div class="text-xs text-[#aaa] mt-0.5">/{{ $item->slug }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 hidden md:table-cell">
                                        @if($category === 'kampanye-darurat')
                                            <a href="{{ $item->tags }}" target="_blank" class="text-[#256D4A] hover:underline text-xs break-all">
                                                {{ $item->tags }}
                                            </a>
                                        @elseif($category === 'banner')
                                            @php
                                                $bParts = explode('|', $item->tags ?? '');
                                                $btn1T = $bParts[0] ?? '';
                                                $btn2T = $bParts[2] ?? '';
                                            @endphp
                                            <div class="flex flex-wrap gap-1">
                                                @if($btn1T)
                                                    <span class="px-1.5 py-0.5 bg-[#f0ede8] text-[#1D1D1D] font-medium text-[10px] rounded border border-[#ddd]">
                                                        {{ $btn1T }}
                                                    </span>
                                                @endif
                                                @if($btn2T)
                                                    <span class="px-1.5 py-0.5 bg-[#256D4A]/10 text-[#256D4A] font-medium text-[10px] rounded border border-[#256D4A]/20">
                                                        {{ $btn2T }}
                                                    </span>
                                                @endif
                                                @if(!$btn1T && !$btn2T)
                                                    <span class="text-xs text-[#aaa] italic">Tombol default</span>
                                                @endif
                                            </div>
                                        @elseif($category === 'isu-kritis')
                                            @php
                                                // Same rules as the home page: "icon|badge", or only a badge with
                                                // a default icon from /iqon/ (Icon-4.svg .. Icon-9.svg).
                                                $parts = explode('|', (string) $item->tags, 2);
                                                $hasIcon = count($parts) === 2 && preg_match('/^Icon-\d+\.svg$/', $parts[0]);
                                                $icon = $hasIcon ? $parts[0] : 'Icon-'.(4 + $loop->index % 6).'.svg';
                                                $badge = count($parts) === 2 ? $parts[1] : $parts[0];
                                            @endphp
                                            <span class="inline-flex items-center gap-1.5">
                                                <img src="{{ asset('iqon/' . $icon) }}" alt="" class="w-4 h-4" />
                                                <span class="px-1.5 py-0.5 bg-[#f0ede8] text-[#666] text-[10px] rounded">{{ $badge }}</span>
                                            </span>
                                        @elseif($category === 'statistik')
                                            <span class="inline-flex items-center gap-1.5">
                                                {{-- The home page picks the icon by position (Icon-10.svg .. Icon-13.svg). --}}
                                                <img src="{{ asset('iqon/Icon-'.(10 + $loop->index % 4).'.svg') }}" alt="" class="w-4 h-4" />
                                                <span class="text-xs text-gray-500 font-mono">{{ $item->tags }}</span>
                                            </span>
                                        @elseif($category === 'regulasi')
                                            <div class="flex flex-wrap gap-1">
                                                @foreach(explode(',', $item->tags) as $tag)
                                                    <span class="px-1.5 py-0.5 bg-[#f0ede8] text-[#666] text-[10px] rounded">
                                                        {{ trim($tag) }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="flex flex-wrap gap-1">
                                                @if($item->tags)
                                                    @foreach(explode(',', $item->tags) as $tag)
                                                        @if(trim($tag))
                                                            <span class="px-1.5 py-0.5 bg-[#f0ede8] text-[#666] text-[10px] rounded">
                                                                {{ trim($tag) }}
                                                            </span>
                                                        @endif
                                                    @endforeach
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($item->status === 'published')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-[#5C8D59]/15 text-[#256D4A]">
                                                Published
                                            </span>
                                        @elseif($item->status === 'draft')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">
                                                Draft
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">
                                                Archived
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs text-[#888] hidden sm:table-cell">
                                        {{ $item->publish_date ? \Carbon\Carbon::parse($item->publish_date)->translatedFormat('d M Y') : $item->created_at->translatedFormat('d M Y') }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            @if(in_array($category, ['blog', 'regulasi', 'siaran-pers', 'infografis', 'laporan-tahunan', 'kertas-posisi', 'newsletter', 'buletin-bumi', 'jurnal']))
                                                <a href="{{ route('content.show', $item->slug) }}" target="_blank" class="p-1.5 rounded hover:bg-[#f4faf6] text-[#256D4A] transition-colors" title="Lihat Publik">
                                                    <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                                                </a>
                                            @endif

                                            @if(!\App\Models\Content::isSensitiveCategory($category) || auth()->user()->isAdmin())
                                                <!-- Toggle Status -->
                                                <form action="{{ route('admin.content' . (request()->is('admin/tentang/*') ? '.tentang' : '') . '.toggle-status', [$category, $item->id]) }}" method="POST">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" title="{{ $item->status === 'published' ? 'Arsipkan' : 'Terbitkan' }}" class="p-1.5 rounded hover:bg-[#eaf4ee] text-[#256D4A] transition-colors">
                                                        <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                                    </button>
                                                </form>

                                                <!-- Edit -->
                                                <button data-on-click="openEditModal" data-item="{{ json_encode($item->toEditorArray()) }}" class="p-1.5 rounded hover:bg-[#f0f5ff] text-[#555] transition-colors" title="Edit">
                                                    <i data-lucide="edit-2" class="w-3.5 h-3.5"></i>
                                                </button>
                                            @else
                                                <span class="text-[11px] text-[#888] italic px-1.5 py-0.5 bg-[#f0ede8] rounded">Hanya Admin</span>
                                            @endif

                                            @if(auth()->user()->isAdmin())
                                                <!-- Delete -->
                                                <form action="{{ route('admin.content' . (request()->is('admin/tentang/*') ? '.tentang' : '') . '.destroy', [$category, $item->id]) }}" method="POST" data-confirm="Hapus &quot;{{ $item->title }}&quot;?">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="p-1.5 rounded hover:bg-[#fdf0ee] text-[#D95C3F] transition-colors">
                                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>

            <!-- Pagination Section -->
            <div class="flex items-center justify-between px-4 py-3 border-t border-[#f0ede8] bg-[#fafaf8]">
                <span class="text-xs text-[#888]">
                    {{ $items->total() }} item · Hal {{ $items->currentPage() }} dari {{ $items->lastPage() }}
                </span>
                <div class="flex gap-1">
                    <a href="{{ $items->previousPageUrl() }}" class="p-1.5 rounded border border-[#ddd] @if($items->onFirstPage()) opacity-40 pointer-events-none @endif hover:bg-[#eee] transition-colors">
                        <i data-lucide="chevron-left" class="w-3.5 h-3.5"></i>
                    </a>
                    <a href="{{ $items->nextPageUrl() }}" class="p-1.5 rounded border border-[#ddd] @if(!$items->hasMorePages()) opacity-40 pointer-events-none @endif hover:bg-[#eee] transition-colors">
                        <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Dialog (Add / Edit) -->
<div id="editor-modal" class="fixed inset-0 bg-black/50 z-50 backdrop-blur-sm hidden flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <!-- Modal Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-[#ddd] sticky top-0 bg-white z-10">
            <h2 id="modal-title" class="font-bold text-[#1D1D1D]">Tambah {{ $config['title'] }}</h2>
            <button data-on-click="closeModal" class="p-1.5 rounded hover:bg-[#f0ede8] transition-colors">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <!-- Modal Form -->
        <form id="modal-form" action="" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
            @csrf
            <div id="method-field"></div>

            <div>
                <label class="block text-xs font-semibold text-[#555] mb-1.5 uppercase tracking-wide">Judul *</label>
                <input type="text" id="form-title" name="title" required class="w-full px-3 py-2 border border-[#ddd] rounded text-sm focus:outline-none focus:border-[#256D4A]" placeholder="Masukkan judul..." />
            </div>

            <div>
                <label class="block text-xs font-semibold text-[#555] mb-1.5 uppercase tracking-wide">Slug URL</label>
                <input type="text" id="form-slug" name="slug" class="w-full px-3 py-2 border border-[#ddd] rounded text-sm font-mono text-[#666] focus:outline-none focus:border-[#256D4A]" placeholder="slug-otomatis" />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-[#555] mb-1.5 uppercase tracking-wide">Status</label>
                    <select id="form-status" name="status" class="w-full px-3 py-2 border border-[#ddd] rounded text-sm focus:outline-none focus:border-[#256D4A] bg-white">
                        <option value="draft">Draf</option>
                        <option value="published">Terbit</option>
                        <option value="archived">Arsip</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-[#555] mb-1.5 uppercase tracking-wide">Tanggal Terbit</label>
                    <input type="date" id="form-date" name="publish_date" class="w-full px-3 py-2 border border-[#ddd] rounded text-sm focus:outline-none focus:border-[#256D4A]" />
                </div>
            </div>

            @if(in_array($category, ['blog', 'siaran-pers', 'infografis', 'kertas-posisi', 'newsletter', 'buletin-bumi', 'jurnal', 'laporan-tahunan', 'regulasi']))
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-[#555] mb-1.5 uppercase tracking-wide">Penulis / Kontributor</label>
                    <input type="text" id="form-author" name="author" class="w-full px-3 py-2 border border-[#ddd] rounded text-sm focus:outline-none focus:border-[#256D4A]" placeholder="Contoh: WALHI Jawa Barat" />
                </div>
                <div class="flex items-center pt-2 sm:pt-6">
                    <label class="inline-flex items-center cursor-pointer select-none">
                        <input type="checkbox" id="form-is-promoted" name="is_promoted" value="1" class="rounded border-gray-300 text-[#256D4A] shadow-sm focus:border-[#256D4A] focus:ring focus:ring-[#256D4A] focus:ring-opacity-50">
                        <span class="ml-2 text-xs font-semibold text-[#555] uppercase tracking-wide">Promosikan / Tampilkan sebagai Hype</span>
                    </label>
                </div>
            </div>
            @endif

            <div>
                <label class="block text-xs font-semibold text-[#555] mb-1.5 uppercase tracking-wide">
                    @if($category === 'laporan-tahunan' || $category === 'regulasi')
                        Berkas / Dokumen Lampiran (PDF / Excel / Image)
                    @elseif($category === 'banner')
                        Gambar Latar Belakang Banner (Rekomendasi: 1920x800 px)
                    @else
                        Gambar Utama / Cover
                    @endif
                </label>
                <input type="hidden" id="form-remove-image" name="remove_image" value="0" />
                <div class="flex items-center gap-3 mb-2 text-xs">
                    <button type="button" data-on-click="setImageInputMode" data-args='["upload"]' id="btn-mode-upload" class="px-2.5 py-1 rounded bg-[#256D4A] text-white font-medium transition-colors">Unggah File</button>
                    <button type="button" data-on-click="setImageInputMode" data-args='["url"]' id="btn-mode-url" class="px-2.5 py-1 rounded bg-[#f0ede8] text-[#666] font-medium transition-colors">Teks URL</button>
                </div>
                
                <div id="container-mode-upload" class="space-y-2">
                    <input type="file" id="form-upload" name="image" @if($category === 'laporan-tahunan' || $category === 'regulasi') accept=".pdf,.xls,.xlsx,.doc,.docx,image/*" @else accept="image/*" @endif data-on-change="previewSelectedImage" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-[#256D4A]/10 file:text-[#256D4A] hover:file:bg-[#256D4A]/20" />
                    <span class="text-[11px] text-gray-500 block">Ukuran berkas maksimal <strong>2 MB</strong> (Format yang didukung: JPG, PNG, WebP, PDF).</span>
                </div>

                <div id="container-mode-url" class="hidden">
                    <input type="text" id="form-image" name="image_url" class="w-full px-3 py-2 border border-[#ddd] rounded text-sm focus:outline-none focus:border-[#256D4A]" placeholder="https://..." />
                </div>

                <div id="image-preview-wrapper" class="hidden mt-3 p-2 border border-[#eee] rounded bg-gray-50 flex items-center gap-3">
                    <img id="image-preview-el" alt="" class="h-16 w-24 object-cover border border-[#ddd] rounded" />
                    <div class="text-xs">
                        <span class="text-[#888] block">Preview File / Gambar</span>
                        <button type="button" data-on-click="clearImagePreview" class="text-[#D95C3F] font-semibold hover:underline">Hapus Berkas</button>
                    </div>
                </div>
            </div>

            @if($category === 'kampanye-darurat')
            <div>
                <label class="block text-xs font-semibold text-[#555] mb-1.5 uppercase tracking-wide">Tautan Aksi Kampanye (Action URL) *</label>
                <input type="text" id="form-tags" name="tags" required class="w-full px-3 py-2 border border-[#ddd] rounded text-sm focus:outline-none focus:border-[#256D4A]" placeholder="Contoh: https://example.com/petisi" />
            </div>
            @elseif($category === 'banner')
            <div class="space-y-3 p-3.5 bg-[#fbfaf8] border border-[#eee] rounded-lg">
                <span class="text-xs font-bold text-[#1D1D1D] uppercase tracking-wide block">Pengaturan Tombol Aksi Banner (CTA)</span>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-[#666] mb-1 uppercase tracking-wide">Tombol 1: Teks Label</label>
                        <input type="text" id="form-banner-btn1-text" name="banner_btn1_text" class="w-full px-3 py-2 border border-[#ddd] rounded text-sm focus:outline-none focus:border-[#256D4A] bg-white" placeholder="Contoh: Isu Strategis" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-[#666] mb-1 uppercase tracking-wide">Tombol 1: Tautan URL</label>
                        <input type="text" id="form-banner-btn1-url" name="banner_btn1_url" class="w-full px-3 py-2 border border-[#ddd] rounded text-sm focus:outline-none focus:border-[#256D4A] bg-white" placeholder="Contoh: #isu atau /tentang-kami" />
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-[#666] mb-1 uppercase tracking-wide">Tombol 2: Teks Label</label>
                        <input type="text" id="form-banner-btn2-text" name="banner_btn2_text" class="w-full px-3 py-2 border border-[#ddd] rounded text-sm focus:outline-none focus:border-[#256D4A] bg-white" placeholder="Contoh: Lihat Publikasi" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-[#666] mb-1 uppercase tracking-wide">Tombol 2: Tautan URL</label>
                        <input type="text" id="form-banner-btn2-url" name="banner_btn2_url" class="w-full px-3 py-2 border border-[#ddd] rounded text-sm focus:outline-none focus:border-[#256D4A] bg-white" placeholder="Contoh: /publikasi/siaran-pers" />
                    </div>
                </div>
                <input type="hidden" id="form-tags" name="tags" />
            </div>
            @elseif($category === 'isu-kritis')
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-[#555] mb-1.5 uppercase tracking-wide">Ikon Isu</label>
                    <select id="form-isu-icon" name="isu_icon" class="w-full px-3 py-2 border border-[#ddd] rounded text-sm focus:outline-none focus:border-[#256D4A] bg-white">
                        <option value="Icon-4.svg">Pohon / Hutan</option>
                        <option value="Icon-5.svg">Air / Sungai</option>
                        <option value="Icon-6.svg">Gunung / Tambang</option>
                        <option value="Icon-7.svg">Pesisir / Laut</option>
                        <option value="Icon-8.svg">Udara / Emisi</option>
                        <option value="Icon-9.svg">Satwa / Hayati</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-[#555] mb-1.5 uppercase tracking-wide">Label Badge *</label>
                    <input type="text" id="form-isu-badge" name="isu_badge" required class="w-full px-3 py-2 border border-[#ddd] rounded text-sm focus:outline-none focus:border-[#256D4A]" placeholder="Contoh: Hutan & Iklim" />
                </div>
            </div>
            @elseif($category === 'statistik')
            <div>
                <label class="block text-xs font-semibold text-[#555] mb-1.5 uppercase tracking-wide">Pilihan Ikon Statistik</label>
                <select id="form-isu-icon" name="isu_icon" class="w-full px-3 py-2 border border-[#ddd] rounded text-sm focus:outline-none focus:border-[#256D4A] bg-white">
                    <option value="Icon-10.svg">Anggota / Komunitas</option>
                    <option value="Icon-11.svg">Hutan Terselamatkan</option>
                    <option value="Icon-12.svg">Advokasi Kasus</option>
                    <option value="Icon-13.svg">Riset & Kajian</option>
                </select>
            </div>
            @elseif($category === 'regulasi')
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-[#555] mb-1.5 uppercase tracking-wide">Kategori Regulasi</label>
                    <select id="form-reg-category" name="reg_category" class="w-full px-3 py-2 border border-[#ddd] rounded text-sm focus:outline-none focus:border-[#256D4A] bg-white">
                        <option value="undang-undang">Undang-Undang</option>
                        <option value="peraturan-pemerintah">Peraturan Pemerintah</option>
                        <option value="peraturan-daerah">Peraturan Daerah</option>
                        <option value="peraturan-menteri">Peraturan Menteri</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-[#555] mb-1.5 uppercase tracking-wide">Instansi Penerbit</label>
                    <input type="text" id="form-reg-issuer" name="reg_issuer" class="w-full px-3 py-2 border border-[#ddd] rounded text-sm focus:outline-none focus:border-[#256D4A]" placeholder="Contoh: Pemerintah RI" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-[#555] mb-1.5 uppercase tracking-wide">Status Berlaku</label>
                    <select id="form-reg-status" name="reg_status" class="w-full px-3 py-2 border border-[#ddd] rounded text-sm focus:outline-none focus:border-[#256D4A] bg-white">
                        <option value="berlaku">Berlaku</option>
                        <option value="tidak-berlaku">Tidak Berlaku</option>
                    </select>
                </div>
            </div>
            @else
            <div>
                <label class="block text-xs font-semibold text-[#555] mb-1.5 uppercase tracking-wide">Tag Konten</label>
                <input type="text" id="form-tags" name="tags" class="w-full px-3 py-2 border border-[#ddd] rounded text-sm focus:outline-none focus:border-[#256D4A]" placeholder="lingkungan, advokasi, bandung" />
            </div>
            @endif

            <div>
                <label class="block text-xs font-semibold text-[#555] mb-1.5 uppercase tracking-wide">
                    @if($category === 'kampanye-darurat')
                        Deskripsi Singkat Aksi
                    @elseif($category === 'banner')
                        Subjudul / Tagline Banner (Heading 2)
                    @elseif($category === 'regulasi')
                        Ringkasan / Abstrak Regulasi
                    @else
                        Isi Konten / Deskripsi Artikel
                    @endif
                </label>
                <input type="hidden" id="form-body" name="body" />
                <div id="editor-wrapper" class="border border-[#ddd] rounded overflow-hidden">
                    <div id="editor-container" style="min-height: 280px; background: white;" class="text-sm"></div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <button type="button" data-on-click="closeModal" class="px-4 py-2 text-sm border border-[#ddd] rounded hover:bg-[#f0ede8] transition-colors">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 text-sm bg-[#256D4A] text-white font-semibold rounded hover:bg-[#1e5a3d] transition-colors">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet" integrity="sha384-VvSC4PGxeMkOaAmyuDGZECjY2dkqdO/IdBYBUK+BCYNc3WIvRxHLUzQ5OSgUaMA7" crossorigin="anonymous" />
<script nonce="{{ Vite::cspNonce() }}" src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js" integrity="sha384-hcxmSutM10NL6iGBAA0LStIhy+kWJxfrhqWVMRuABZH5Vqztexq2nBz/Xnfllly9" crossorigin="anonymous"></script>
<script nonce="{{ Vite::cspNonce() }}">
    var Align = Quill.import('attributors/style/align');
    Quill.register(Align, true);
    var Size = Quill.import('attributors/style/size');
    Quill.register(Size, true);

    let quillEditor;

    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('editor-container');
        if (container) {
            var toolbarOptions = [
                [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'color': [] }, { 'background': [] }],
                ['blockquote', 'code-block'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'indent': '-1'}, { 'indent': '+1' }],
                [{ 'align': [] }],
                ['link', 'image', 'video'],
                ['clean']
            ];

            quillEditor = new Quill('#editor-container', {
                modules: {
                    toolbar: {
                        container: toolbarOptions,
                        handlers: {
                            image: imageHandler
                        }
                    }
                },
                theme: 'snow'
            });

            function imageHandler() {
                const input = document.createElement('input');
                input.setAttribute('type', 'file');
                input.setAttribute('accept', 'image/jpeg,image/png,image/webp,image/gif');
                input.click();

                input.onchange = async () => {
                    const file = input.files[0];
                    if (!file) return;

                    if (file.size > 2 * 1024 * 1024) {
                        const sizeMb = (file.size / (1024 * 1024)).toFixed(1);
                        alert(`Ukuran gambar (${sizeMb} MB) melebihi batas maksimal 2 MB. Silakan kompres gambar terlebih dahulu.`);
                        return;
                    }

                    const formData = new FormData();
                    formData.append('image', file);

                    try {
                        const response = await fetch('{{ route('admin.upload-image') }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                'Accept': 'application/json'
                            },
                            body: formData
                        });

                        const result = await response.json();
                        if (response.ok && result.url) {
                            const range = quillEditor.getSelection(true);
                            quillEditor.insertEmbed(range.index, 'image', result.url);
                            quillEditor.setSelection(range.index + 1);
                        } else {
                            alert(result.message || 'Gagal mengunggah gambar ke server.');
                        }
                    } catch (error) {
                        console.error('Error uploading image:', error);
                        alert('Terjadi kesalahan saat mengunggah gambar ke server.');
                    }
                };
            }

            const form = document.getElementById('modal-form');
            if (form) {
                form.addEventListener('submit', function() {
                    const bodyInput = document.getElementById('form-body');
                    if (bodyInput && quillEditor) {
                        bodyInput.value = quillEditor.root.innerHTML;
                    }
                });
            }
        }
    });

    const modal = document.getElementById('editor-modal');
    const modalTitle = document.getElementById('modal-title');
    const modalForm = document.getElementById('modal-form');
    const methodField = document.getElementById('method-field');
    let currentMode = 'add';
    let imageInputMode = 'upload';

    // Auto-generate slug on adding
    document.getElementById('form-title').addEventListener('input', function() {
        if (currentMode === 'add') {
            const slug = this.value.toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '') // remove invalid chars
                .replace(/\s+/g, '-')         // collapse whitespace and replace by -
                .replace(/-+/g, '-');         // collapse dashes
            document.getElementById('form-slug').value = slug;
        }
    });

    function setImageInputMode(mode) {
        imageInputMode = mode;
        const btnUpload = document.getElementById('btn-mode-upload');
        const btnUrl = document.getElementById('btn-mode-url');
        const containerUpload = document.getElementById('container-mode-upload');
        const containerUrl = document.getElementById('container-mode-url');

        if (!btnUpload || !btnUrl || !containerUpload || !containerUrl) {
            return;
        }

        if (mode === 'upload') {
            btnUpload.className = "px-2.5 py-1 rounded bg-[#256D4A] text-white font-medium transition-colors";
            btnUrl.className = "px-2.5 py-1 rounded bg-[#f0ede8] text-[#666] font-medium transition-colors";
            containerUpload.classList.remove('hidden');
            containerUrl.classList.add('hidden');
            
            const fileInput = document.getElementById('form-upload');
            if (fileInput && fileInput.files && fileInput.files[0]) {
                previewSelectedImage(fileInput);
            } else {
                const formImage = document.getElementById('form-image');
                const formImageVal = formImage ? formImage.value : '';
                if (formImageVal && formImageVal.startsWith('/storage/')) {
                    showPreview(formImageVal);
                } else {
                    hidePreview();
                }
            }
        } else {
            btnUpload.className = "px-2.5 py-1 rounded bg-[#f0ede8] text-[#666] font-medium transition-colors";
            btnUrl.className = "px-2.5 py-1 rounded bg-[#256D4A] text-white font-medium transition-colors";
            containerUpload.classList.add('hidden');
            containerUrl.classList.remove('hidden');
            
            const formImage = document.getElementById('form-image');
            const urlVal = formImage ? formImage.value : '';
            if (urlVal) {
                showPreview(urlVal);
            } else {
                hidePreview();
            }
        }
    }

    const formImageEl = document.getElementById('form-image');
    if (formImageEl) {
        formImageEl.addEventListener('input', function() {
            if (imageInputMode === 'url') {
                if (this.value) {
                    showPreview(this.value);
                } else {
                    hidePreview();
                }
            }
        });
    }

    function showPreview(src) {
        const wrapper = document.getElementById('image-preview-wrapper');
        const img = document.getElementById('image-preview-el');
        if (wrapper && img) {
            img.src = src;
            wrapper.classList.remove('hidden');
        }
    }

    function hidePreview() {
        const wrapper = document.getElementById('image-preview-wrapper');
        if (wrapper) {
            wrapper.classList.add('hidden');
        }
    }

    function previewSelectedImage(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const maxBytes = 2 * 1024 * 1024; // 2 MB
            if (file.size > maxBytes) {
                const sizeMb = (file.size / (1024 * 1024)).toFixed(1);
                alert(`Ukuran berkas (${sizeMb} MB) melebihi batas maksimal 2 MB. Silakan kompres atau pilih berkas yang lebih kecil agar server tetap cepat.`);
                input.value = '';
                hidePreview();
                return;
            }
            const reader = new FileReader();
            reader.onload = function(e) {
                showPreview(e.target.result);
            }
            reader.readAsDataURL(file);
        }
    }

    function clearImagePreview() {
        const uploadInput = document.getElementById('form-upload');
        const imageInput = document.getElementById('form-image');
        const removeInput = document.getElementById('form-remove-image');
        if (uploadInput) uploadInput.value = '';
        if (imageInput) imageInput.value = '';
        if (removeInput) removeInput.value = '1';
        hidePreview();
    }

    function openAddModal() {
        currentMode = 'add';
        modalTitle.innerText = 'Tambah {{ $config['title'] }}';
        
        // Setup Form Action
        const baseRoute = '{{ route('admin.content' . (request()->is('admin/tentang/*') ? '.tentang' : '') . '.store', $category) }}';
        modalForm.setAttribute('action', baseRoute);
        methodField.innerHTML = ''; // POST is default

        // Clear values
        document.getElementById('form-title').value = '';
        document.getElementById('form-slug').value = '';
        document.getElementById('form-status').value = 'draft';
        document.getElementById('form-date').value = new Date().toISOString().slice(0, 10);
        if(document.getElementById('form-upload')) document.getElementById('form-upload').value = '';
        if(document.getElementById('form-image')) document.getElementById('form-image').value = '';
        if(document.getElementById('form-remove-image')) document.getElementById('form-remove-image').value = '0';
        if(document.getElementById('form-tags')) document.getElementById('form-tags').value = '';
        if(document.getElementById('form-body')) document.getElementById('form-body').value = '';
        if(quillEditor) quillEditor.root.innerHTML = '';
        if(document.getElementById('form-author')) document.getElementById('form-author').value = '';
        if(document.getElementById('form-is-promoted')) document.getElementById('form-is-promoted').checked = false;

        if(document.getElementById('form-isu-icon')) document.getElementById('form-isu-icon').value = 'Icon-4.svg';
        if(document.getElementById('form-isu-badge')) document.getElementById('form-isu-badge').value = '';
        if(document.getElementById('form-reg-category')) document.getElementById('form-reg-category').value = 'undang-undang';
        if(document.getElementById('form-reg-issuer')) document.getElementById('form-reg-issuer').value = '';
        if(document.getElementById('form-reg-status')) document.getElementById('form-reg-status').value = 'berlaku';
        if(document.getElementById('form-banner-btn1-text')) document.getElementById('form-banner-btn1-text').value = '';
        if(document.getElementById('form-banner-btn1-url')) document.getElementById('form-banner-btn1-url').value = '';
        if(document.getElementById('form-banner-btn2-text')) document.getElementById('form-banner-btn2-text').value = '';
        if(document.getElementById('form-banner-btn2-url')) document.getElementById('form-banner-btn2-url').value = '';

        hidePreview();
        setImageInputMode('upload');

        modal.classList.remove('hidden');
    }

    function openEditModal(button) {
        const item = JSON.parse(button.getAttribute('data-item'));
        currentMode = 'edit';
        modalTitle.innerText = 'Edit ' + item.title;
        
        // Setup Form Action for PUT
        let updateUrl = '{{ route('admin.content' . (request()->is('admin/tentang/*') ? '.tentang' : '') . '.update', [$category, ':id']) }}';
        updateUrl = updateUrl.replace(':id', item.id);
        modalForm.setAttribute('action', updateUrl);
        methodField.innerHTML = '@method("PUT")';

        // Populate values
        document.getElementById('form-title').value = item.title;
        document.getElementById('form-slug').value = item.slug;
        document.getElementById('form-status').value = item.status;
        
        if (item.publish_date) {
            const pDate = typeof item.publish_date === 'object' ? item.publish_date.date.slice(0,10) : item.publish_date.slice(0,10);
            document.getElementById('form-date').value = pDate;
        } else {
            document.getElementById('form-date').value = '';
        }
        
        if(document.getElementById('form-upload')) document.getElementById('form-upload').value = '';
        if(document.getElementById('form-image')) document.getElementById('form-image').value = item.image_url || '';
        if(document.getElementById('form-remove-image')) document.getElementById('form-remove-image').value = '0';
        if(document.getElementById('form-tags')) document.getElementById('form-tags').value = item.tags || '';
        if(document.getElementById('form-body')) document.getElementById('form-body').value = item.body || '';
        if(quillEditor) { quillEditor.setContents([]); quillEditor.clipboard.dangerouslyPasteHTML(0, item.body || ''); }
        if(document.getElementById('form-author')) document.getElementById('form-author').value = item.author || '';
        if(document.getElementById('form-is-promoted')) document.getElementById('form-is-promoted').checked = !!item.is_promoted;

        if(document.getElementById('form-banner-btn1-text')) document.getElementById('form-banner-btn1-text').value = '';
        if(document.getElementById('form-banner-btn1-url')) document.getElementById('form-banner-btn1-url').value = '';
        if(document.getElementById('form-banner-btn2-text')) document.getElementById('form-banner-btn2-text').value = '';
        if(document.getElementById('form-banner-btn2-url')) document.getElementById('form-banner-btn2-url').value = '';

        // Handle tags parsing for specific categories
        if (item.tags) {
            if ('{{ $category }}' === 'banner') {
                const parts = item.tags.split('|');
                if (document.getElementById('form-banner-btn1-text')) document.getElementById('form-banner-btn1-text').value = parts[0] || '';
                if (document.getElementById('form-banner-btn1-url')) document.getElementById('form-banner-btn1-url').value = parts[1] || '';
                if (document.getElementById('form-banner-btn2-text')) document.getElementById('form-banner-btn2-text').value = parts[2] || '';
                if (document.getElementById('form-banner-btn2-url')) document.getElementById('form-banner-btn2-url').value = parts[3] || '';
            } else if ('{{ $category }}' === 'isu-kritis') {
                if (item.tags.includes('|')) {
                    const parts = item.tags.split('|');
                    if (document.getElementById('form-isu-icon')) document.getElementById('form-isu-icon').value = parts[0];
                    if (document.getElementById('form-isu-badge')) document.getElementById('form-isu-badge').value = parts[1];
                } else {
                    if (document.getElementById('form-isu-icon')) document.getElementById('form-isu-icon').value = 'Icon-4.svg';
                    if (document.getElementById('form-isu-badge')) document.getElementById('form-isu-badge').value = item.tags;
                }
            } else if ('{{ $category }}' === 'statistik') {
                if (document.getElementById('form-isu-icon')) document.getElementById('form-isu-icon').value = item.tags;
            } else if ('{{ $category }}' === 'regulasi') {
                const parts = item.tags.split(',').map(s => s.trim());
                if (parts.length >= 3) {
                    if (document.getElementById('form-reg-category')) document.getElementById('form-reg-category').value = parts[0];
                    if (document.getElementById('form-reg-issuer')) document.getElementById('form-reg-issuer').value = parts[1];
                    if (document.getElementById('form-reg-status')) document.getElementById('form-reg-status').value = parts[2];
                } else {
                    if (document.getElementById('form-reg-category')) document.getElementById('form-reg-category').value = 'undang-undang';
                    if (document.getElementById('form-reg-issuer')) document.getElementById('form-reg-issuer').value = 'Pemerintah RI';
                    if (document.getElementById('form-reg-status')) document.getElementById('form-reg-status').value = 'berlaku';
                }
            }
        }

        if (item.image_url) {
            showPreview(item.image_url);
            if (item.image_url.startsWith('/storage/')) {
                setImageInputMode('upload');
            } else {
                setImageInputMode('url');
            }
        } else {
            hidePreview();
            setImageInputMode('upload');
        }

        modal.classList.remove('hidden');
    }

    function closeModal() {
        modal.classList.add('hidden');
    }

    // Close on click outside modal
    window.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeModal();
        }
    });
</script>
@endpush
