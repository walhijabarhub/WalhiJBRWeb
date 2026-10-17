@extends('layouts.admin')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-2xl font-bold text-[#1D1D1D]">Pekan Rakyat Lingkungan Hidup</h1>
        <p class="text-sm text-[#888] mt-1">Manajemen event dan pendaftaran peserta</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Event Aktif -->
        <div class="bg-white border border-[#ddd] rounded-lg p-5 flex items-start gap-4">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 bg-[#256D4A] text-white">
                <i data-lucide="calendar" class="w-[18px] h-[18px]"></i>
            </div>
            <div class="min-w-0">
                <div class="text-xs text-[#888] uppercase tracking-wide mb-1">Event Aktif</div>
                <div class="text-2xl font-bold text-[#1D1D1D] leading-tight">3</div>
                <div class="text-xs text-[#888] mt-0.5">Bulan ini</div>
            </div>
        </div>

        <!-- Total Peserta -->
        <div class="bg-white border border-[#ddd] rounded-lg p-5 flex items-start gap-4">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 bg-[#5C8D59] text-white">
                <i data-lucide="users" class="w-[18px] h-[18px]"></i>
            </div>
            <div class="min-w-0">
                <div class="text-xs text-[#888] uppercase tracking-wide mb-1">Total Peserta</div>
                <div class="text-2xl font-bold text-[#1D1D1D] leading-tight">481</div>
                <div class="text-xs text-[#888] mt-0.5">Terdaftar</div>
            </div>
        </div>

        <!-- Lokasi -->
        <div class="bg-white border border-[#ddd] rounded-lg p-5 flex items-start gap-4">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 bg-[#8B6B4A] text-white">
                <i data-lucide="map-pin" class="w-[18px] h-[18px]"></i>
            </div>
            <div class="min-w-0">
                <div class="text-xs text-[#888] uppercase tracking-wide mb-1">Lokasi</div>
                <div class="text-2xl font-bold text-[#1D1D1D] leading-tight">5</div>
                <div class="text-xs text-[#888] mt-0.5">Kota di Jabar</div>
            </div>
        </div>

        <!-- Event Mendatang -->
        <div class="bg-white border border-[#ddd] rounded-lg p-5 flex items-start gap-4">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 bg-[#D95C3F] text-white">
                <i data-lucide="clock" class="w-[18px] h-[18px]"></i>
            </div>
            <div class="min-w-0">
                <div class="text-xs text-[#888] uppercase tracking-wide mb-1">Event Mendatang</div>
                <div class="text-2xl font-bold text-[#1D1D1D] leading-tight">2</div>
                <div class="text-xs text-[#888] mt-0.5">dalam 7 hari</div>
            </div>
        </div>
    </div>

    <!-- Upcoming Events List -->
    <div class="bg-white border border-[#ddd] rounded-lg p-5">
        <h2 class="font-bold text-[#1D1D1D] text-sm mb-4">Event Mendatang</h2>
        <div class="space-y-3">
            <div class="flex items-center justify-between gap-4 p-3 rounded-lg bg-[#f9f8f5] border border-[#eee]">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-lg bg-[#256D4A] flex items-center justify-center shrink-0">
                        <i data-lucide="calendar" class="text-white w-3.5 h-3.5"></i>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-[#1D1D1D]">Pekan Rakyat Lingkungan Hidup 2025</p>
                        <p class="text-xs text-[#888]">15–20 Agt 2025 · Bandung</p>
                    </div>
                </div>
                <div class="text-right shrink-0">
                    <p class="text-sm font-bold text-[#256D4A]">342</p>
                    <p class="text-[10px] text-[#888]">peserta</p>
                </div>
            </div>

            <div class="flex items-center justify-between gap-4 p-3 rounded-lg bg-[#f9f8f5] border border-[#eee]">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-lg bg-[#256D4A] flex items-center justify-center shrink-0">
                        <i data-lucide="calendar" class="text-white w-3.5 h-3.5"></i>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-[#1D1D1D]">Workshop Pemantauan Kualitas Air</p>
                        <p class="text-xs text-[#888]">5 Agt 2025 · Cirebon</p>
                    </div>
                </div>
                <div class="text-right shrink-0">
                    <p class="text-sm font-bold text-[#256D4A]">48</p>
                    <p class="text-[10px] text-[#888]">peserta</p>
                </div>
            </div>

            <div class="flex items-center justify-between gap-4 p-3 rounded-lg bg-[#f9f8f5] border border-[#eee]">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-lg bg-[#256D4A] flex items-center justify-center shrink-0">
                        <i data-lucide="calendar" class="text-white w-3.5 h-3.5"></i>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-[#1D1D1D]">Aksi Bersih Sungai Cikapundung</p>
                        <p class="text-xs text-[#888]">27 Jul 2025 · Bandung</p>
                    </div>
                </div>
                <div class="text-right shrink-0">
                    <p class="text-sm font-bold text-[#256D4A]">91</p>
                    <p class="text-[10px] text-[#888]">peserta</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Events Table -->
    <div>
        <div class="flex items-center justify-between gap-4 mb-4">
            <h2 class="font-bold text-[#1D1D1D] text-base">Semua Event</h2>
            <button data-on-click="openAddModal" class="flex items-center gap-2 px-4 py-2 bg-[#256D4A] text-white text-sm font-semibold rounded hover:bg-[#1e5a3d] transition-colors shrink-0">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                Tambah Event
            </button>
        </div>

        <div class="bg-white border border-[#ddd] rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-[#f9f8f5] border-b border-[#ddd]">
                            <th class="text-left px-4 py-3 text-xs font-semibold text-[#888] uppercase tracking-wide">Nama Event</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-[#888] uppercase tracking-wide">Status</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-[#888] uppercase tracking-wide hidden sm:table-cell">Tanggal Event</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-[#888] uppercase tracking-wide">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#f0ede8]">
                        @if($items->isEmpty())
                            <tr>
                                <td colSpan="4" class="text-center py-10 text-[#888] text-sm">
                                    Belum ada event. Klik Tambah untuk mulai.
                                </td>
                            </tr>
                        @else
                            @foreach($items as $item)
                                <tr class="hover:bg-[#fafaf8] transition-colors">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-[#1D1D1D] leading-snug line-clamp-2 max-w-xs">{{ $item->title }}</div>
                                        <div class="text-xs text-[#aaa] mt-0.5">/{{ $item->slug }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($item->status === 'published')
                                            <span class="px-2 py-0.5 text-xs font-medium rounded border bg-[#eaf4ee] text-[#256D4A] border-[#c5e0ce]">Terbit</span>
                                        @elseif($item->status === 'draft')
                                            <span class="px-2 py-0.5 text-xs font-medium rounded border bg-[#f5f5f0] text-[#8B6B4A] border-[#ddd5c5]">Draf</span>
                                        @else
                                            <span class="px-2 py-0.5 text-xs font-medium rounded border bg-[#f5f5f5] text-[#888] border-[#ddd]">Arsip</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs text-[#888] hidden sm:table-cell">
                                        {{ $item->publish_date ? $item->publish_date->format('Y-m-d') : $item->updated_at->format('Y-m-d') }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-1">
                                            <form action="{{ route('admin.content.toggle-status', [$category, $item->id]) }}" method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" title="{{ $item->status === 'published' ? 'Arsipkan' : 'Terbitkan' }}" class="p-1.5 rounded hover:bg-[#eaf4ee] text-[#256D4A] transition-colors">
                                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                                </button>
                                            </form>
                                            
                                            <button data-on-click="openEditModal" data-item="{{ json_encode($item->toEditorArray()) }}" class="p-1.5 rounded hover:bg-[#f0f5ff] text-[#555] transition-colors">
                                                <i data-lucide="edit-2" class="w-3.5 h-3.5"></i>
                                            </button>

                                            <form action="{{ route('admin.content.destroy', [$category, $item->id]) }}" method="POST" data-confirm="Hapus event &quot;{{ $item->title }}&quot;?">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1.5 rounded hover:bg-[#fdf0ee] text-[#D95C3F] transition-colors">
                                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                                </button>
                                            </form>
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
        <div class="flex items-center justify-between px-6 py-4 border-b border-[#ddd] sticky top-0 bg-white z-10">
            <h2 id="modal-title" class="font-bold text-[#1D1D1D]">Tambah Event</h2>
            <button data-on-click="closeModal" class="p-1.5 rounded hover:bg-[#f0ede8] transition-colors">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <form id="modal-form" action="" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
            @csrf
            <div id="method-field"></div>

            <div>
                <label class="block text-xs font-semibold text-[#555] mb-1.5 uppercase tracking-wide">Nama Event *</label>
                <input type="text" id="form-title" name="title" required class="w-full px-3 py-2 border border-[#ddd] rounded text-sm focus:outline-none focus:border-[#256D4A]" placeholder="Masukkan nama event..." />
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
                    <label class="block text-xs font-semibold text-[#555] mb-1.5 uppercase tracking-wide">Tanggal Event</label>
                    <input type="date" id="form-date" name="publish_date" class="w-full px-3 py-2 border border-[#ddd] rounded text-sm focus:outline-none focus:border-[#256D4A]" />
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-[#555] mb-1.5 uppercase tracking-wide">Gambar Event</label>
                <input type="hidden" id="form-remove-image" name="remove_image" value="0" />
                <div class="flex items-center gap-3 mb-2 text-xs">
                    <button type="button" data-on-click="setImageInputMode" data-args='["upload"]' id="btn-mode-upload" class="px-2.5 py-1 rounded bg-[#256D4A] text-white font-medium transition-colors">Unggah File</button>
                    <button type="button" data-on-click="setImageInputMode" data-args='["url"]' id="btn-mode-url" class="px-2.5 py-1 rounded bg-[#f0ede8] text-[#666] font-medium transition-colors">Teks URL</button>
                </div>
                
                <!-- Upload File Container -->
                <div id="container-mode-upload" class="space-y-2">
                    <input type="file" id="form-upload" name="image" accept="image/*" data-on-change="previewSelectedImage" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-[#256D4A]/10 file:text-[#256D4A] hover:file:bg-[#256D4A]/20" />
                    <span class="text-[11px] text-gray-500 block">Maksimal ukuran file: <strong>2 MB</strong> (Rekomendasi format: JPG, PNG, WebP).</span>
                </div>

                <!-- URL Container -->
                <div id="container-mode-url" class="hidden">
                    <input type="text" id="form-image" name="image_url" class="w-full px-3 py-2 border border-[#ddd] rounded text-sm focus:outline-none focus:border-[#256D4A]" placeholder="https://..." />
                </div>

                <!-- Image Preview -->
                <div id="image-preview-wrapper" class="hidden mt-3 p-2 border border-[#eee] rounded bg-gray-50 flex items-center gap-3">
                    <img id="image-preview-el" src="" class="h-16 w-24 object-cover border border-[#ddd] rounded" />
                    <div class="text-xs">
                        <span class="text-[#888] block">Preview Gambar</span>
                        <button type="button" data-on-click="clearImagePreview" class="text-[#D95C3F] font-semibold hover:underline">Hapus Berkas</button>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-[#555] mb-1.5 uppercase tracking-wide">Tag Event</label>
                <input type="text" id="form-tags" name="tags" class="w-full px-3 py-2 border border-[#ddd] rounded text-sm focus:outline-none focus:border-[#256D4A]" placeholder="pekan rakyat, event, bandung" />
            </div>

            <div>
                <label class="block text-xs font-semibold text-[#555] mb-1.5 uppercase tracking-wide">Lokasi & Deskripsi Event</label>
                <input type="hidden" id="form-body" name="body" />
                <div id="editor-wrapper" class="border border-[#ddd] rounded overflow-hidden">
                    <div id="editor-container" style="height: 320px; background: white;" class="text-sm"></div>
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
<!-- Quill CSS and JS -->
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet" integrity="sha384-VvSC4PGxeMkOaAmyuDGZECjY2dkqdO/IdBYBUK+BCYNc3WIvRxHLUzQ5OSgUaMA7" crossorigin="anonymous" />
<script nonce="{{ Vite::cspNonce() }}" src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js" integrity="sha384-hcxmSutM10NL6iGBAA0LStIhy+kWJxfrhqWVMRuABZH5Vqztexq2nBz/Xnfllly9" crossorigin="anonymous"></script>

<script nonce="{{ Vite::cspNonce() }}">
    // Register style attributors to produce inline CSS (e.g. style="text-align: justify")
    var Align = Quill.import('attributors/style/align');
    Quill.register(Align, true);
    var Size = Quill.import('attributors/style/size');
    Quill.register(Size, true);

    let quillEditor = null;
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

                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

                    try {
                        const response = await fetch('{{ route('admin.upload-image') }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
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

            // Keep form-body in sync before submission
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

        if (!btnUpload || !btnUrl || !containerUpload || !containerUrl) return;

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
        if (wrapper) wrapper.classList.add('hidden');
    }

    function previewSelectedImage(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const maxBytes = 2 * 1024 * 1024; // 2 MB
            if (file.size > maxBytes) {
                const sizeMb = (file.size / (1024 * 1024)).toFixed(1);
                alert(`Ukuran berkas (${sizeMb} MB) melebihi batas maksimal 2 MB. Silakan kompres atau pilih gambar yang lebih kecil.`);
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
        modalTitle.innerText = 'Tambah Event';
        
        const baseRoute = '{{ route('admin.content.store', $category) }}';
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
        document.getElementById('form-tags').value = '';
        document.getElementById('form-body').value = '';
        if(quillEditor) quillEditor.root.innerHTML = '';

        hidePreview();
        setImageInputMode('upload');

        modal.classList.remove('hidden');
    }

    function openEditModal(button) {
        const item = JSON.parse(button.getAttribute('data-item'));
        currentMode = 'edit';
        modalTitle.innerText = 'Edit ' + item.title;
        
        let updateUrl = '{{ route('admin.content.update', [$category, ':id']) }}';
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
        document.getElementById('form-image').value = item.image_url || '';
        if(document.getElementById('form-remove-image')) document.getElementById('form-remove-image').value = '0';
        document.getElementById('form-tags').value = item.tags || '';
        document.getElementById('form-body').value = item.body || '';
        if(quillEditor) { quillEditor.setContents([]); quillEditor.clipboard.dangerouslyPasteHTML(0, item.body || ''); }

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

    window.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeModal();
        }
    });
</script>
@endpush
