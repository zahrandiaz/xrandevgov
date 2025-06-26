<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MonitoringSource; // Import model kita
use Illuminate\Support\Facades\DB;   // Import DB untuk operasi yang lebih efisien

class ResetCrawlFailuresCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitoring:reset-failures';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mereset status kegagalan (consecutive_failures) semua situs monitoring menjadi 0';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai proses reset status kegagalan crawl...');

        // Minta konfirmasi dari pengguna untuk keamanan
        if ($this->confirm('Apakah Anda yakin ingin mereset status kegagalan untuk SEMUA situs monitoring?')) {
            
            // Lakukan update massal menggunakan Query Builder untuk efisiensi
            $updatedCount = DB::table('monitoring_sources')->update([
                'consecutive_failures' => 0,
                'last_crawl_status' => null
            ]);

            $this->info("Proses selesai. Status kegagalan untuk {$updatedCount} situs telah direset.");

        } else {
            $this->comment('Proses reset dibatalkan.');
        }

        return 0;
    }
}