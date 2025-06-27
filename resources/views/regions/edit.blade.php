@extends('layouts.app')

@section('title', 'Edit Wilayah')

@section('content')
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
        <h2 class="text-2xl font-semibold text-gray-800 leading-tight mb-6">
            {{ __('Edit Wilayah: ') }} <span class="font-bold">{{ $region->name }}</span>
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

        <form method="POST" action="{{ route('regions.update', $region) }}" x-data="{ type: '{{ old('type', $region->type) }}' }">
            @csrf
            @method('PATCH')
            <div class="space-y-4">
                {{-- Pilihan Tipe Wilayah --}}
                <div>
                    <label for="type" class="block font-medium text-sm text-gray-700">Tipe Wilayah</label>
                    <select id="type" name="type" x-model="type" required
                        class="block w-full mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        <option value="Provinsi">Provinsi</option>
                        <option value="Kabupaten/Kota">Kabupaten/Kota</option>
                    </select>
                </div>

                {{-- Nama Wilayah --}}
                <div>
                    <label for="name" class="block font-medium text-sm text-gray-700">Nama Wilayah</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $region->name) }}" required
                        class="block w-full mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                </div>

                {{-- Pilihan Wilayah Induk --}}
                <div x-show="type === 'Kabupaten/Kota'" x-transition>
                    <label for="parent_id" class="block font-medium text-sm text-gray-700">Wilayah Induk (Provinsi)</label>
                    <select id="parent_id" name="parent_id"
                        class="block w-full mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        <option value="">-- Pilih Provinsi --</option>
                        @foreach($provinces as $province)
                            <option value="{{ $province->id }}" {{ old('parent_id', $region->parent_id) == $province->id ? 'selected' : '' }}>
                                {{ $province->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-end mt-6">
                <a href="{{ route('regions.index') }}" class="text-gray-600 hover:text-gray-900 mr-4">Batal</a>
                <button type="submit" class="bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600">
                    Perbarui Wilayah
                </button>
            </div>
        </form>
    </div>
@endsection