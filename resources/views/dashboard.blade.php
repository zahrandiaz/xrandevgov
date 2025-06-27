<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-g">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Dashboard Xrandev</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="antialiased bg-gray-100 min-h-screen py-12">
        <div class="p-6 max-w-4xl mx-auto bg-white rounded-xl shadow-md space-y-8">
            <div class="text-center">
                <h1 class="text-3xl font-bold text-gray-900">Selamat Datang di Dashboard Xrandev!</h1>
                <p class="text-lg text-gray-700 mt-2">Anda berhasil masuk dengan kode akses.</p>
            </div>

            <div class="pt-6 border-t">
                <h2 class="text-xl font-semibold text-center text-gray-800 mb-4">Pusat Kendali & Navigasi</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <a href="{{ route('monitoring.sources.index') }}" class="bg-green-500 text-white p-4 rounded-lg shadow hover:bg-green-600 text-center flex items-center justify-center">
                        <span class="font-semibold">Manajemen Situs</span>
                    </a>
                    <a href="{{ route('monitoring.articles.index') }}" class="bg-blue-500 text-white p-4 rounded-lg shadow hover:bg-blue-600 text-center flex items-center justify-center">
                        <span class="font-semibold">Daftar Artikel</span>
                    </a>
                    <a href="{{ route('regions.index') }}" class="bg-indigo-500 text-white p-4 rounded-lg shadow hover:bg-indigo-600 text-center flex items-center justify-center">
                        <span class="font-semibold">Manajemen Wilayah</span>
                    </a>
                    <a href="{{ route('import.sources.show') }}" class="bg-purple-500 text-white p-4 rounded-lg shadow hover:bg-purple-600 text-center flex items-center justify-center">
                        <span class="font-semibold">Impor Data</span>
                    </a>
                    <a href="{{ route('selector-presets.index') }}" class="bg-gray-700 text-white p-4 rounded-lg shadow hover:bg-gray-800 text-center flex items-center justify-center">
                        <span class="font-semibold">Manajemen Preset</span>
                    </a>
                    <a href="{{ route('suggestion-selectors.index') }}" class="bg-orange-500 text-white p-4 rounded-lg shadow hover:bg-orange-600 text-center flex items-center justify-center">
                        <span class="font-semibold">Manajemen Kamus</span>
                    </a>
                    <a href="{{ route('logout') }}" class="bg-red-500 text-white p-4 rounded-lg shadow hover:bg-red-600 text-center flex items-center justify-center col-span-2 md:col-span-1 lg:col-span-2">
                        <span class="font-semibold">Logout</span>
                    </a>
                </div>
            </div>

            <div class="pt-6 border-t">
                 <h2 class="text-xl font-semibold text-center text-gray-800 mb-4">Statistik Sistem</h2>
                <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="bg-teal-100 p-4 rounded-lg shadow text-center">
                        <p class="text-gray-600 text-sm">Tingkat Sukses Crawl</p>
                        <p class="text-3xl font-bold text-teal-800">{{ $crawlSuccessRate }}%</p>
                    </div>
                    <div class="bg-blue-100 p-4 rounded-lg shadow text-center">
                        <p class="text-gray-600 text-sm">Total Situs</p>
                        <p class="text-3xl font-bold text-blue-800">{{ $totalSources }}</p>
                    </div>
                    <div class="bg-green-100 p-4 rounded-lg shadow text-center">
                        <p class="text-gray-600 text-sm">Situs Aktif</p>
                        <p class="text-3xl font-bold text-green-800">{{ $activeSources }}</p>
                    </div>
                    <div class="bg-yellow-100 p-4 rounded-lg shadow text-center">
                        <p class="text-gray-600 text-sm">Artikel Baru 7 Hari</p>
                        <p class="text-3xl font-bold text-yellow-800">{{ $newArticlesLast7Days }}</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 pt-6 border-t">
                @if($latestCrawls->isNotEmpty())
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-3">Status Crawling Terbaru</h3>
                        <ul class="text-sm text-gray-700 space-y-2">
                            @foreach($latestCrawls as $source)
                                <li class="flex justify-between items-center bg-gray-50 p-3 rounded-md">
                                    <span>{{ Str::limit($source->name, 30) }}</span>
                                    @if($source->last_crawled_at)
                                        <span class="text-gray-600">{{ $source->last_crawled_at->diffForHumans() }}</span>
                                    @else
                                        <span class="text-gray-500">Belum di-crawl</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if($problematicSources->isNotEmpty())
                    <div>
                        <h3 class="text-lg font-semibold text-red-800 mb-3">Situs Bermasalah (Gagal >= 3x)</h3>
                        <div class="bg-red-50 border-l-4 border-red-400 p-4">
                            <ul class="text-sm text-gray-700 space-y-2">
                                @foreach($problematicSources as $source)
                                    <li class="flex justify-between items-center">
                                        <a href="{{ route('monitoring.sources.edit', $source) }}" class="font-semibold text-indigo-600 hover:underline">{{ Str::limit($source->name, 30) }}</a>
                                        <span class="text-red-600 font-bold">{{ $source->consecutive_failures }}x Gagal</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif
            </div>
            
            {{-- [BARU] Seksi Log Aktivitas Sistem --}}
            <div class="pt-6 border-t">
                <h2 class="text-xl font-semibold text-center text-gray-800 mb-4">Log Aktivitas Terbaru</h2>
                <div class="space-y-3">
                    @forelse($systemActivities as $activity)
                        <div class="flex items-start p-3 rounded-lg
                            @switch($activity->level)
                                @case('success') bg-green-50 border-l-4 border-green-400 @break
                                @case('warning') bg-yellow-50 border-l-4 border-yellow-400 @break
                                @case('error')   bg-red-50 border-l-4 border-red-400 @break
                                @default        bg-blue-50 border-l-4 border-blue-400
                            @endswitch">
                            <div class="ml-3 flex-1">
                                <p class="text-sm text-gray-800">{{ $activity->message }}</p>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ $activity->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-gray-500 py-4">
                            <p>Belum ada aktivitas sistem yang tercatat.</p>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>
    </body>
</html>