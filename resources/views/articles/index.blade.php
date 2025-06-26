<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Xrandev - Daftar Artikel</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="antialiased bg-gray-100 min-h-screen">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h2 class="text-2xl font-semibold text-gray-800 leading-tight mb-4">
                    {{ __('Daftar Artikel yang Di-crawl') }}
                </h2>

                {{-- Form Pencarian dan Filter --}}
                <div class="mb-6 bg-gray-50 p-4 rounded-lg border">
                    <form action="{{ route('monitoring.articles.index') }}" method="GET" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="keyword" class="block text-sm font-medium text-gray-700">Cari Judul Artikel</label>
                                <input type="text" name="keyword" id="keyword" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                       value="{{ $filters['keyword'] ?? '' }}" placeholder="Masukkan kata kunci...">
                            </div>
                            <div>
                                <label for="start_date" class="block text-sm font-medium text-gray-700">Tanggal Publikasi (Mulai)</label>
                                <input type="date" name="start_date" id="start_date" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                       value="{{ $filters['start_date'] ?? '' }}">
                            </div>
                            <div>
                                <label for="end_date" class="block text-sm font-medium text-gray-700">Tanggal Publikasi (Akhir)</label>
                                <input type="date" name="end_date" id="end_date" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                       value="{{ $filters['end_date'] ?? '' }}">
                            </div>
                        </div>
                        <div class="flex items-center justify-end space-x-3">
                            <a href="{{ route('monitoring.articles.index') }}" class="text-gray-600 hover:text-gray-900 text-sm">Reset Filter</a>
                            <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">
                                Cari
                            </button>
                        </div>
                    </form>
                </div>

                @if (session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline">{{ session('success') }}</span>
                    </div>
                @endif
                {{-- ... (notifikasi lain) ... --}}

                @if ($articles->isEmpty())
                    <div class="text-center py-10">
                        <p class="text-gray-600 font-semibold">Tidak ada artikel yang ditemukan.</p>
                        <p class="text-sm text-gray-500 mt-2">Coba ubah atau reset filter pencarian Anda.</p>
                    </div>
                @else
                    <div class="overflow-x-auto shadow-md sm:rounded-lg mb-6">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Judul</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sumber</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Publikasi</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu Crawl</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th> {{-- [BARU] Kolom Aksi --}}
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($articles as $article)
                                    <tr>
                                        <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                            <div class="max-w-md">{{ $article->title }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $article->source->name ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $article->published_date?->format('Y-m-d') ?? 'Tidak Diketahui' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $article->crawled_at?->format('Y-m-d H:i') ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            {{-- [BARU] Tombol Lihat dan Hapus --}}
                                            <div class="flex items-center space-x-4">
                                                <a href="{{ $article->url }}" target="_blank" class="text-blue-600 hover:text-blue-900">Lihat</a>
                                                <form action="{{ route('monitoring.articles.destroy', $article->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus artikel ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900">Hapus</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Paginasi --}}
                    <div class="mt-4">
                        {{ $articles->links('pagination::tailwind') }}
                    </div>

                @endif

                <div class="mt-8 text-center">
                    <a href="{{ route('dashboard') }}" class="text-blue-500 hover:underline mr-4">Kembali ke Dashboard</a>
                    <a href="{{ route('monitoring.sources.index') }}" class="text-blue-500 hover:underline">Kembali ke Manajemen Situs</a>
                </div>
            </div>
        </div>
    </body>
</html>