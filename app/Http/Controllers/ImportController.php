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
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('csv_file');

        try {
            // 2. Baca file CSV
            $csv = Reader::createFromPath($file->getRealPath(), 'r');
            $csv->setHeaderOffset(0);

            $records = $csv->getRecords();
            $processedCount = 0;
            $skippedCount = 0;

            DB::beginTransaction();

            // [FIX] Cache SEMUA data wilayah, bukan hanya Kab/Kota
            $regions = Region::all()->keyBy('name');

            // [DEBUGGING] Tampilkan semua nama wilayah yang ada di database, lalu hentikan script
            //dd($regions->keys());

            foreach ($records as $index => $record) {
                $rowNumber = $index + 2;

                // 4. Validasi setiap baris
                $nama_situs = trim($record['nama_situs'] ?? '');
                $url_situs = trim($record['url_situs'] ?? '');
                $nama_wilayah = trim($record['nama_wilayah'] ?? '');
                $tipe_instansi = trim(strtoupper($record['tipe_instansi'] ?? ''));

                if (empty($nama_situs) || empty($url_situs) || empty($nama_wilayah) || empty($tipe_instansi)) {
                    $skippedCount++;
                    continue;
                }

                if (!in_array($tipe_instansi, ['BKD', 'BKPSDM'])) {
                     $skippedCount++;
                     continue;
                }

                if (!$regions->has($nama_wilayah)) {
                    $skippedCount++;
                    Log::warning("Impor CSV Baris {$rowNumber}: Wilayah '{$nama_wilayah}' tidak ditemukan. Baris dilewati.");
                    continue;
                }
                
                $region = $regions->get($nama_wilayah);

                if (($tipe_instansi === 'BKD' && $region->type !== 'Provinsi') || ($tipe_instansi === 'BKPSDM' && $region->type !== 'Kabupaten/Kota')) {
                    $skippedCount++;
                    Log::warning("Impor CSV Baris {$rowNumber}: Ketidaksesuaian antara Tipe Instansi '{$tipe_instansi}' dan Tipe Wilayah '{$region->type}'. Baris dilewati.");
                    continue;
                }

                // 5. Simpan atau Perbarui Data
                MonitoringSource::updateOrCreate(
                    ['url' => $url_situs],
                    [
                        'name' => $nama_situs,
                        'region_id' => $region->id,
                        'tipe_instansi' => $tipe_instansi,
                        'crawl_url' => trim($record['url_crawl'] ?? '/'),
                        'selector_title' => trim($record['selector_title'] ?? ''),
                        'selector_date' => trim($record['selector_date'] ?? ''),
                        'selector_link' => trim($record['selector_link'] ?? ''),
                        'is_active' => true,
                    ]
                );

                $processedCount++;
            }

            DB::commit();

            return redirect()->route('import.sources.show')
                ->with('success', "Proses impor selesai! Berhasil memproses {$processedCount} situs dan melewati {$skippedCount} situs.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal melakukan impor CSV: " . $e->getMessage());
            return redirect()->route('import.sources.show')
                ->with('error', 'Terjadi kesalahan fatal saat memproses file. Pastikan format header CSV sudah benar. Error: ' . $e->getMessage());
        }
    }
}