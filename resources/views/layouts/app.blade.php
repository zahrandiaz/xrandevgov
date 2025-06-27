<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Xrandev')</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    @stack('styles')
</head>
<body class="antialiased bg-gray-100 min-h-screen">

    {{-- [BARU] Memasang komponen notifikasi toast --}}
    <x-toast-notifications />

    <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        @yield('content')
    </div>

    {{-- [BARU] Script untuk memicu notifikasi dari session flash message --}}
    @if(session()->has('notify'))
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                window.dispatchEvent(new CustomEvent('notify', {
                    detail: {
                        type: '{{ session('notify')[0] }}', // Tipe: success, error, dll.
                        message: '{{ session('notify')[1] }}' // Isi pesan notifikasi
                    }
                }));
            });
        </script>
    @endif

    @stack('scripts')
</body>
</html>