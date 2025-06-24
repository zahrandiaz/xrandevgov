<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Xrandev</title>

        <script src="https://cdn.tailwindcss.com"></script>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <style>
            body {
                font-family: 'Figtree', sans-serif;
            }
        </style>
    </head>
    <body class="antialiased bg-gray-100 flex items-center justify-center min-h-screen">
        <div class="p-6 max-w-sm mx-auto bg-white rounded-xl shadow-md space-y-4">
            <h1 class="text-2xl font-bold text-center text-gray-900">Selamat Datang di Xrandev</h1>
            <p class="text-gray-600 text-center">Silakan masukkan kode akses Anda untuk melanjutkan.</p>

            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <strong class="font-bold">Error!</strong>
                    <span class="block sm:inline">{{ $errors->first('access_code') }}</span>
                </div>
            @endif

            <form action="{{ url('/') }}" method="POST" class="mt-4">
                @csrf <input type="password" name="access_code" placeholder="Kode Akses"
                    class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring focus:border-blue-300">
                <button type="submit"
                    class="mt-4 w-full bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600 focus:outline-none focus:ring focus:border-blue-300">
                    Masuk
                </button>
            </form>

        </div>
    </body>
</html>