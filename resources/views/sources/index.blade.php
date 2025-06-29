<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Xrandev - Government Monitoring</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    </head>
    <body class="antialiased bg-gray-100 min-h-screen">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-semibold text-gray-800 leading-tight">
                        {{ __('Manajemen Situs Monitoring') }}
                    </h2>
                    <div class="flex items-center space-x-4">
                        <form method="POST" action="{{ route('monitoring.sources.crawl') }}" x-data="{ submitting: false }" @submit="submitting = true">
                            @csrf
                            <button type="submit" 
                                    :disabled="submitting"
                                    class="bg-purple-600 text-white py-2 px-4 rounded-md hover:bg-purple-700 focus:outline-none focus:ring focus:border-purple-300 disabled:opacity-50 disabled:cursor-not-allowed">
                                <span x-show="!submitting">Crawl Semua Situs Aktif</span>
                                <span x-show="submitting">Mengirim Jobs...</span>
                            </button>
                        </form>
                        <a href="{{ route('monitoring.sources.create') }}" class="bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600 focus:outline-none focus:ring focus:border-blue-300">
                            Tambah Situs Baru
                        </a>
                    </div>
                </div>

                @if (session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline">{{ session('success') }}</span>
                    </div>
                @endif
                @if (session('info'))
                    <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline">{{ session('info') }}</span>
                    </div>
                @endif

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
                                <div class="flex flex-col items-start space-y-1">
                                    @php
                                        $status_color = match($source->site_status) {
                                            'Aktif' => 'bg-green-100 text-green-800',
                                            'URL Tidak Valid' => 'bg-red-100 text-red-800',
                                            'Tanpa Halaman Berita' => 'bg-yellow-100 text-yellow-800',
                                            default => 'bg-gray-200 text-gray-800',
                                        };
                                    @endphp
                                    <span class="px-2 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full {{ $status_color }}">
                                        {{ $source->site_status }}
                                    </span>
                                    <span class="px-2 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full {{ $source->is_active ? 'bg-blue-100 text-blue-800' : 'bg-gray-200 text-gray-700' }}">
                                        {{ $source->is_active ? 'Crawl ON' : 'Crawl OFF' }}
                                    </span>
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
                        <div x-data="{ open: false }" class="bg-white border border-gray-200 rounded-lg">
                            <div @click="open = !open" class="p-4 flex justify-between items-center cursor-pointer hover:bg-gray-50">
                                <div class="flex items-center space-x-3">
                                    <h3 class="text-lg font-medium text-gray-900">{{ $province->name }}</h3>
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
                                @if($province->total_sites_count === 0)
                                    <p class="text-sm text-gray-500">Tidak ada situs monitoring di provinsi ini.</p>
                                @endif
                                
                                @php
                                    // Gabungkan semua situs (provinsi dan kab/kota) dan urutkan berdasarkan nama
                                    $allSources = $province->monitoringSources->merge($province->children->flatMap->monitoringSources)->sortBy('name');
                                @endphp

                                @foreach($allSources as $source)
                                    <div class="flex items-center justify-between p-3 {{ $source->tipe_instansi === 'BKD' ? 'bg-blue-50' : 'bg-gray-50' }} rounded-md">
                                        <div class="flex items-center space-x-3">
                                             <div class="flex flex-col items-start space-y-1">
                                                @php
                                                    $status_color = match($source->site_status) {
                                                        'Aktif' => 'bg-green-100 text-green-800',
                                                        'URL Tidak Valid' => 'bg-red-100 text-red-800',
                                                        'Tanpa Halaman Berita' => 'bg-yellow-100 text-yellow-800',
                                                        default => 'bg-gray-200 text-gray-800',
                                                    };
                                                @endphp
                                                <span class="px-2 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full {{ $status_color }}">
                                                    {{ $source->site_status }}
                                                </span>
                                                <span class="px-2 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full {{ $source->is_active ? 'bg-blue-100 text-blue-800' : 'bg-gray-200 text-gray-700' }}">
                                                    {{ $source->is_active ? 'Crawl ON' : 'Crawl OFF' }}
                                                </span>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">{{ $source->name }}</p>
                                                <div class="flex items-center space-x-2">
                                                    <p class="text-xs {{ $source->tipe_instansi === 'BKD' ? 'text-blue-600' : 'text-gray-500' }} font-semibold">{{ $source->region->name ?? 'N/A' }} ({{$source->tipe_instansi}})</p>
                                                    @if($source->suggestion_engine)
                                                    <span class="px-1.5 py-0.5 text-xs font-medium rounded-md bg-indigo-100 text-indigo-800">{{ $source->suggestion_engine }}</span>
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
                                @endforeach
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
    </body>
</html>