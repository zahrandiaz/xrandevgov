<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Xrandev - Manajemen Preset Selector</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="antialiased bg-gray-100 min-h-screen">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h2 class="text-2xl font-semibold text-gray-800 leading-tight mb-6">
                    {{ __('Manajemen Preset Selector') }}
                </h2>

                @if (session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline">{{ session('success') }}</span>
                    </div>
                @endif

                @if (session('error'))
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline">{{ session('error') }}</span>
                    </div>
                @endif

                <div class="flex justify-end mb-6">
                    <a href="{{ route('selector-presets.create') }}" class="bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600 focus:outline-none focus:ring focus:border-blue-300">
                        Tambah Preset Baru
                    </a>
                </div>

                <h3 class="text-lg font-medium text-gray-900 mb-4">Daftar Preset Selector</h3>
                @if ($presets->isEmpty())
                    <p class="text-gray-600">Belum ada preset selector yang ditambahkan. Silakan tambah preset baru.</p>
                @else
                    <div class="overflow-x-auto shadow-md sm:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Preset</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Selector Judul (Sebagian)</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Selector Tanggal (Sebagian)</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Selector Link (Sebagian)</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($presets as $preset)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $preset->name }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-500 truncate max-w-xs">{{ Str::limit($preset->selector_title, 40) }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-500 truncate max-w-xs">{{ Str::limit($preset->selector_date, 40) }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-500 truncate max-w-xs">{{ Str::limit($preset->selector_link, 40) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="{{ route('selector-presets.edit', $preset) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                                            <form action="{{ route('selector-presets.destroy', $preset) }}" method="POST" class="inline-block">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" onclick="return confirm('Apakah Anda yakin ingin menghapus preset {{ $preset->name }}? Ini mungkin mempengaruhi situs monitoring yang menggunakannya.')"
                                                        class="text-red-600 hover:text-red-900">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <div class="mt-8 text-center">
                    <a href="{{ route('dashboard') }}" class="text-blue-500 hover:underline mr-4">Kembali ke Dashboard</a>
                    <a href="{{ route('monitoring.sources.index') }}" class="text-blue-500 hover:underline">Manajemen Situs Monitoring</a>
                </div>
            </div>
        </div>
    </body>
</html>