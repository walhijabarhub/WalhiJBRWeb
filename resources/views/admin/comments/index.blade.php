@extends('layouts.admin')

@section('content')
<div class="space-y-6">
    <!-- Success Alert -->
    @if(session('success'))
        <div class="bg-[#256D4A] border-l-4 border-white text-white p-4 rounded shadow-sm text-sm font-semibold">
            {{ session('success') }}
        </div>
    @endif

    <!-- Header & Tabs -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-[#ddd] pb-4">
        <div class="flex gap-2">
            <a href="{{ route('admin.comments.index', ['status' => 'pending']) }}" class="px-4 py-2 text-sm font-semibold border-b-2 transition-colors {{ $status === 'pending' ? 'border-[#256D4A] text-[#256D4A]' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                Menunggu Persetujuan
            </a>
            <a href="{{ route('admin.comments.index', ['status' => 'approved']) }}" class="px-4 py-2 text-sm font-semibold border-b-2 transition-colors {{ $status === 'approved' ? 'border-[#256D4A] text-[#256D4A]' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                Disetujui
            </a>
            <a href="{{ route('admin.comments.index', ['status' => 'spam']) }}" class="px-4 py-2 text-sm font-semibold border-b-2 transition-colors {{ $status === 'spam' ? 'border-[#256D4A] text-[#256D4A]' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                Spam
            </a>
        </div>
    </div>

    <!-- Table Card -->
    <div class="bg-white border border-[#ddd] rounded shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-[#ddd] text-xs font-bold uppercase text-gray-500 tracking-wider">
                        <th class="px-6 py-4">Pengirim</th>
                        <th class="px-6 py-4">Konten</th>
                        <th class="px-6 py-4 w-1/3">Isi Komentar</th>
                        <th class="px-6 py-4">Tanggal</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#eee] text-sm">
                    @forelse($comments as $comment)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-semibold text-gray-900">{{ $comment->author_name }}</div>
                                <div class="text-xs text-gray-500">{{ $comment->author_email }}</div>
                                @if($comment->parent)
                                    <div class="mt-1 text-[11px] text-[#D95C3F] font-semibold">
                                        Balasan untuk ID: #{{ $comment->parent_id }} ({{ $comment->parent->author_name }})
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <a href="{{ route('content.show', $comment->content->slug) }}" target="_blank" class="text-[#256D4A] hover:underline font-medium">
                                    {{ Str::limit($comment->content->title, 40) }}
                                </a>
                                <div class="text-xs text-gray-500 uppercase mt-0.5">{{ $comment->content->category }}</div>
                            </td>
                            <td class="px-6 py-4 text-gray-700 break-all whitespace-pre-wrap">{{ $comment->body }}</td>
                            <td class="px-6 py-4 text-gray-500 whitespace-nowrap">
                                {{ $comment->created_at->translatedFormat('d M Y') }}
                                <div class="text-xs text-gray-400">{{ $comment->created_at->format('H:i') }}</div>
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <div class="inline-flex gap-2 justify-end">
                                    @if($comment->status === 'pending' || $comment->status === 'spam')
                                        <form action="{{ route('admin.comments.approve', $comment->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="bg-[#256D4A] hover:bg-[#1a4b33] text-white text-xs font-bold py-1.5 px-3 rounded transition-colors shadow-sm">
                                                Setujui
                                            </button>
                                        </form>
                                    @endif

                                    @if($comment->status === 'pending' || $comment->status === 'approved')
                                        <form action="{{ route('admin.comments.spam', $comment->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="bg-[#8B6B4A] hover:bg-[#6b5238] text-white text-xs font-bold py-1.5 px-3 rounded transition-colors shadow-sm">
                                                Spam
                                            </button>
                                        </form>
                                    @endif

                                    <form action="{{ route('admin.comments.destroy', $comment->id) }}" method="POST" data-confirm="Apakah Anda yakin ingin menghapus komentar ini?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="bg-[#D95C3F] hover:bg-[#b04b33] text-white text-xs font-bold py-1.5 px-3 rounded transition-colors shadow-sm">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500 italic">
                                Tidak ada komentar dalam kategori ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Links -->
        @if($comments->hasPages())
            <div class="px-6 py-4 bg-gray-50 border-t border-[#ddd]">
                {{ $comments->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
