<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Xrandev - Manajemen Wilayah</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="antialiased bg-gray-100 min-h-screen">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-semibold text-gray-800 leading-tight">
                        {{ __('Manajemen Wilayah') }}
                    </h2>
                    <a href="{{ route('regions.create') }}" class="bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600 focus:outline-none focus:ring focus:border-blue-300">
                        Tambah Wilayah Baru
                    </a>
                </div>

                @if (session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline">{{ session('success') }}</span>
                    </div>
                @endif

                <div class="space-y-4">
                    @forelse($provinces as $province)
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <div class="flex justify-between items-center">
                                <h3 class="text-lg font-medium text-gray-900">{{ $province->name }}</h3>
                                <div class="flex space-x-3">
                                    <a href="{{ route('regions.edit', $province) }}" class="text-sm text-indigo-600 hover:text-indigo-900">Edit Provinsi</a>
                                    <form action="{{ route('regions.destroy', $province) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus provinsi ini? SEMUA kabupaten/kota di bawahnya juga akan terhapus.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm text-red-600 hover:text-red-900">Hapus</button>
                                    </form>
                                </div>
                            </div>
                            <div class="border-t border-gray-200 mt-3 pt-3 pl-4 space-y-2">
                                @forelse($province->children as $kabkota)
                                    <div class="flex justify-between items-center">
                                        <p class="text-gray-700">- {{ $kabkota->name }}</p>
                                        <div class="flex space-x-3">
                                            <a href="{{ route('regions.edit', $kabkota) }}" class="text-sm text-indigo-600 hover:text-indigo-900">Edit</a>
                                            <form action="{{ route('regions.destroy', $kabkota) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus wilayah ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-sm text-red-600 hover:text-red-900">Hapus</button>
                                            </form>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-sm text-gray-500">Belum ada Kabupaten/Kota di provinsi ini.</p>
                                @endforelse
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-600">Belum ada data wilayah yang ditambahkan. Silakan klik "Tambah Wilayah Baru".</p>
                    @endforelse
                </div>
            </div>
            <div class="mt-8 text-center">
                    <a href="{{ route('dashboard') }}" class="text-blue-500 hover:underline mr-4">Kembali ke Dashboard</a>
                    <a href="{{ route('monitoring.sources.index') }}" class="text-blue-500 hover:underline">Kembali ke Manajemen Situs</a>
            </div>
        </div>
    </body>
</html>