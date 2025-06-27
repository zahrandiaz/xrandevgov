<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;

class CrawlerService
{
    private $httpClient;

    public function __construct()
    {
        $this->httpClient = HttpClient::create(['verify_peer' => false, 'verify_host' => false]);
    }

    /**
     * [BARU v1.18] Mengambil konten HTML mentah dari URL sebagai objek Crawler.
     * Diperlukan untuk analisis heuristik.
     *
     * @param string $baseUrl
     * @param string $crawlUrlPath
     * @return Crawler
     * @throws \Exception
     */
    public function fetchHtmlAsCrawler(string $baseUrl, string $crawlUrlPath): Crawler
    {
        $fullCrawlUrl = rtrim($baseUrl, '/') . '/' . ltrim($crawlUrlPath, '/');
        $client = new HttpBrowser($this->httpClient);

        Log::info("CrawlerService: Mengambil konten mentah dari URL: {$fullCrawlUrl}");

        try {
            $crawler = $client->request('GET', $fullCrawlUrl);
            return $crawler;
        } catch (\Symfony\Component\HttpClient\Exception\TransportException $e) {
            Log::error("CrawlerService: Gagal terhubung ke {$fullCrawlUrl}. Error: " . $e->getMessage());
            throw new \Exception("Gagal terhubung ke URL. Pastikan URL benar dan situs aktif. Detail: " . $e->getMessage());
        } catch (\Exception $e) {
            Log::error("CrawlerService: Terjadi kesalahan saat memproses {$fullCrawlUrl}. Error: " . $e->getMessage());
            throw $e; // Lemparkan kembali exception asli
        }
    }

    public function parseArticles(string $baseUrl, string $crawlUrlPath, string $titleSelector, ?string $dateSelector, ?string $linkSelector): array
    {
        $fullCrawlUrl = rtrim($baseUrl, '/') . '/' . ltrim($crawlUrlPath, '/');
        $client = new HttpBrowser($this->httpClient);
        $foundArticles = [];

        Log::info("CrawlerService: Memulai crawling ke URL: {$fullCrawlUrl}");

        try {
            $crawler = $client->request('GET', $fullCrawlUrl);

            if ($crawler->filter($titleSelector)->count() === 0) {
                 throw new \Exception("Selector judul ('{$titleSelector}') tidak menemukan elemen apapun di halaman.");
            }

            $crawler->filter($titleSelector)->each(function (Crawler $node) use (&$foundArticles, $baseUrl, $dateSelector, $linkSelector) {
                if (count($foundArticles) >= 20) return;

                $title = trim($node->text());
                $link = null;
                $date = null;

                // [FIX FINAL] Logika ekstraksi link yang super aman
                $linkNode = $node->filter('a')->count() > 0 ? $node->filter('a')->first() : $node;
                if ($linkNode && $linkNode->count() > 0 && $linkNode->getNode(0) && $linkNode->getNode(0)->hasAttribute('href')) {
                    $link = $linkNode->attr('href');
                } else {
                    $parentLinkNode = $node->closest('a');
                    // Periksa apakah $parentLinkNode ada sebelum memanggil metode padanya
                    if ($parentLinkNode && $parentLinkNode->count() > 0 && $parentLinkNode->getNode(0) && $parentLinkNode->getNode(0)->hasAttribute('href')) {
                         $link = $parentLinkNode->attr('href');
                    }
                }
                
                // [FIX FINAL] Logika ekstraksi tanggal yang super aman
                if (!empty($dateSelector)) {
                    $dateContainer = $node->closest('article, .post, .item, li, div');
                    if ($dateContainer && $dateContainer->count() > 0) {
                        $dateNodes = $dateContainer->filter($dateSelector);
                        if ($dateNodes->count() > 0) {
                            $firstDateNode = $dateNodes->first();
                            if ($firstDateNode && $firstDateNode->getNode(0)) {
                                $dateText = trim($firstDateNode->text());
                                $parsedTimestamp = strtotime($dateText);
                                if ($parsedTimestamp !== false) {
                                    $date = date('Y-m-d H:i:s', $parsedTimestamp);
                                } elseif ($firstDateNode->getNode(0)->hasAttribute('datetime')) {
                                    $date = date('Y-m-d H:i:s', strtotime($firstDateNode->attr('datetime')));
                                }
                            }
                        }
                    }
                }

                if ($link) {
                    if (!preg_match('~^https?://~i', $link)) {
                        $link = rtrim($baseUrl, '/') . '/' . ltrim($link, '/');
                    }
                    $parsedSourceHost = parse_url($baseUrl, PHP_URL_HOST);
                    $parsedArticleHost = parse_url($link, PHP_URL_HOST);

                    if (!empty($title) && filter_var($link, FILTER_VALIDATE_URL) && $parsedArticleHost && str_contains($parsedArticleHost, $parsedSourceHost)) {
                        $foundArticles[] = [
                            'title' => $title,
                            'link' => $link,
                            'date' => $date,
                        ];
                    }
                }
            });

            if (empty($foundArticles)) {
                throw new \Exception("Proses crawling selesai tetapi tidak ada artikel valid yang berhasil diparsing.");
            }

            Log::info("CrawlerService: Berhasil menemukan " . count($foundArticles) . " artikel dari {$fullCrawlUrl}");
            return $foundArticles;

        } catch (\Symfony\Component\HttpClient\Exception\TransportException $e) {
            Log::error("CrawlerService: Gagal terhubung ke {$fullCrawlUrl}. Error: " . $e->getMessage());
            throw new \Exception("Gagal terhubung ke URL. Pastikan URL benar dan situs aktif. Detail: " . $e->getMessage());
        } catch (\Exception $e) {
            Log::error("CrawlerService: Terjadi kesalahan saat memproses {$fullCrawlUrl}. Error: " . $e->getMessage());
            throw new \Exception("Terjadi kesalahan saat crawling: " . $e->getMessage());
        }
    }
}