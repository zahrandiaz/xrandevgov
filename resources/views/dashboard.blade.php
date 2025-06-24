<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Dashboard Xrandev</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="antialiased bg-gray-100 flex items-center justify-center min-h-screen">
        <div class="p-6 max-w-xl mx-auto bg-white rounded-xl shadow-md space-y-4 text-center">
            <h1 class="text-3xl font-bold text-gray-900">Selamat Datang di Dashboard Xrandev!</h1>
            <p class="text-lg text-gray-700">Anda berhasil masuk dengan kode akses.</p>
            <div class="mt-6">
                <a href="{{ route('monitoring.sources.index') }}" class="inline-block bg-green-500 text-white py-2 px-4 rounded-md hover:bg-green-600">
                    Pergi ke Government Monitoring
                </a>

                <a href="{{ route('logout') }}" class="inline-block bg-red-500 text-white py-2 px-4 rounded-md hover:bg-red-600 ml-4">
                    Logout
                </a>
            </div>
        </div>
    </body>
</html>