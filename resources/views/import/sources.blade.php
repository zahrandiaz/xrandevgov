<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Xrandev - Impor Data Situs</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="antialiased bg-gray-100 min-h-screen">
        <div class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h2 class="text-2xl font-semibold text-gray-800 leading-tight mb-4">
                    {{ __('Impor Data Situs Monitoring dari CSV') }}
                </h2>

                {{-- Menampilkan pesan sukses atau error dari sesi --}}
                @if (session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <strong class="font-bold">Berhasil!</strong>
                        <span class="block sm:inline">{{ session('success') }}</span>
                    </div>
                @endif
                @if (session('error'))
                     <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <strong class="font-bold">Error!</strong>
                        <span class="block sm:inline">{{ session('error') }}</span>
                    </div>
                @endif
                 @if ($errors->any())
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <strong class="font-bold">Oops! Ada masalah dengan file Anda:</strong>
                        <ul class="mt-2 list-disc list-inside text-sm">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif


                {{-- Petunjuk Penggunaan --}}
                    <div class="mb-6 bg-blue-50 border-l-4 border-blue-400 p-4">
                        <h4 class="font-bold text-lg text-gray-800">Petunjuk Penggunaan:</h4>
                        <ol class="list-decimal list-inside mt-2 text-gray-700 space-y-1">
                            <li>Gunakan file CSV dengan header yang sesuai. Header wajib: <strong>`nama_situs`</strong>, <strong>`url_situs`</strong>, <strong>`nama_wilayah`</strong>, dan <strong>`tipe_instansi`</strong>.</li>
                            <li>Kolom <strong>`tipe_instansi`</strong> harus diisi dengan `BKD` atau `BKPSDM` (huruf besar/kecil tidak masalah).</li>
                            <li>Kolom <strong>`nama_wilayah`</strong> harus berisi nama Provinsi atau Kab/Kota yang <strong>sudah ada</strong> di database.</li>
                            <li>Pastikan ada kesesuaian: `BKD` harus dengan wilayah Provinsi, dan `BKPSDM` dengan wilayah Kabupaten/Kota.</li>
                            <li>Kolom opsional: <strong>`url_crawl`</strong>, <strong>`selector_title`</strong>, <strong>`selector_date`</strong>, <strong>`selector_link`</strong>.</li>
                        </ol>
                    </div>

                {{-- Form Upload --}}
                <form action="{{ route('import.sources.handle') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div>
                        <label for="csv_file" class="block font-medium text-sm text-gray-700">Pilih File CSV</label>
                        <input type="file" name="csv_file" id="csv_file" required
                               class="block w-full mt-1 text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none">
                        <p class="mt-1 text-sm text-gray-500">File harus berupa .csv dan ukurannya tidak lebih dari 2MB.</p>
                    </div>

                    <div class="flex items-center justify-end mt-4">
                        <button type="submit" class="bg-purple-600 text-white py-2 px-4 rounded-md hover:bg-purple-700">
                            Mulai Proses Impor
                        </button>
                    </div>
                </form>

            </div>
            <div class="mt-8 text-center">
                    <a href="{{ route('dashboard') }}" class="text-blue-500 hover:underline mr-4">Kembali ke Dashboard</a>
                    <a href="{{ route('monitoring.sources.index') }}" class="text-blue-500 hover:underline">Kembali ke Manajemen Situs</a>
                </div>
        </div>
    </body>
</html>