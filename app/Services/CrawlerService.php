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

    public function fetchHtmlAsCrawler(string $baseUrl, string $crawlUrlPath): Crawler
    {
        $fullCrawlUrl = rtrim($baseUrl, '/') . '/' . ltrim($crawlUrlPath, '/');
        $client = new HttpBrowser($this->httpClient);
        try {
            return $client->request('GET', $fullCrawlUrl);
        } catch (\Exception $e) {
            throw new \Exception("Gagal terhubung ke URL. Detail: " . $e->getMessage());
        }
    }

    /**
     * [REWORK TOTAL v1.19.8] Logika parsing dengan pendekatan pragmatis dan anti-gagal.
     */
    public function parseArticles(string $baseUrl, string $crawlUrlPath, string $titleSelector, ?string $dateSelector, ?string $linkSelector, int $limit = 20): array
    {
        $client = new HttpBrowser($this->httpClient);
        $fullCrawlUrl = rtrim($baseUrl, '/') . '/' . ltrim($crawlUrlPath, '/');
        $crawler = $client->request('GET', $fullCrawlUrl);
        $foundArticles = [];

        if ($crawler->filter($titleSelector)->count() === 0) {
            throw new \Exception("Selector judul ('{$titleSelector}') tidak menemukan elemen.");
        }

        $crawler->filter($titleSelector)->each(function (Crawler $titleNode) use (&$foundArticles, $dateSelector, $baseUrl, $limit) {
            if (count($foundArticles) >= $limit) return;

            $title = trim($titleNode->text());
            $date = null;

            if ($dateSelector) {
                $container = $titleNode->closest('article, .post, .item, li, div, tr');
                if ($container && $container->count() > 0) {
                    $dateNode = $container->filter($dateSelector)->first();
                    if ($dateNode->count() > 0) {
                        // [LOGIKA BARU] Coba semua kemungkinan dari node tanggal yang ditemukan.
                        $date = $this->extractDateFromCandidateNode($dateNode);
                    }
                }
            }
            
            // Fallback
            if (!$date) {
                $date = $this->parseDateFromText($title);
            }

            $linkNode = $titleNode->closest('a') ?? $titleNode;
            $link = $linkNode->count() > 0 ? $linkNode->attr('href') : null;

            if ($link) {
                if (!preg_match('~^https?://~i', $link)) $link = rtrim($baseUrl, '/') . '/' . ltrim($link, '/');
                if (filter_var($link, FILTER_VALIDATE_URL)) {
                    $foundArticles[] = ['title' => $title, 'link' => $link, 'date' => $date];
                }
            }
        });

        if (empty($foundArticles)) throw new \Exception("Tidak ada artikel valid yang berhasil diparsing.");
        return $foundArticles;
    }

    /**
     * [BARU v1.19.8] Metode Cerdas untuk mengekstrak tanggal dari satu node kandidat.
     * Ia akan mencoba semua cara: atribut, teks, bahkan teks dari child node.
     */
    private function extractDateFromCandidateNode(Crawler $node): ?string
    {
        // Prioritas 1: Atribut 'datetime' adalah yang paling bisa diandalkan.
        if ($node->attr('datetime')) {
            $timestamp = strtotime($node->attr('datetime'));
            if ($timestamp) return date('Y-m-d H:i:s', $timestamp);
        }

        // Prioritas 2: Teks dari node itu sendiri.
        $nodeText = $node->text();
        if (!empty(trim($nodeText))) {
            $parsedDate = $this->parseDateFromText($nodeText);
            if ($parsedDate) return $parsedDate;
        }

        // Prioritas 3 (Jaring Pengaman): Jika node itu sendiri tidak punya teks (misal: <div><span>Tanggal</span></div>)
        // Coba cari teks dari semua anak-anaknya.
        $childText = $node->filter('*')->each(function(Crawler $child) { return $child->text(); });
        if(!empty($childText)) {
            $parsedDate = $this->parseDateFromText(implode(' ', $childText));
            if ($parsedDate) return $parsedDate;
        }

        return null;
    }

    private function parseDateFromText(?string $text): ?string
    {
        // Kode ini tidak berubah, sudah cukup andal dari versi sebelumnya.
        if (empty(trim($text))) return null;
        $months = [
            'januari' => '01', 'jan' => '01', 'january' => '01', 'februari' => '02', 'feb' => '02', 'february' => '02',
            'maret' => '03', 'mar' => '03', 'march' => '03', 'april' => '04', 'apr' => '04', 'mei' => '05', 'may' => '05',
            'juni' => '06', 'jun' => '06', 'june' => '06', 'juli' => '07', 'jul' => '07', 'july' => '07',
            'agustus' => '08', 'agu' => '08', 'august' => '08', 'september' => '09', 'sep' => '09',
            'oktober' => '10', 'okt' => '10', 'october' => '10', 'november' => '11', 'nov' => '11',
            'desember' => '12', 'des' => '12', 'december' => '12'
        ];
        $text = strtolower(trim($text));
        $patternNamaBulan = '/(\d{1,2})\s+(' . implode('|', array_keys($months)) . ')\s+(\d{4})/i';
        if (preg_match($patternNamaBulan, $text, $matches)) {
            $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $month = $months[strtolower($matches[2])];
            $year = $matches[3];
            return date('Y-m-d H:i:s', strtotime("$year-$month-$day"));
        }
        $patternNumerik = '/(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{2,4})/';
        if (preg_match($patternNumerik, $text, $matches)) {
            $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $year = strlen($matches[3]) === 2 ? "20" . $matches[3] : $matches[3];
            return date('Y-m-d H:i:s', strtotime("$year-$month-$day"));
        }
        return null;
    }
}