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
use Illuminate\Support\Facades\DB; // Pastikan DB di-import
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;
use Carbon\Carbon;
use Throwable; // Import Throwable

class CrawlSourceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $source;

    public function __construct(MonitoringSource $source)
    {
        $this->source = $source;
    }

    public function handle(): void
    {
        $this->source->update(['last_crawled_at' => now()]);
        
        try {
            $httpClient = HttpClient::create(['verify_peer' => false, 'verify_host' => false]);
            $client = new HttpBrowser($httpClient);
            
            $fullCrawlUrl = $this->source->crawl_url;
            if (strpos($fullCrawlUrl, 'http') !== 0) {
                $fullCrawlUrl = rtrim($this->source->url, '/') . '/' . ltrim($fullCrawlUrl, '/');
            }

            $crawler = $client->request('GET', $fullCrawlUrl);
            $titleSelector = $this->source->selector_title ?: 'h1 a, h2 a, h3 a, .post-title a, .entry-title a';
            $dateSelector = $this->source->selector_date ?: 'time, .date, .post-date, .entry-date';
            $linkSelector = $this->source->selector_link ?: 'a';
            $articlesFound = 0;

            $crawler->filter($titleSelector)->each(function ($node) use ($dateSelector, $linkSelector, &$articlesFound) {
                // ... (Logika parsing yang panjang di sini tetap sama) ...
                try {
                    $title = trim($node->text());
                    $link = null;
                    $date = null;
                    if (!empty($linkSelector) && $node->filter($linkSelector)->count() > 0) {
                        $firstLinkNodeCrawler = $node->filter($linkSelector)->first();
                        $domElement = $firstLinkNodeCrawler->getNode(0);
                        if ($domElement instanceof \DOMElement && $domElement->hasAttribute('href')) {
                            $link = $domElement->getAttribute('href');
                        } else {
                            $linkObject = $firstLinkNodeCrawler->link();
                            if ($linkObject) $link = $linkObject->getUri();
                        }
                    } elseif ($node->getNode(0) instanceof \DOMElement && $node->getNode(0)->nodeName === 'a' && $node->getNode(0)->hasAttribute('href')) {
                        $link = $node->getNode(0)->getAttribute('href');
                    } else {
                        $readMoreNode = $node->closest('div, p, article, li')->filter('a[href*="berita"], a[href*="artikel"], a[href*="read"], a.btn-primary')->first();
                        if ($readMoreNode->count() > 0) {
                            $readMoreDomElement = $readMoreNode->getNode(0);
                            if ($readMoreDomElement instanceof \DOMElement && $readMoreDomElement->hasAttribute('href')) {
                                $link = $readMoreDomElement->getAttribute('href');
                            }
                        }
                    }
                    if ($link && strpos($link, 'http') !== 0) {
                        $link = rtrim($this->source->url, '/') . '/' . ltrim($link, '/');
                    }
                    $dateCandidateNode = null;
                    if (!empty($dateSelector) && $node->filter($dateSelector)->count() > 0) {
                        $dateCandidateNode = $node->filter($dateSelector)->first();
                    } else {
                        $dateCandidateNode = $node->closest('li, div, article, p, span')->filter($dateSelector)->first();
                    }
                    if ($dateCandidateNode && $dateCandidateNode->count() > 0) {
                        $dateDomElement = $dateCandidateNode->getNode(0);
                        if ($dateDomElement instanceof \DOMElement) {
                            $dateText = trim($dateDomElement->textContent);
                            $parsedDate = strtotime($dateText);
                            if ($parsedDate !== false) {
                                $date = date('Y-m-d H:i:s', $parsedDate);
                            } elseif ($dateDomElement->hasAttribute('datetime')) {
                                $date = date('Y-m-d H:i:s', strtotime($dateDomElement->getAttribute('datetime')));
                            }
                        }
                    }
                    $parsedSourceUrl = parse_url($this->source->url, PHP_URL_HOST);
                    $parsedArticleLinkHost = $link ? parse_url($link, PHP_URL_HOST) : null;
                    if (filter_var($link, FILTER_VALIDATE_URL) && $title && str_contains($parsedArticleLinkHost, $parsedSourceUrl)) {
                         CrawledArticle::updateOrCreate(
                            ['url' => $link],
                            [
                                'monitoring_source_id' => $this->source->id,
                                'title' => $title,
                                'published_date' => $date,
                                'crawled_at' => now(),
                            ]
                        );
                        $articlesFound++;
                    }
                } catch (\Exception $e) {
                    Log::warning('Gagal memproses satu artikel dari ' . $this->source->url . ' di dalam job: ' . $e->getMessage());
                }
            });

            if ($articlesFound === 0) {
                throw new \Exception("Crawl process finished but found 0 articles. Selector is likely broken or page has no news.");
            }

            $this->source->update([
                'last_crawl_status' => 'success',
                'consecutive_failures' => 0,
            ]);

        } catch (\Exception $e) {
            // Jika terjadi error, kita hanya perlu melemparnya kembali agar job ditandai gagal
            // Logika pencatatan kegagalan akan ditangani oleh metode failed()
            $this->fail($e);
        }
    }

    /**
     * [BARU] Menangani job yang gagal.
     * Metode ini akan dipanggil oleh queue worker saat job gagal.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(Throwable $exception): void
    {
        // Logika untuk mencatat kegagalan kita pindahkan ke sini
        Log::error("Job crawling GAGAL dan DITANDAI untuk source ID {$this->source->id}: " . $exception->getMessage());
        
        $this->source->update([
            'last_crawl_status' => 'failed',
            'consecutive_failures' => DB::raw('consecutive_failures + 1'),
        ]);
    }
}