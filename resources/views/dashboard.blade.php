<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Dashboard Xrandev</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="antialiased bg-gray-100 flex items-center justify-center min-h-screen">
        <div class="p-6 max-w-xl mx-auto bg-white rounded-xl shadow-md space-y-4 text-center">
            <h1 class="text-3xl font-bold text-gray-900 mb-4">Selamat Datang di Dashboard Xrandev!</h1>
            <p class="text-lg text-gray-700 mb-8">Anda berhasil masuk dengan kode akses.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                {{-- [BARU] Kartu Statistik Kesehatan Crawl --}}
                <div class="bg-teal-100 p-4 rounded-lg shadow-md text-center">
                    <p class="text-gray-600 text-sm">Tingkat Sukses Crawl</p>
                    <p class="text-3xl font-bold text-teal-800">{{ $crawlSuccessRate }}%</p>
                </div>

                <div class="bg-blue-100 p-4 rounded-lg shadow-md text-center">
                    <p class="text-gray-600 text-sm">Total Situs Monitoring</p>
                    <p class="text-3xl font-bold text-blue-800">{{ $totalSources }}</p>
                </div>
                <div class="bg-green-100 p-4 rounded-lg shadow-md text-center">
                    <p class="text-gray-600 text-sm">Situs Aktif</p>
                    <p class="text-3xl font-bold text-green-800">{{ $activeSources }}</p>
                </div>
                <div class="bg-red-100 p-4 rounded-lg shadow-md text-center">
                    <p class="text-gray-600 text-sm">Situs Nonaktif</p>
                    <p class="text-3xl font-bold text-red-800">{{ $inactiveSources }}</p>
                </div>
                <div class="bg-purple-100 p-4 rounded-lg shadow-md text-center">
                    <p class="text-gray-600 text-sm">Total Artikel Di-crawl</p>
                    <p class="text-3xl font-bold text-purple-800">{{ $totalArticles }}</p>
                </div>
                <div class="bg-yellow-100 p-4 rounded-lg shadow-md text-center">
                    <p class="text-gray-600 text-sm">Artikel Baru Hari Ini</p>
                    <p class="text-3xl font-bold text-yellow-800">{{ $newArticlesToday }}</p>
                </div>
                <div class="bg-gray-200 p-4 rounded-lg shadow-md text-center">
                    <p class="text-gray-600 text-sm">Artikel Baru 7 Hari Terakhir</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $newArticlesLast7Days }}</p>
                </div>

                
            </div>

            <div class="mt-6 flex flex-wrap justify-center gap-4">
                <a href="{{ route('monitoring.sources.index') }}" class="inline-block bg-green-500 text-white py-2 px-4 rounded-md hover:bg-green-600">
                    Pergi ke Manajemen Situs
                </a>
                <a href="{{ route('monitoring.articles.index') }}" class="inline-block bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600">
                    Lihat Semua Artikel
                </a>
                <a href="{{ route('logout') }}" class="inline-block bg-red-500 text-white py-2 px-4 rounded-md hover:bg-red-600">
                    Logout
                </a>
            </div>

            @if($latestCrawls->isNotEmpty())
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">Status Crawling Terbaru (5 Situs Terakhir):</h3>
                    <ul class="text-sm text-gray-700 space-y-2">
                        @foreach($latestCrawls as $source)
                            <li class="flex justify-between items-center bg-gray-50 p-3 rounded-md">
                                <span>{{ $source->name }} ({{ $source->url }})</span>
                                @if($source->last_crawled_at)
                                    <span class="text-gray-600">Terakhir di-crawl: {{ $source->last_crawled_at->diffForHumans() }}</span>
                                @else
                                    <span class="text-gray-600">Belum pernah di-crawl</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif



            {{-- [BARU] Daftar Situs Bermasalah --}}
            @if($problematicSources->isNotEmpty())
                <div class="mt-8 pt-6 border-t border-gray-200 text-left">
                    <h3 class="text-lg font-semibold text-red-800 mb-3">Situs Bermasalah (Gagal >= 3x)</h3>
                    <div class="bg-red-50 border-l-4 border-red-400 p-4">
                        <p class="text-sm text-red-700">Situs berikut mengalami kegagalan *crawling* beberapa kali berturut-turut. Kemungkinan *selector*-nya perlu diperbarui.</p>
                        <ul class="text-sm text-gray-700 space-y-2 mt-3">
                            @foreach($problematicSources as $source)
                                <li class="flex justify-between items-center bg-white p-3 rounded-md shadow-sm">
                                    <div>
                                        <a href="{{ route('monitoring.sources.edit', $source) }}" class="font-semibold text-indigo-600 hover:underline">{{ $source->name }}</a>
                                        <span class="text-xs text-gray-500 block">{{ $source->url }}</span>
                                    </div>
                                    <span class="text-red-600 font-bold">{{ $source->consecutive_failures }}x Gagal</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </body>
</html>