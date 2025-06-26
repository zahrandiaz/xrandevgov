<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Xrandev - Manajemen Kamus Selector</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="antialiased bg-gray-100 min-h-screen">
    <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-semibold text-gray-800 leading-tight">
                    {{ __('Manajemen Kamus Selector Saran') }}
                </h2>
                <a href="{{ route('suggestion-selectors.create') }}" class="bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600 focus:outline-none focus:ring focus:border-blue-300">
                    Tambah Selector Baru
                </a>
            </div>

            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <div class="space-y-8">
                {{-- Tabel untuk Selector Judul --}}
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Selector Tipe: Judul (Title)</h3>
                    @if(isset($selectors['title']) && $selectors['title']->isNotEmpty())
                        <div class="overflow-x-auto shadow-md sm:rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Selector</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prioritas</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($selectors['title'] as $selector)
                                        <tr>
                                            <td class="px-6 py-4 text-sm font-mono text-gray-900">{{ $selector->selector }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $selector->priority }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <a href="{{ route('suggestion-selectors.edit', $selector) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                                                <form action="{{ route('suggestion-selectors.destroy', $selector) }}" method="POST" class="inline-block" onsubmit="return confirm('Yakin ingin menghapus selector ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900">Hapus</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-gray-500">Belum ada selector saran untuk tipe 'Judul'.</p>
                    @endif
                </div>

                {{-- Tabel untuk Selector Tanggal --}}
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Selector Tipe: Tanggal (Date)</h3>
                    @if(isset($selectors['date']) && $selectors['date']->isNotEmpty())
                        <div class="overflow-x-auto shadow-md sm:rounded-lg">
                             <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Selector</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prioritas</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($selectors['date'] as $selector)
                                        <tr>
                                            <td class="px-6 py-4 text-sm font-mono text-gray-900">{{ $selector->selector }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $selector->priority }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <a href="{{ route('suggestion-selectors.edit', $selector) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                                                <form action="{{ route('suggestion-selectors.destroy', $selector) }}" method="POST" class="inline-block" onsubmit="return confirm('Yakin ingin menghapus selector ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900">Hapus</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                         <p class="text-gray-500">Belum ada selector saran untuk tipe 'Tanggal'.</p>
                    @endif
                </div>
            </div>
            <div class="mt-8 text-center">
                <a href="{{ route('dashboard') }}" class="text-blue-500 hover:underline">Kembali ke Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>