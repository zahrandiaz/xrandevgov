@extends('layouts.app')

@section('title', 'Manajemen Wilayah')

@section('content')
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-semibold text-gray-800 leading-tight">
                {{ __('Manajemen Wilayah') }}
            </h2>
            <a href="{{ route('regions.create') }}" class="bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600 focus:outline-none focus:ring focus:border-blue-300">
                Tambah Wilayah Baru
            </a>
        </div>

        {{-- [DIHAPUS] Blok notifikasi lama dihapus karena sudah ditangani oleh layout --}}

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
            <a href="{{ route('dashboard') }}" class="text-blue-500 hover:underline">Kembali ke Dashboard</a>
     </div>
@endsection