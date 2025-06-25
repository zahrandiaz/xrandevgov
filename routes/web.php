<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AccessController;
use App\Http\Controllers\Monitoring\MonitoringSourceController; // Tambahkan ini
use App\Http\Controllers\SelectorPresetController; // TAMBAHKAN BARIS INI

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Rute untuk menampilkan form kode akses
Route::get('/', [AccessController::class, 'showAccessForm'])->name('access.form');

// Rute POST untuk memproses input kode akses
Route::post('/', [AccessController::class, 'handleAccessRequest'])->middleware('access.code'); // Pastikan middleware ini ada

// --- Grup Rute yang Dilindungi Kode Akses ---
Route::middleware('access.code')->group(function () {

    // Rute Logout
    Route::get('/logout', function (Illuminate\Http\Request $request) {
    $request->session()->forget('authenticated_by_access_code');
    return redirect()->route('access.form')->with('status', 'Anda telah berhasil logout.');
    })->name('logout');

    // Rute Dashboard Utama
    Route::get('/dashboard', [MonitoringSourceController::class, 'showDashboard'])->name('dashboard'); // MODIFIKASI BARIS INI

    // Rute untuk Manajemen Situs Monitoring
    Route::get('/monitoring/sources', [MonitoringSourceController::class, 'index'])->name('monitoring.sources.index');
    Route::post('/monitoring/sources', [MonitoringSourceController::class, 'store'])->name('monitoring.sources.store'); // Ini akan kita ubah fungsinya sedikit

    // Rute untuk menampilkan form tambah situs baru
    Route::get('/monitoring/sources/create', [MonitoringSourceController::class, 'create'])->name('monitoring.sources.create'); // Tambah ini
    // Rute untuk menampilkan form edit situs
    Route::get('/monitoring/sources/{source}/edit', [MonitoringSourceController::class, 'edit'])->name('monitoring.sources.edit'); // Tambah ini
    // Rute untuk update situs (pakai PATCH/PUT)
    Route::patch('/monitoring/sources/{source}', [MonitoringSourceController::class, 'update'])->name('monitoring.sources.update'); // Tambah ini
    // Rute untuk hapus situs
    Route::delete('/monitoring/sources/{source}', [MonitoringSourceController::class, 'destroy'])->name('monitoring.sources.destroy'); // Tambah ini

    // Rute untuk menjalankan proses crawling
    Route::post('/monitoring/sources/crawl', [MonitoringSourceController::class, 'crawl'])->name('monitoring.sources.crawl');

    // [BARU] Rute untuk menguji selector secara real-time
    Route::post('/monitoring/sources/test-selector', [MonitoringSourceController::class, 'testSelector'])->name('monitoring.sources.testSelector');

    // [BARU] Rute untuk menampilkan daftar artikel yang di-crawl
    Route::get('/monitoring/articles', [MonitoringSourceController::class, 'listArticles'])->name('monitoring.articles.index');

    // [BARU] Rute untuk manajemen Selector Presets (CRUD)
    Route::resource('selector-presets', SelectorPresetController::class)->except(['show']); // Tidak memerlukan metode 'show'

    // [BARU] Rute untuk manajemen Wilayah (Provinsi & Kab/Kota)
    Route::resource('regions', \App\Http\Controllers\RegionController::class)->except(['show']);
    
    // [BARU] Rute untuk fitur impor data situs
    Route::prefix('import')->name('import.')->group(function () {
        Route::get('/sources', [\App\Http\Controllers\ImportController::class, 'showSourcesForm'])->name('sources.show');
        Route::post('/sources', [\App\Http\Controllers\ImportController::class, 'handleSourcesImport'])->name('sources.handle');
    });
});