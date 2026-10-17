@extends('layouts.admin')

@section('content')
<div class="space-y-6">
    <!-- Success Alert -->
    @if(session('success'))
        <div class="bg-[#256D4A] border-l-4 border-white text-white p-4 rounded shadow-sm text-sm font-semibold">
            {{ session('success') }}
        </div>
    @endif

    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-[#ddd] pb-4">
        <h2 class="text-xl font-bold text-gray-900">Pelanggan Newsletter</h2>
        
        <a href="{{ route('admin.subscribers.export') }}" class="inline-flex items-center gap-2 bg-[#256D4A] hover:bg-[#1a4b33] text-white text-sm font-bold py-2 px-4 rounded transition-colors shadow-sm">
            <i data-lucide="download" class="w-4 h-4"></i>
            Ekspor List CSV
        </a>
    </div>

    <!-- Table Card -->
    <div class="bg-white border border-[#ddd] rounded shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-[#ddd] text-xs font-bold uppercase text-gray-500 tracking-wider">
                        <th class="px-6 py-4">Email</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Tanggal Daftar</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#eee] text-sm">
                    @forelse($subscribers as $subscriber)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 font-medium text-gray-900">
                                {{ $subscriber->email }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $subscriber->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $subscriber->is_active ? 'Aktif' : ($subscriber->confirmed_at ? 'Berhenti' : 'Belum Konfirmasi') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-500 whitespace-nowrap">
                                {{ $subscriber->created_at->translatedFormat('d M Y - H:i') }}
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <form action="{{ route('admin.subscribers.destroy', $subscriber->id) }}" method="POST" data-confirm="Apakah Anda yakin ingin menghapus pelanggan ini?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bg-[#D95C3F] hover:bg-[#b04b33] text-white text-xs font-bold py-1.5 px-3 rounded transition-colors shadow-sm">
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-gray-500 italic">
                                Belum ada pelanggan terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Links -->
        @if($subscribers->hasPages())
            <div class="px-6 py-4 bg-gray-50 border-t border-[#ddd]">
                {{ $subscribers->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
