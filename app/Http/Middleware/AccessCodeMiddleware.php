<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log; // TAMBAHKAN BARIS INI

class AccessCodeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Ambil kode akses dari input form
        $accessCode = $request->input('access_code');

        // Ambil kode akses yang benar dari .env
        $correctAccessCode = env('APP_ACCESS_CODE');

        // --- AKTIFKAN BARIS DEBUGGING INI ---
        Log::info('Access attempt:');
        Log::info('Input Code: ' . $accessCode);
        Log::info('Correct Code: ' . $correctAccessCode);
        Log::info('Codes match: ' . ($accessCode === $correctAccessCode ? 'Yes' : 'No'));
        Log::info('Session has authenticated_by_access_code: ' . ($request->session()->has('authenticated_by_access_code') ? 'Yes' : 'No'));
        // --- AKHIR BARIS DEBUGGING ---

        // Periksa apakah kode akses sudah ada di session
        // Jika ada dan benar, biarkan request berlanjut
        if ($request->session()->has('authenticated_by_access_code') &&
            $request->session()->get('authenticated_by_access_code') === $correctAccessCode) {
            return $next($request);
        }

        // Jika kode akses diinput dan cocok, simpan ke session dan redirect ke dashboard
        if ($accessCode && $accessCode === $correctAccessCode) {
            $request->session()->put('authenticated_by_access_code', $correctAccessCode);
            return redirect()->route('dashboard'); // Kita akan membuat rute ini nanti
        }

        // Jika tidak ada kode yang diinput atau salah, tampilkan halaman input kode
        // Hanya tampilkan halaman input kode jika route saat ini bukan 'access.form'
        if ($request->route()->getName() !== 'access.form') {
            return redirect()->route('access.form');
        }

        return $next($request);
    }
}