<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Xrandev - Tambah Selector Saran</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="antialiased bg-gray-100 min-h-screen">
    <div class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <h2 class="text-2xl font-semibold text-gray-800 leading-tight mb-6">
                {{ __('Tambah Selector Saran Baru') }}
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

            <form method="POST" action="{{ route('suggestion-selectors.store') }}">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label for="type" class="block font-medium text-sm text-gray-700">Tipe Selector</label>
                        <select id="type" name="type" required
                            class="block w-full mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="title" {{ old('type') == 'title' ? 'selected' : '' }}>Judul (Title)</option>
                            <option value="date" {{ old('type') == 'date' ? 'selected' : '' }}>Tanggal (Date)</option>
                        </select>
                    </div>

                    <div>
                        <label for="selector" class="block font-medium text-sm text-gray-700">Isi Selector CSS</label>
                        <input type="text" id="selector" name="selector" value="{{ old('selector') }}" required
                            class="block w-full mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm font-mono"
                            placeholder="Contoh: h2.entry-title a">
                    </div>

                    <div>
                        <label for="priority" class="block font-medium text-sm text-gray-700">Prioritas (Angka lebih tinggi dicoba dulu)</label>
                        <input type="number" id="priority" name="priority" value="{{ old('priority', 0) }}" required
                            class="block w-full mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                    </div>
                </div>

                <div class="flex items-center justify-end mt-6">
                    <a href="{{ route('suggestion-selectors.index') }}" class="text-gray-600 hover:text-gray-900 mr-4">Batal</a>
                    <button type="submit" class="bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600">
                        Simpan Selector
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>