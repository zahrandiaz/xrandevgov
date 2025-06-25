<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Xrandev - Tambah Preset Selector</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="antialiased bg-gray-100 min-h-screen">
        <div class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h2 class="text-2xl font-semibold text-gray-800 leading-tight mb-6">
                    {{ __('Tambah Preset Selector Baru') }}
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

                <form method="POST" action="{{ route('selector-presets.store') }}">
                    @csrf

                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label for="name" class="block font-medium text-sm text-gray-700">Nama Preset (misal: WordPress Default News, Kompas Artikel)</label>
                            <input type="text" id="name" name="name" value="{{ old('name') }}" required
                                class="block w-full mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <p class="mt-1 text-xs text-gray-500">Berikan nama unik untuk preset ini.</p>
                            @error('name')<p class="text-sm text-red-600 mt-2">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="selector_title" class="block font-medium text-sm text-gray-700">Selector CSS Judul Berita (misal: h3 a, .post-title a)</label>
                            <input type="text" id="selector_title" name="selector_title" value="{{ old('selector_title') }}" required
                                class="block w-full mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <p class="mt-1 text-xs text-gray-500">Contoh: `h3 a` atau `.entry-header h2 a`</p>
                            @error('selector_title')<p class="text-sm text-red-600 mt-2">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="selector_date" class="block font-medium text-sm text-gray-700">Selector CSS Tanggal Berita (Opsional, misal: .post-date, time)</label>
                            <input type="text" id="selector_date" name="selector_date" value="{{ old('selector_date') }}"
                                class="block w-full mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <p class="mt-1 text-xs text-gray-500">Contoh: `.info-meta li` atau `time[datetime]`</p>
                            @error('selector_date')<p class="text-sm text-red-600 mt-2">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="selector_link" class="block font-medium text-sm text-gray-700">Selector CSS Link Berita (Opsional, jika berbeda dari Judul)</label>
                            <input type="text" id="selector_link" name="selector_link" value="{{ old('selector_link') }}"
                                class="block w-full mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <p class="mt-1 text-xs text-gray-500">Contoh: `p.read-more a` atau biarkan kosong jika link ada di selector judul.</p>
                            @error('selector_link')<p class="text-sm text-red-600 mt-2">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="flex items-center justify-end mt-6">
                        <a href="{{ route('selector-presets.index') }}" class="text-gray-600 hover:text-gray-900 mr-4">Batal</a>
                        <button type="submit" class="bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600 focus:outline-none focus:ring focus:border-blue-300">
                            Simpan Preset
                        </button>
                    </div>
                </form>

                <div class="mt-8 text-center">
                    <a href="{{ route('dashboard') }}" class="text-blue-500 hover:underline">Kembali ke Dashboard</a>
                </div>
            </div>
        </div>
    </body>
</html>