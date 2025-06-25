<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

// Import semua class yang kita butuhkan untuk crawling
use App\Models\MonitoringSource;
use App\Models\CrawledArticle;
use Illuminate\Support\Facades\Log;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;
use Carbon\Carbon;

class CrawlSourceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Properti untuk menyimpan objek MonitoringSource yang akan di-crawl.
     * Properti ini akan secara otomatis disimpan dan diambil dari queue.
     * @var \App\Models\MonitoringSource
     */
    public $source;

    /**
     * Create a new job instance.
     *
     * @param \App\Models\MonitoringSource $source
     * @return void
     */
    public function __construct(MonitoringSource $source)
    {
        $this->source = $source;
    }

    /**
     * Execute the job.
     * Logika utama crawling akan kita letakkan di sini.
     *
     * @return void
     */
    public function handle(): void
    {
        // Inisialisasi HTTP Client
        $httpClient = HttpClient::create(['verify_peer' => false, 'verify_host' => false]);
        $client = new HttpBrowser($httpClient);

        // Ambil data source dari properti yang sudah disimpan
        $source = $this->source;

        try {
            // Update last_crawled_at sebelum crawling untuk menandai proses dimulai
            $source->update(['last_crawled_at' => now()]);

            $fullCrawlUrl = $source->crawl_url;
            if (strpos($fullCrawlUrl, 'http') !== 0) {
                $fullCrawlUrl = rtrim($source->url, '/') . '/' . ltrim($fullCrawlUrl, '/');
            }

            $crawler = $client->request('GET', $fullCrawlUrl);

            $titleSelector = $source->selector_title ?: 'h1 a, h2 a, h3 a, .post-title a, .entry-title a';
            $dateSelector = $source->selector_date ?: 'time, .date, .post-date, .entry-date';
            $linkSelector = $source->selector_link ?: 'a';

            $crawler->filter($titleSelector)->each(function ($node) use ($source, $dateSelector, $linkSelector) {
                try {
                    $title = trim($node->text());
                    $link = null;
                    $date = null;

                    // Logika pengambilan link
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
                        // Logika fallback jika link tidak di selector utama
                        $readMoreNode = $node->closest('div, p, article, li')->filter('a[href*="berita"], a[href*="artikel"], a[href*="read"], a.btn-primary')->first();
                        if ($readMoreNode->count() > 0) {
                            $readMoreDomElement = $readMoreNode->getNode(0);
                            if ($readMoreDomElement instanceof \DOMElement && $readMoreDomElement->hasAttribute('href')) {
                                $link = $readMoreDomElement->getAttribute('href');
                            }
                        }
                    }

                    // Pastikan link absolut
                    if ($link && strpos($link, 'http') !== 0) {
                        $link = rtrim($source->url, '/') . '/' . ltrim($link, '/');
                    }

                    // Logika pengambilan tanggal
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
                    
                    // Verifikasi dan simpan artikel
                    $parsedSourceUrl = parse_url($source->url, PHP_URL_HOST);
                    $parsedArticleLinkHost = $link ? parse_url($link, PHP_URL_HOST) : null;
                    if (filter_var($link, FILTER_VALIDATE_URL) && $title && str_contains($parsedArticleLinkHost, $parsedSourceUrl)) {
                         CrawledArticle::updateOrCreate(
                            ['url' => $link],
                            [
                                'monitoring_source_id' => $source->id,
                                'title' => $title,
                                'published_date' => $date,
                                'crawled_at' => now(),
                            ]
                        );
                    }

                } catch (\Exception $e) {
                    Log::warning('Gagal memproses satu artikel dari ' . $source->url . ' di dalam job: ' . $e->getMessage());
                }
            });

        } catch (\Exception $e) {
            Log::error("Job crawling gagal untuk source ID {$source->id} ({$source->url}): " . $e->getMessage());
            // Jika job gagal, Laravel akan otomatis mencoba lagi sesuai konfigurasi queue Anda.
            // Kita bisa melempar exception lagi agar job ditandai sebagai 'failed'.
            $this->fail($e);
        }
    }
}