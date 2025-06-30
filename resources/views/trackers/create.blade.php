{{-- resources/views/trackers/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Buat Pantauan Baru')

@section('content')
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
    <h2 class="text-2xl font-semibold text-gray-800 leading-tight mb-6">
        {{ __('Buat Pantauan Pengumuman Baru') }}
    </h2>

    @if ($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <strong class="font-bold">Oops! Ada beberapa masalah:</strong>
            <ul class="mt-2 list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('trackers.store') }}">
        @csrf
        <div class="space-y-4">
            <div>
                <label for="title" class="block font-medium text-sm text-gray-700">Judul Pantauan</label>
                <input type="text" id="title" name="title" value="{{ old('title') }}" required class="block w-full mt-1 border-gray-300 rounded-md shadow-sm" placeholder="Contoh: Pengumuman Kelulusan PPPK Guru 2025">
            </div>
            <div>
                <label for="keywords" class="block font-medium text-sm text-gray-700">Kata Kunci (pisahkan dengan koma)</label>
                <input type="text" id="keywords" name="keywords" value="{{ old('keywords') }}" required class="block w-full mt-1 border-gray-300 rounded-md shadow-sm" placeholder="Contoh: pppk, guru, kelulusan, hasil akhir">
                <p class="mt-1 text-xs text-gray-500">Kata kunci ini akan digunakan untuk mencari artikel yang relevan.</p>
            </div>
            
            {{-- [BARU v1.27.1] TAMBAHKAN BLOK DI BAWAH INI --}}
            <div>
                <label class="block font-medium text-sm text-gray-700">Mode Pencarian</label>
                <div class="mt-2 space-y-2">
                    <label class="inline-flex items-center">
                        <input type="radio" class="form-radio" name="search_mode" value="OR" @checked(old('search_mode', 'OR') == 'OR')>
                        <span class="ml-2">Mode ATAU (OR)</span>
                    </label>
                    <p class="text-xs text-gray-500 ml-6">Menemukan artikel jika mengandung **SALAH SATU** dari kata kunci. (Hasil lebih luas)</p>
                    <label class="inline-flex items-center">
                        <input type="radio" class="form-radio" name="search_mode" value="AND" @checked(old('search_mode') == 'AND')>
                        <span class="ml-2">Mode DAN (AND)</span>
                    </label>
                    <p class="text-xs text-gray-500 ml-6">Menemukan artikel jika mengandung **SEMUA** kata kunci. (Hasil lebih spesifik)</p>
                </div>
            </div>
            {{-- AKHIR BLOK BARU --}}

            <div>
                <label for="description" class="block font-medium text-sm text-gray-700">Deskripsi (Opsional)</label>
                <textarea id="description" name="description" rows="3" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm">{{ old('description') }}</textarea>
            </div>
             <div>
                <label for="status" class="block font-medium text-sm text-gray-700">Status</label>
                <select id="status" name="status" required class="block w-full mt-1 border-gray-300 rounded-md shadow-sm">
                    <option value="Aktif" @selected(old('status', 'Aktif') == 'Aktif')>Aktif</option>
                    <option value="Arsip" @selected(old('status') == 'Arsip')>Arsip</option>
                </select>
            </div>
        </div>

        <div class="flex items-center justify-end mt-6">
            <a href="{{ route('trackers.index') }}" class="text-gray-600 hover:text-gray-900 mr-4">Batal</a>
            <button type="submit" class="bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600">
                Simpan Pantauan
            </button>
        </div>
    </form>
</div>
@endsection