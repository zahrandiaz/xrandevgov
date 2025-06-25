<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MonitoringSource; // Import model MonitoringSource
use App\Jobs\CrawlSourceJob;     // Import Job yang sudah kita buat

class DispatchCrawlingJobsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // Ini adalah nama command yang akan kita panggil
    protected $signature = 'monitoring:dispatch-jobs';

    /**
     * The console command description.
     *
     * @var string
     */
    // Deskripsi command
    protected $description = 'Memicu (dispatch) job crawling untuk semua situs monitoring yang aktif';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Mencari situs monitoring yang aktif...');

        // 1. Ambil semua situs yang aktif
        $sources = MonitoringSource::where('is_active', true)->get();

        // 2. Jika tidak ada, hentikan dan beri pesan
        if ($sources->isEmpty()) {
            $this->comment('Tidak ada situs monitoring yang aktif. Tidak ada job yang dikirim.');
            return 0;
        }

        $this->info("Ditemukan {$sources->count()} situs aktif. Mengirim job ke queue...");

        // 3. Loop setiap situs dan kirim tugasnya ke queue
        foreach ($sources as $source) {
            CrawlSourceJob::dispatch($source);
        }

        $this->info('Berhasil! Semua job crawling telah dikirim ke queue.');
        return 0;
    }
}