@extends('layouts.app')

@section('title', 'Dashboard Pantauan: ' . $tracker->title)

@section('content')
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6"
     x-data="{ 
        search: '', 
        activeTab: 'all', 
        typeFilter: 'all',
        foundIds: {{ json_encode($foundArticles->keys()) }}
     }">
    <div class="mb-6 pb-4 border-b">
        <h2 class="text-3xl font-bold text-gray-800 leading-tight">
            {{ $tracker->title }}
        </h2>
        <p class="text-gray-600 mt-1">{{ $tracker->description }}</p>
        <div class="mt-2">
            <span class="text-sm font-medium text-gray-700">Kata Kunci:</span>
            @foreach($tracker->keywords as $keyword)
                <span class="inline-block bg-gray-200 rounded-full px-2 py-1 text-xs font-semibold text-gray-700 ml-1">{{ $keyword }}</span>
            @endforeach
        </div>
    </div>

    {{-- Widget Statistik --}}
    <div class="mb-6 p-4 bg-blue-50 rounded-lg">
        <h3 class="text-lg font-semibold text-blue-800">Ringkasan Statistik</h3>
        <div class="flex items-baseline mt-2">
            <p class="text-4xl font-bold text-blue-900">{{ $stats['found'] }}</p>
            <p class="text-xl text-gray-600 ml-2">/ {{ $stats['total'] }} instansi ditemukan</p>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2.5 mt-2">
            <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ $stats['percentage'] }}%"></div>
        </div>
        <p class="text-right text-sm text-blue-800 font-semibold mt-1">{{ $stats['percentage'] }}% Selesai</p>
    </div>

    {{-- [BARU v1.28.0] Kontrol Filter dan Tab --}}
    <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-6 p-4 bg-gray-50 rounded-lg border">
        {{-- Kontrol Tab Status --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Filter Berdasarkan Status</label>
            <div class="flex flex-wrap gap-2">
                <button @click="activeTab = 'all'" 
                        :class="activeTab === 'all' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'"
                        class="px-4 py-2 text-sm font-semibold rounded-md shadow-sm border border-gray-300">
                    Semua
                </button>
                <button @click="activeTab = 'found'" 
                        :class="activeTab === 'found' ? 'bg-green-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'"
                        class="px-4 py-2 text-sm font-semibold rounded-md shadow-sm border border-gray-300">
                    ✅ Ditemukan
                </button>
                <button @click="activeTab = 'notFound'" 
                        :class="activeTab === 'notFound' ? 'bg-red-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'"
                        class="px-4 py-2 text-sm font-semibold rounded-md shadow-sm border border-gray-300">
                    ❌ Belum Ditemukan
                </button>
            </div>
        </div>
        {{-- Kontrol Filter Tipe Instansi --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Filter Berdasarkan Tipe</label>
            <div class="flex flex-wrap gap-x-6 gap-y-2">
                 <label class="inline-flex items-center">
                    <input type="radio" x-model="typeFilter" value="all" class="form-radio h-4 w-4 text-indigo-600">
                    <span class="ml-2 text-sm text-gray-700">Semua Tipe</span>
                </label>
                 <label class="inline-flex items-center">
                    <input type="radio" x-model="typeFilter" value="BKD" class="form-radio h-4 w-4 text-indigo-600">
                    <span class="ml-2 text-sm text-gray-700">BKD Saja</span>
                </label>
                 <label class="inline-flex items-center">
                    <input type="radio" x-model="typeFilter" value="BKPSDM" class="form-radio h-4 w-4 text-indigo-600">
                    <span class="ml-2 text-sm text-gray-700">BKPSDM Saja</span>
                </label>
            </div>
        </div>
    </div>

    {{-- Daftar Instansi dengan Filter --}}
    <div class="space-y-2">
        <div class="mb-4">
            <input type="text" x-model="search" placeholder="Cari nama instansi..." class="block w-full border-gray-300 rounded-md shadow-sm">
        </div>

        @forelse($provinces as $province)
            @php
                $allSources = $province->monitoringSources->merge($province->children->flatMap->monitoringSources)->sortBy('name');
                
                // Menyiapkan data JSON untuk difilter oleh Alpine.js
                $provinceSourcesJson = json_encode($allSources->map(function($source) use ($foundArticles) {
                    return [
                        'id' => $source->id,
                        'name' => strtolower($source->name),
                        'tipe_instansi' => $source->tipe_instansi,
                        'is_found' => isset($foundArticles[$source->id])
                    ];
                }));
            @endphp
            
            {{-- [MODIFIKASI v1.28.0] x-show pada provinsi kini mengecek apakah ada anak yang cocok filter --}}
            <div x-data="{ open: false }" class="bg-white border border-gray-200 rounded-lg"
                 x-show="{{ $provinceSourcesJson }}.filter(s => 
                    (activeTab === 'all' || (activeTab === 'found' && s.is_found) || (activeTab === 'notFound' && !s.is_found)) &&
                    (typeFilter === 'all' || typeFilter === s.tipe_instansi) &&
                    (search === '' || s.name.includes(search.toLowerCase()))
                 ).length > 0">
                 
                <div @click="open = !open" class="p-4 flex justify-between items-center cursor-pointer hover:bg-gray-50">
                    <h3 class="text-lg font-medium text-gray-900">{{ $province->name }}</h3>
                    <svg x-show="!open" class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    <svg x-show="open" class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                </div>

                <div x-show="open" x-transition class="border-t border-gray-200 p-4 space-y-3">
                    @forelse($allSources as $source)
                        {{-- [MODIFIKASI v1.28.0] x-show pada setiap baris instansi --}}
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-md" 
                             x-show="(activeTab === 'all' || (activeTab === 'found' && foundIds.includes({{ $source->id }})) || (activeTab === 'notFound' && !foundIds.includes({{ $source->id }}))) &&
                                     (typeFilter === 'all' || typeFilter === '{{ $source->tipe_instansi }}') &&
                                     (search === '' || '{{ strtolower($source->name) }}'.includes(search.toLowerCase()))">
                            <p class="text-sm font-medium text-gray-900">{{ $source->name }} <span class="text-xs text-gray-500">({{ $source->tipe_instansi }})</span></p>
                            <div>
                                @if(isset($foundArticles[$source->id]))
                                    <a href="{{ $foundArticles[$source->id]->url }}" target="_blank" class="flex items-center space-x-2 text-green-600 hover:text-green-800 font-semibold" title="Judul: {{ $foundArticles[$source->id]->title }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                                        <span>Ditemukan</span>
                                    </a>
                                @else
                                    <span class="flex items-center space-x-2 text-red-500 font-semibold">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>
                                        <span>Belum Ditemukan</span>
                                    </span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Tidak ada situs monitoring di provinsi ini.</p>
                    @endforelse
                </div>
            </div>
        @empty
            <p class="text-gray-600">Belum ada data wilayah di sistem.</p>
        @endforelse
    </div>

    <div class="mt-8 text-center">
        <a href="{{ route('trackers.index') }}" class="text-blue-500 hover:underline">Kembali ke Daftar Pantauan</a>
    </div>
</div>
@endsection