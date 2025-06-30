<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AccessController;
use App\Http\Controllers\Monitoring\MonitoringSourceController;
use App\Http\Controllers\SelectorPresetController;
use App\Http\Controllers\SuggestionSelectorController; // [BARU] Impor controller kamus

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
    Route::get('/dashboard', [MonitoringSourceController::class, 'showDashboard'])->name('dashboard');

    // Rute untuk Manajemen Situs Monitoring
    Route::get('/monitoring/sources', [MonitoringSourceController::class, 'index'])->name('monitoring.sources.index');
    Route::post('/monitoring/sources', [MonitoringSourceController::class, 'store'])->name('monitoring.sources.store');
    Route::get('/monitoring/sources/create', [MonitoringSourceController::class, 'create'])->name('monitoring.sources.create');
    Route::get('/monitoring/sources/{source}/edit', [MonitoringSourceController::class, 'edit'])->name('monitoring.sources.edit');
    Route::patch('/monitoring/sources/{source}', [MonitoringSourceController::class, 'update'])->name('monitoring.sources.update');
    Route::delete('/monitoring/sources/{source}', [MonitoringSourceController::class, 'destroy'])->name('monitoring.sources.destroy');

    // Rute untuk proses crawling
    Route::post('/monitoring/sources/crawl', [MonitoringSourceController::class, 'crawl'])->name('monitoring.sources.crawl');
    Route::post('/monitoring/sources/{source}/crawl-single', [MonitoringSourceController::class, 'crawlSingle'])->name('monitoring.sources.crawl_single');

    // Rute untuk fitur interaktif
    Route::post('/monitoring/sources/test-selector', [MonitoringSourceController::class, 'testSelector'])->name('monitoring.sources.testSelector');
    Route::post('/monitoring/sources/suggest-selectors-ajax', [MonitoringSourceController::class, 'suggestSelectorsAjax'])->name('monitoring.sources.suggest_selectors_ajax');

    // [BARU v1.21] Rute untuk fitur Inspektur DOM
    Route::post('/monitoring/sources/inspect-html', [MonitoringSourceController::class, 'inspectHtml'])->name('monitoring.sources.inspect_html');

    // Rute untuk daftar artikel
    Route::get('/monitoring/articles', [MonitoringSourceController::class, 'listArticles'])->name('monitoring.articles.index');
    Route::delete('/monitoring/articles/{article}', [MonitoringSourceController::class, 'destroyArticle'])->name('monitoring.articles.destroy');
    
    // [BARU v1.27.1] Rute untuk mereset kegagalan crawl dari Dashboard
    Route::post('/monitoring/reset-failures', [MonitoringSourceController::class, 'resetFailures'])->name('monitoring.reset_failures');

    // Rute untuk manajemen data master
    Route::resource('selector-presets', SelectorPresetController::class)->except(['show']);
    Route::resource('regions', \App\Http\Controllers\RegionController::class)->except(['show']);
    
    // [BARU] Rute untuk manajemen Kamus Selector Saran (CRUD)
    Route::resource('suggestion-selectors', SuggestionSelectorController::class)->except(['show']);

    // [BARU v1.27.0] Rute untuk Manajemen Pantauan Pengumuman
    Route::resource('trackers', \App\Http\Controllers\TrackerController::class);

    // Rute untuk fitur impor data
    Route::prefix('import')->name('import.')->group(function () {
        Route::get('/sources', [\App\Http\Controllers\ImportController::class, 'showSourcesForm'])->name('sources.show');
        Route::post('/sources', [\App\Http\Controllers\ImportController::class, 'handleSourcesImport'])->name('sources.handle');
    });
});