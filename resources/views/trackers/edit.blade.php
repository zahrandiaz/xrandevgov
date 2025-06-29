{{-- resources/views/trackers/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Edit Pantauan: ' . $tracker->title)

@section('content')
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
    <h2 class="text-2xl font-semibold text-gray-800 leading-tight mb-6">
        {{ __('Edit Pantauan Pengumuman') }}
    </h2>

    @if ($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            {{-- ... (error handling sama seperti create) ... --}}
        </div>
    @endif

    <form method="POST" action="{{ route('trackers.update', $tracker) }}">
        @csrf
        @method('PATCH')
        <div class="space-y-4">
            <div>
                <label for="title" class="block font-medium text-sm text-gray-700">Judul Pantauan</label>
                <input type="text" id="title" name="title" value="{{ old('title', $tracker->title) }}" required class="block w-full mt-1 border-gray-300 rounded-md shadow-sm">
            </div>
            <div>
                <label for="keywords" class="block font-medium text-sm text-gray-700">Kata Kunci (pisahkan dengan koma)</label>
                <input type="text" id="keywords" name="keywords" value="{{ old('keywords', implode(', ', $tracker->keywords)) }}" required class="block w-full mt-1 border-gray-300 rounded-md shadow-sm">
            </div>
            <div>
                <label for="description" class="block font-medium text-sm text-gray-700">Deskripsi (Opsional)</label>
                <textarea id="description" name="description" rows="3" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm">{{ old('description', $tracker->description) }}</textarea>
            </div>
            <div>
                <label for="status" class="block font-medium text-sm text-gray-700">Status</label>
                <select id="status" name="status" required class="block w-full mt-1 border-gray-300 rounded-md shadow-sm">
                    <option value="Aktif" @selected(old('status', $tracker->status) == 'Aktif')>Aktif</option>
                    <option value="Arsip" @selected(old('status', $tracker->status) == 'Arsip')>Arsip</option>
                </select>
            </div>
        </div>

        <div class="flex items-center justify-end mt-6">
            <a href="{{ route('trackers.index') }}" class="text-gray-600 hover:text-gray-900 mr-4">Batal</a>
            <button type="submit" class="bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600">
                Perbarui Pantauan
            </button>
        </div>
    </form>
</div>
@endsection