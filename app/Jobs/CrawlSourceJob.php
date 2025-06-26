<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\MonitoringSource;
use App\Models\CrawledArticle;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\CrawlerService; // [BARU] Impor service kita
use Throwable;

class CrawlSourceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $source;

    public function __construct(MonitoringSource $source)
    {
        $this->source = $source;
    }

    /**
     * [REFAKTOR] Mengeksekusi job crawling menggunakan CrawlerService.
     *
     * @param \App\Services\CrawlerService $crawlerService
     * @return void
     */
    public function handle(CrawlerService $crawlerService): void
    {
        $this->source->update(['last_crawled_at' => now()]);
        
        try {
            // [REFAKTOR] Panggil service untuk melakukan semua pekerjaan berat.
            $articles = $crawlerService->parseArticles(
                $this->source->url,
                $this->source->crawl_url,
                $this->source->selector_title,
                $this->source->selector_date,
                $this->source->selector_link
            );

            // Jika service berhasil, proses dan simpan artikel ke database.
            $articlesFoundCount = 0;
            foreach ($articles as $articleData) {
                CrawledArticle::updateOrCreate(
                    ['url' => $articleData['link']], // Kunci unik untuk mencegah duplikasi
                    [
                        'monitoring_source_id' => $this->source->id,
                        'title' => $articleData['title'],
                        'published_date' => $articleData['date'], // Bisa jadi null
                        'crawled_at' => now(),
                    ]
                );
                $articlesFoundCount++;
            }
            
            // Catat sebagai sukses jika setidaknya satu artikel valid ditemukan dan disimpan.
            if ($articlesFoundCount > 0) {
                 $this->source->update([
                    'last_crawl_status' => 'success',
                    'consecutive_failures' => 0,
                ]);
                Log::info("CrawlSourceJob: Sukses. {$articlesFoundCount} artikel diproses untuk source ID {$this->source->id}.");
            } else {
                // Ini terjadi jika service berjalan tapi tidak ada artikel valid yang bisa diparsing
                throw new \Exception("Meskipun crawling berhasil, tidak ada artikel valid yang dapat disimpan.");
            }

        } catch (\Exception $e) {
            // Jika CrawlerService melempar exception (koneksi, selector salah, dll.),
            // job ini akan gagal dan logikanya akan ditangani oleh metode failed().
            Log::error("CrawlSourceJob: Melempar kegagalan untuk source ID {$this->source->id}. Pesan: " . $e->getMessage());
            $this->fail($e);
        }
    }

    /**
     * Menangani job yang gagal.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(Throwable $exception): void
    {
        // Logika untuk mencatat kegagalan, tidak ada perubahan di sini.
        Log::error("Job crawling GAGAL dan DITANDAI untuk source ID {$this->source->id}: " . $exception->getMessage());
        
        $this->source->update([
            'last_crawl_status' => 'failed',
            'consecutive_failures' => DB::raw('consecutive_failures + 1'),
        ]);
    }
}