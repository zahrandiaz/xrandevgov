<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Xrandev - Tambah Wilayah Baru</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="antialiased bg-gray-100 min-h-screen">
    <div class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <h2 class="text-2xl font-semibold text-gray-800 leading-tight mb-6">
                {{ __('Tambah Wilayah Baru') }}
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

            <form method="POST" action="{{ route('regions.store') }}" x-data="{ type: '{{ old('type', 'Provinsi') }}' }">
                @csrf
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
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required
                            class="block w-full mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            placeholder="Contoh: Jawa Barat atau Kota Bandung">
                    </div>

                    {{-- Pilihan Wilayah Induk (hanya muncul jika tipe adalah Kab/Kota) --}}
                    <div x-show="type === 'Kabupaten/Kota'" x-transition>
                        <label for="parent_id" class="block font-medium text-sm text-gray-700">Wilayah Induk (Provinsi)</label>
                        <select id="parent_id" name="parent_id"
                            class="block w-full mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">-- Pilih Provinsi --</option>
                            @foreach($provinces as $province)
                                <option value="{{ $province->id }}" {{ old('parent_id') == $province->id ? 'selected' : '' }}>
                                    {{ $province->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-end mt-6">
                    <a href="{{ route('regions.index') }}" class="text-gray-600 hover:text-gray-900 mr-4">Batal</a>
                    <button type="submit" class="bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600">
                        Simpan Wilayah
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>