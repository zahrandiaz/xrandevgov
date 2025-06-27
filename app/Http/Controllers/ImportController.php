<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
     */
    public function showSourcesForm()
    {
        return view('import.sources');
    }

    /**
     * Menangani logika pemrosesan file CSV yang diunggah.
     */
    public function handleSourcesImport(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('csv_file');
        $skippedRows = []; // [BARU] Inisialisasi array untuk menyimpan detail error
        $processedCount = 0;

        try {
            $csv = Reader::createFromPath($file->getRealPath(), 'r');
            $csv->setHeaderOffset(0);

            $records = $csv->getRecords();
            $regions = Region::all()->keyBy('name');

            DB::beginTransaction();

            foreach ($records as $index => $record) {
                $rowNumber = $index + 2;

                $nama_situs = trim($record['nama_situs'] ?? '');
                $url_situs = trim($record['url_situs'] ?? '');
                $nama_wilayah = trim($record['nama_wilayah'] ?? '');
                $tipe_instansi = trim(strtoupper($record['tipe_instansi'] ?? ''));

                // [MODIFIKASI] Logika validasi dengan pencatatan error
                if (empty($nama_situs) || empty($url_situs) || empty($nama_wilayah) || empty($tipe_instansi)) {
                    $skippedRows[] = "Baris {$rowNumber}: Dilewati karena salah satu kolom wajib (nama_situs, url_situs, nama_wilayah, tipe_instansi) kosong.";
                    continue;
                }

                if (!in_array($tipe_instansi, ['BKD', 'BKPSDM'])) {
                    $skippedRows[] = "Baris {$rowNumber}: Dilewati karena tipe_instansi '{$tipe_instansi}' tidak valid. Harus 'BKD' atau 'BKPSDM'.";
                    continue;
                }

                if (!$regions->has($nama_wilayah)) {
                    $skippedRows[] = "Baris {$rowNumber}: Dilewati karena nama_wilayah '{$nama_wilayah}' tidak ditemukan di database.";
                    continue;
                }
                
                $region = $regions->get($nama_wilayah);

                if (($tipe_instansi === 'BKD' && $region->type !== 'Provinsi') || ($tipe_instansi === 'BKPSDM' && $region->type !== 'Kabupaten/Kota')) {
                    $skippedRows[] = "Baris {$rowNumber}: Dilewati karena tipe_instansi '{$tipe_instansi}' tidak sesuai dengan tipe wilayah '{$region->type}'.";
                    continue;
                }

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

            $message = "Impor CSV berhasil. {$processedCount} situs diproses, " . count($skippedRows) . " situs dilewati.";
            log_activity($message, 'success', 'import-csv');

            // [MODIFIKASI] Kirim detail baris yang dilewati ke view
            return redirect()->route('import.sources.show')
                ->with('success', $message)
                ->with('skipped_rows', $skippedRows);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal melakukan impor CSV: " . $e->getMessage());
            
            log_activity("Gagal melakukan impor CSV. Error: " . $e->getMessage(), 'error', 'import-csv');

            return redirect()->route('import.sources.show')
                ->with('error', 'Terjadi kesalahan fatal saat memproses file. Pastikan format header CSV sudah benar. Error: ' . $e->getMessage());
        }
    }
}