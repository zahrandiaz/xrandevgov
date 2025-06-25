<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
// [BARU] Kita akan butuh semua ini nanti
use App\Models\MonitoringSource;
use App\Models\Region;
use League\Csv\Reader;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ImportController extends Controller
{
    /**
     * Menampilkan halaman formulir untuk mengunggah file CSV.
     *
     * @return \Illuminate\View\View
     */
    public function showSourcesForm()
    {
        // Cukup tampilkan view yang sudah kita buat
        return view('import.sources');
    }

    /**
     * Menangani logika pemrosesan file CSV yang diunggah.
     * (Akan kita isi di langkah selanjutnya)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleSourcesImport(Request $request)
    {
        // 1. Validasi file yang diunggah
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048', // Wajib, harus file, ekstensi csv/txt, maks 2MB
        ]);

        $file = $request->file('csv_file');

        // Gunakan try-catch untuk menangani potensi error
        try {
            // 2. Baca file CSV menggunakan library
            $csv = Reader::createFromPath($file->getRealPath(), 'r');
            $csv->setHeaderOffset(0); // Anggap baris pertama adalah header

            $records = $csv->getRecords();
            $processedCount = 0;
            $skippedCount = 0;

            // 3. Gunakan Transaksi Database
            // Jika ada satu baris saja yang error, semua data yang sudah dimasukkan akan dibatalkan (rollback).
            DB::beginTransaction();

            // Cache data wilayah untuk mengurangi query ke DB di dalam loop
            $regions = Region::where('type', 'Kabupaten/Kota')->pluck('id', 'name')->all();

            foreach ($records as $record) {
                // 4. Validasi setiap baris
                $nama_situs = trim($record['nama_situs'] ?? '');
                $url_situs = trim($record['url_situs'] ?? '');
                $nama_wilayah = trim($record['nama_wilayah'] ?? '');

                // Lewati baris jika data wajib (nama, url, wilayah) kosong
                if (empty($nama_situs) || empty($url_situs) || empty($nama_wilayah)) {
                    $skippedCount++;
                    continue;
                }

                // Cek apakah wilayah ada di cache kita
                if (!isset($regions[$nama_wilayah])) {
                    // Jika wilayah tidak ditemukan, lewati baris ini
                    Log::warning("Impor CSV: Wilayah '{$nama_wilayah}' tidak ditemukan untuk situs '{$nama_situs}'. Baris dilewati.");
                    $skippedCount++;
                    continue;
                }
                $region_id = $regions[$nama_wilayah];

                // 5. Simpan atau Perbarui Data
                // 'updateOrCreate' akan mencari situs dengan URL yang sama.
                // Jika ditemukan, data akan diperbarui. Jika tidak, data baru akan dibuat.
                MonitoringSource::updateOrCreate(
                    ['url' => $url_situs],
                    [
                        'name' => $nama_situs,
                        'region_id' => $region_id,
                        'crawl_url' => trim($record['url_crawl'] ?? '/'),
                        'selector_title' => trim($record['selector_title'] ?? ''),
                        'selector_date' => trim($record['selector_date'] ?? ''),
                        'selector_link' => trim($record['selector_link'] ?? ''),
                        'is_active' => true, // Default ke aktif saat impor
                    ]
                );

                $processedCount++;
            }

            // 6. Jika semua berhasil, commit transaksi
            DB::commit();

            return redirect()->route('import.sources.show')
                ->with('success', "Proses impor selesai! Berhasil memproses {$processedCount} situs dan melewati {$skippedCount} situs.");

        } catch (\Exception $e) {
            // 7. Jika terjadi error, batalkan semua
            DB::rollBack();
            Log::error("Gagal melakukan impor CSV: " . $e->getMessage());

            return redirect()->route('import.sources.show')
                ->with('error', 'Terjadi kesalahan fatal saat memproses file. Silakan periksa format file CSV Anda atau lihat log sistem. Error: ' . $e->getMessage());
        }
    }
}