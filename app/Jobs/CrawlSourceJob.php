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
use App\Services\CrawlerService;
use Throwable;

class CrawlSourceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $source;

    public function __construct(MonitoringSource $source)
    {
        $this->source = $source;
    }

    public function handle(CrawlerService $crawlerService): void
    {
        $this->source->update(['last_crawled_at' => now()]);
        
        try {
            // [MODIFIKASI v1.29.0] Periksa mode "Tanpa Tanggal"
            // Jika mode ini aktif, kita paksa dateSelector menjadi null agar tidak dicari.
            $dateSelector = $this->source->expects_date ? $this->source->selector_date : null;

            $articles = $crawlerService->parseArticles(
                $this->source->url,
                $this->source->crawl_url,
                $this->source->selector_title,
                $dateSelector, // Gunakan variabel yang sudah disiapkan
                $this->source->selector_link
            );

            $articlesFoundCount = 0;
            foreach ($articles as $articleData) {
                CrawledArticle::updateOrCreate(
                    ['url' => $articleData['link']],
                    [
                        'monitoring_source_id' => $this->source->id,
                        'title' => $articleData['title'],
                        'published_date' => $articleData['date'],
                        'crawled_at' => now(),
                    ]
                );
                $articlesFoundCount++;
            }
            
            if ($articlesFoundCount > 0) {
                 $this->source->update([
                    'last_crawl_status' => 'success',
                    'consecutive_failures' => 0,
                ]);
                Log::info("CrawlSourceJob: Sukses. {$articlesFoundCount} artikel diproses untuk source ID {$this->source->id}.");
            } else {
                throw new \Exception("Meskipun crawling berhasil, tidak ada artikel valid yang dapat disimpan.");
            }

        } catch (\Exception $e) {
            Log::error("CrawlSourceJob: Melempar kegagalan untuk source ID {$this->source->id}. Pesan: " . $e->getMessage());
            $this->fail($e);
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error("Job crawling GAGAL dan DITANDAI untuk source ID {$this->source->id}: " . $exception->getMessage());
        
        $this->source->update([
            'last_crawl_status' => 'failed',
            'consecutive_failures' => DB::raw('consecutive_failures + 1'),
        ]);
    }
}