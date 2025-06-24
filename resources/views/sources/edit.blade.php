<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Xrandev - Edit Situs Monitoring</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="antialiased bg-gray-100 min-h-screen">
        <div class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h2 class="text-2xl font-semibold text-gray-800 leading-tight mb-6">
                    {{ __('Edit Situs Monitoring') }}
                </h2>

                @if ($errors->any())
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <strong class="font-bold">Oops!</strong>
                        <span class="block sm:inline">Ada beberapa masalah dengan input Anda:</span>
                        <ul class="mt-3 list-disc list-inside text-sm">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('monitoring.sources.update', $source) }}">
                    @csrf
                    @method('PATCH') <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block font-medium text-sm text-gray-700">Nama Situs (misal: Kemendagri)</label>
                            <input type="text" id="name" name="name" value="{{ old('name', $source->name) }}" required
                                class="block w-full mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            @error('name')<p class="text-sm text-red-600 mt-2">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="url" class="block font-medium text-sm text-gray-700">URL Utama Situs (misal: https://www.kemendagri.go.id)</label>
                            <input type="url" id="url" name="url" value="{{ old('url', $source->url) }}" required
                                class="block w-full mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            @error('url')<p class="text-sm text-red-600 mt-2">{{ $message }}</p>@enderror
                        </div>

                        <div class="md:col-span-2">
                            <label for="crawl_url" class="block font-medium text-sm text-gray-700">URL Crawl Spesifik (misal: /berita/ atau /blog/page/1)</label>
                            <input type="text" id="crawl_url" name="crawl_url" value="{{ old('crawl_url', $source->crawl_url) }}"
                                class="block w-full mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <p class="mt-1 text-xs text-gray-500">Path relatif dari URL Utama jika ada daftar berita spesifik. Default: /</p>
                            @error('crawl_url')<p class="text-sm text-red-600 mt-2">{{ $message }}</p>@enderror
                        </div>

                        <div class="md:col-span-2">
                            <label for="selector_title" class="block font-medium text-sm text-gray-700">Selector CSS Judul Berita (misal: h3 a, .post-title a)</label>
                            <input type="text" id="selector_title" name="selector_title" value="{{ old('selector_title', $source->selector_title) }}" required
                                class="block w-full mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <p class="mt-1 text-xs text-gray-500">Contoh: `h3 a` atau `.entry-header h2 a`</p>
                            @error('selector_title')<p class="text-sm text-red-600 mt-2">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="selector_date" class="block font-medium text-sm text-gray-700">Selector CSS Tanggal Berita (misal: .post-date, time)</label>
                            <input type="text" id="selector_date" name="selector_date" value="{{ old('selector_date', $source->selector_date) }}"
                                class="block w-full mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <p class="mt-1 text-xs text-gray-500">Contoh: `.info-meta li` atau `time[datetime]`</p>
                            @error('selector_date')<p class="text-sm text-red-600 mt-2">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="selector_link" class="block font-medium text-sm text-gray-700">Selector CSS Link Berita (Opsional, jika berbeda dari Judul)</label>
                            <input type="text" id="selector_link" name="selector_link" value="{{ old('selector_link', $source->selector_link) }}"
                                class="block w-full mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <p class="mt-1 text-xs text-gray-500">Contoh: `p.read-more a` atau biarkan kosong jika link ada di selector judul.</p>
                            @error('selector_link')<p class="text-sm text-red-600 mt-2">{{ $message }}</p>@enderror
                        </div>

                        <div class="md:col-span-2">
                            <label for="is_active" class="flex items-center">
                                <input type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $source->is_active))
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span class="ms-2 text-sm text-gray-600">Situs Aktif (akan di-crawl)</span>
                            </label>
                            @error('is_active')<p class="text-sm text-red-600 mt-2">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="flex items-center justify-end mt-6">
                        <a href="{{ route('monitoring.sources.index') }}" class="text-gray-600 hover:text-gray-900 mr-4">Batal</a>
                        <button type="submit" class="bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600 focus:outline-none focus:ring focus:border-blue-300">
                            Perbarui Situs
                        </button>
                    </div>
                </form>

                <form method="POST" action="{{ route('monitoring.sources.destroy', $source) }}" class="mt-4">
                    @csrf
                    @method('DELETE')
                    <button type="submit" onclick="return confirm('Apakah Anda yakin ingin menghapus situs ini?')"
                            class="bg-red-500 text-white py-2 px-4 rounded-md hover:bg-red-600 focus:outline-none focus:ring focus:border-red-300">
                        Hapus Situs
                    </button>
                </form>

                <div class="mt-8 text-center">
                    <a href="{{ route('dashboard') }}" class="text-blue-500 hover:underline">Kembali ke Dashboard</a>
                </div>
            </div>
        </div>
    </body>
</html>