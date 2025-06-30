@extends('layouts.app')

@section('title', 'Manajemen Situs Monitoring')

@section('content')
<div x-data="{ 
        selectedSources: [],
        toggleAll(event, ids) {
            if (event.target.checked) {
                this.selectedSources = [...new Set([...this.selectedSources, ...ids])];
            } else {
                this.selectedSources = this.selectedSources.filter(id => !ids.includes(id));
            }
        },
        isAllSelected(ids) {
            if (ids.length === 0) return false;
            return ids.every(id => this.selectedSources.includes(id));
        },
        submitBulkAction(action) {
            if (this.selectedSources.length === 0) {
                alert('Pilih setidaknya satu situs untuk melakukan aksi.');
                return;
            }
            if (action === 'delete' && !confirm(`Anda yakin ingin menghapus ${this.selectedSources.length} situs terpilih? Aksi ini tidak dapat dibatalkan.`)) {
                return;
            }
            
            let form = document.getElementById('bulkActionForm');
            form.querySelector('input[name=action]').value = action;
            
            form.querySelectorAll('input[name^=source_ids]').forEach(el => el.remove());
            this.selectedSources.forEach(id => {
                let input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'source_ids[]';
                input.value = id;
                form.appendChild(input);
            });
            form.submit();
        }
     }">

    <form id="bulkActionForm" method="POST" action="{{ route('monitoring.sources.bulk_action') }}" class="hidden">
        @csrf
        <input type="hidden" name="action" value="">
    </form>

    <div x-show="selectedSources.length > 0" 
         x-transition
         class="fixed bottom-4 right-4 z-50 bg-white shadow-lg rounded-lg p-4 border border-gray-200 flex items-center gap-4"
         style="display: none;">
        <span class="text-sm font-semibold text-gray-800" x-text="`${selectedSources.length} situs terpilih`"></span>
        <div class="flex items-center gap-2">
            <button @click="submitBulkAction('activate')" class="px-3 py-1 text-xs font-medium text-white bg-green-600 rounded-md hover:bg-green-700">Aktifkan</button>
            <button @click="submitBulkAction('deactivate')" class="px-3 py-1 text-xs font-medium text-white bg-yellow-600 rounded-md hover:bg-yellow-700">Nonaktifkan</button>
            <button @click="submitBulkAction('crawl')" class="px-3 py-1 text-xs font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">Crawl</button>
            <button @click="submitBulkAction('delete')" class="px-3 py-1 text-xs font-medium text-white bg-red-600 rounded-md hover:bg-red-700">Hapus</button>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-semibold text-gray-800 leading-tight">
                {{ __('Manajemen Situs Monitoring') }}
            </h2>
            <div class="flex items-center space-x-4">
                <form method="POST" action="{{ route('monitoring.sources.crawl') }}" x-data="{ submitting: false }" @submit="submitting = true">
                    @csrf
                    <button type="submit" :disabled="submitting" class="bg-purple-600 text-white py-2 px-4 rounded-md hover:bg-purple-700 focus:outline-none focus:ring focus:border-purple-300 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!submitting">Crawl Semua Situs Aktif</span>
                        <span x-show="submitting">Mengirim Jobs...</span>
                    </button>
                </form>
                <a href="{{ route('monitoring.sources.create') }}" class="bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600 focus:outline-none focus:ring focus:border-blue-300">
                    Tambah Situs Baru
                </a>
            </div>
        </div>

        @if($uncategorizedSources->isNotEmpty())
        <div class="mb-6">
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded-md" role="alert">
                <p class="font-bold">Situs Tanpa Wilayah</p>
                <p>Ditemukan {{ $uncategorizedSources->count() }} situs yang belum memiliki wilayah. Silakan klik "Edit" untuk menetapkan wilayah yang benar.</p>
            </div>
            <div class="mt-2 space-y-2">
                @foreach($uncategorizedSources as $source)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-md border">
                    <div class="flex items-center space-x-3">
                        <input type="checkbox" :value="{{ $source->id }}" x-model="selectedSources" class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                        <div class="flex flex-col items-start space-y-1">
                            @php
                                $status_color = match($source->site_status) {
                                    'Aktif' => 'bg-green-100 text-green-800',
                                    'URL Tidak Valid' => 'bg-red-100 text-red-800',
                                    'Tanpa Halaman Berita' => 'bg-yellow-100 text-yellow-800',
                                    default => 'bg-gray-200 text-gray-800',
                                };
                            @endphp
                            <span class="px-2 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full {{ $status_color }}">{{ $source->site_status }}</span>
                            <span class="px-2 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full {{ $source->is_active ? 'bg-blue-100 text-blue-800' : 'bg-gray-200 text-gray-700' }}">{{ $source->is_active ? 'Crawl ON' : 'Crawl OFF' }}</span>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $source->name }}</p>
                            <p class="text-xs text-red-500 font-semibold">Wilayah belum diatur</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4 text-sm">
                        <a href="{{ route('monitoring.sources.edit', $source) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                        <form action="{{ route('monitoring.sources.destroy', $source) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus situs {{ addslashes($source->name) }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900">Hapus</button>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <div class="space-y-2">
            @forelse($provinces as $province)
                @php
                    $allSourceIds = $province->monitoringSources->pluck('id')->merge($province->children->flatMap->monitoringSources->pluck('id'))->values()->all();
                @endphp
                <div x-data="{ open: false }" class="bg-white border border-gray-200 rounded-lg">
                    <div @click.self="open = !open" class="p-4 flex justify-between items-center cursor-pointer hover:bg-gray-50">
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" @click.stop="toggleAll($event, {{ json_encode($allSourceIds) }})" :checked="isAllSelected({{ json_encode($allSourceIds) }})" class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                            <h3 @click="open = !open" class="text-lg font-medium text-gray-900">{{ $province->name }}</h3>
                            @if($province->total_sites_count > 0)
                                <span class="px-2 py-1 text-xs font-semibold text-gray-700 bg-gray-200 rounded-full">
                                    {{ $province->total_sites_count }} Situs
                                </span>
                            @endif
                        </div>
                        <svg x-show="!open" class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        <svg x-show="open" class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                    </div>

                    <div x-show="open" x-transition class="border-t border-gray-200 p-4 space-y-3">
                        @php
                            $allSources = $province->monitoringSources->merge($province->children->flatMap->monitoringSources)->sortBy('name');
                        @endphp

                        @forelse($allSources as $source)
                            <div class="flex items-center justify-between p-3 {{ $source->tipe_instansi === 'BKD' ? 'bg-blue-50' : 'bg-gray-50' }} rounded-md">
                                <div class="flex items-center space-x-3">
                                    <input type="checkbox" :value="{{ $source->id }}" x-model="selectedSources" class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                    <div class="flex flex-col items-start space-y-1">
                                        @php
                                            $status_color = match($source->site_status) {
                                                'Aktif' => 'bg-green-100 text-green-800',
                                                'URL Tidak Valid' => 'bg-red-100 text-red-800',
                                                'Tanpa Halaman Berita' => 'bg-yellow-100 text-yellow-800',
                                                default => 'bg-gray-200 text-gray-800',
                                            };
                                        @endphp
                                        <span class="px-2 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full {{ $status_color }}">{{ $source->site_status }}</span>
                                        <span class="px-2 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full {{ $source->is_active ? 'bg-blue-100 text-blue-800' : 'bg-gray-200 text-gray-700' }}">{{ $source->is_active ? 'Crawl ON' : 'Crawl OFF' }}</span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ $source->name }}</p>
                                        <div class="flex items-center space-x-2">
                                            <p class="text-xs {{ $source->tipe_instansi === 'BKD' ? 'text-blue-600' : 'text-gray-500' }} font-semibold">{{ $source->region->name ?? 'N/A' }} ({{$source->tipe_instansi}})</p>
                                            @if($source->suggestion_engine)
                                            <span class="px-1.5 py-0.5 text-xs font-medium rounded-md bg-indigo-100 text-indigo-800">{{ $source->suggestion_engine }}</span>
                                            @endif
                                            
                                            {{-- [BARU v1.29.0] Badge untuk Mode Tanpa Tanggal --}}
                                            @if(!$source->expects_date)
                                                <span class="px-1.5 py-0.5 text-xs font-medium rounded-md bg-yellow-200 text-yellow-800 border border-yellow-300" title="Situs ini dikonfigurasi untuk tidak memiliki tanggal.">
                                                    Tanpa Tanggal
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-4 text-sm">
                                    <a href="{{ $source->url }}" target="_blank" class="text-gray-500 hover:text-gray-800" title="Kunjungi Situs">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z" /><path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z" /></svg>
                                    </a>
                                    <form action="{{ route('monitoring.sources.crawl_single', $source) }}" method="POST" x-data="{ submitting: false }" @submit="submitting = true">
                                        @csrf
                                        <button type="submit" :disabled="submitting" class="text-green-600 hover:text-green-900 disabled:opacity-50">Crawl</button>
                                    </form>
                                    <a href="{{ route('monitoring.sources.edit', $source) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                    <form action="{{ route('monitoring.sources.destroy', $source) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus situs {{ addslashes($source->name) }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900">Hapus</button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">Tidak ada situs monitoring untuk provinsi ini.</p>
                        @endforelse
                    </div>
                </div>
            @empty
                <p class="text-gray-600">Belum ada Provinsi yang ditambahkan. Silakan tambahkan data wilayah melalui seeder.</p>
            @endforelse
        </div>

        <div class="mt-8 text-center">
            <a href="{{ route('dashboard') }}" class="text-blue-500 hover:underline">Kembali ke Dashboard</a>
        </div>
    </div>
</div>
@endsection