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
                        {{-- [MODIFIKASI] Tombol Crawl Semua dengan Indikator --}}
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
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $source->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $source->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $source->name }}</p>
                                    <p class="text-xs text-red-500 font-semibold">Wilayah belum diatur</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-3 text-sm">
                                {{-- [MODIFIKASI] Tombol Crawl Individual dengan Indikator --}}
                                <form action="{{ route('monitoring.sources.crawl_single', $source) }}" method="POST" x-data="{ submitting: false }" @submit="submitting = true" onsubmit="return confirm('Mulai crawling untuk situs {{ $source->name }}?')">
                                    @csrf
                                    <button type="submit" 
                                            :disabled="submitting"
                                            class="text-green-600 hover:text-green-900 disabled:opacity-50 disabled:cursor-not-allowed">
                                        <span x-show="!submitting">Crawl Sekarang</span>
                                        <span x-show="submitting">Memproses...</span>
                                    </button>
                                </form>
                                <a href="{{ route('monitoring.sources.edit', $source) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
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
                                <h3 class="text-lg font-medium text-gray-900">{{ $province->name }}</h3>
                                <svg x-show="!open" class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                <svg x-show="open" class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                            </div>

                            <div x-show="open" class="border-t border-gray-200 p-4 space-y-3">
                                
                                {{-- Situs BKD level Provinsi --}}
                                @foreach($province->monitoringSources as $source)
                                    <div class="flex items-center justify-between p-3 bg-blue-50 rounded-md">
                                        <div class="flex items-center space-x-3">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $source->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $source->is_active ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">{{ $source->name }}</p>
                                                <p class="text-xs text-blue-600 font-semibold">{{ $source->region->name ?? 'N/A' }} (BKD Provinsi)</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-3 text-sm">
                                            {{-- [MODIFIKASI] Tombol Crawl Individual dengan Indikator --}}
                                            <form action="{{ route('monitoring.sources.crawl_single', $source) }}" method="POST" x-data="{ submitting: false }" @submit="submitting = true" onsubmit="return confirm('Mulai crawling untuk situs {{ $source->name }}?')">
                                                @csrf
                                                <button type="submit" 
                                                        :disabled="submitting"
                                                        class="text-green-600 hover:text-green-900 disabled:opacity-50 disabled:cursor-not-allowed">
                                                    <span x-show="!submitting">Crawl Sekarang</span>
                                                    <span x-show="submitting">Memproses...</span>
                                                </button>
                                            </form>
                                            <a href="{{ route('monitoring.sources.edit', $source) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                        </div>
                                    </div>
                                @endforeach

                                {{-- Situs BKPSDM level Kab/Kota --}}
                                @foreach($province->children as $kabkota)
                                    @foreach($kabkota->monitoringSources as $source)
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-md">
                                        <div class="flex items-center space-x-3">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $source->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $source->is_active ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">{{ $source->name }}</p>
                                                <p class="text-xs text-gray-500">{{ $source->region->name ?? 'N/A' }} (BKPSDM)</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-3 text-sm">
                                            {{-- [MODIFIKASI] Tombol Crawl Individual dengan Indikator --}}
                                            <form action="{{ route('monitoring.sources.crawl_single', $source) }}" method="POST" x-data="{ submitting: false }" @submit="submitting = true" onsubmit="return confirm('Mulai crawling untuk situs {{ $source->name }}?')">
                                                @csrf
                                                <button type="submit" 
                                                        :disabled="submitting"
                                                        class="text-green-600 hover:text-green-900 disabled:opacity-50 disabled:cursor-not-allowed">
                                                    <span x-show="!submitting">Crawl Sekarang</span>
                                                    <span x-show="submitting">Memproses...</span>
                                                </button>
                                            </form>
                                            <a href="{{ route('monitoring.sources.edit', $source) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                        </div>
                                    </div>
                                    @endforeach
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