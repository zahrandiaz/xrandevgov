<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AccessController extends Controller
{
    /**
     * Show the form for entering the access code.
     *
     * @return \Illuminate\View\View
     */
    public function showAccessForm()
    {
        return view('welcome'); // Menggunakan welcome.blade.php sebagai halaman akses
    }

    /**
     * Handle the access code submission.
     * The actual validation and redirection are handled by AccessCodeMiddleware.
     * This method primarily exists to have a target for the POST request.
     * If the middleware doesn't redirect, it means validation failed,
     * and the user will be redirected back to the form with errors (if any were added by validator).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleAccessRequest(Request $request)
    {
        // Middleware AccessCodeMiddleware akan menangani logika inti
        // Jika kode tidak valid, middleware akan redirect kembali ke '/'
        // Kita bisa menambahkan flash message di sini jika dibutuhkan untuk error spesifik,
        // tapi middleware sudah menangani redirect ke 'access.form' (yaitu '/') jika gagal
        return redirect()->route('access.form')
                         ->withErrors(['access_code' => 'Kode akses salah.']);
    }
}