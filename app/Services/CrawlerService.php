<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;
use Carbon\Carbon;

class CrawlerService
{
    private $httpClient;

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
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
                        $date = $this->extractDateFromCandidateNode($dateNode);
                    }
                }
            }
            
            if (!$date) {
                $date = $this->parseDateFromText($title);
            }

            $linkNode = $titleNode->closest('a') ?? $titleNode;
            $link = $linkNode->count() > 0 ? $linkNode->attr('href') : null;

            if ($link) {
                if (!preg_match('~^https?://~i', $link)) $link = rtrim($baseUrl, '/') . '/' . ltrim($link, '/');
                if (filter_var($link, FILTER_VALIDATE_URL)) {
                    if(!empty($title) && !empty($link)) {
                       $foundArticles[] = ['title' => $title, 'link' => $link, 'date' => $date];
                    }
                }
            }
        });

        if (empty($foundArticles)) throw new \Exception("Tidak ada artikel valid yang berhasil diparsing.");
        return $foundArticles;
    }
    
    private function extractDateFromCandidateNode(Crawler $node): ?string
    {
        if ($node->attr('datetime')) {
            $parsedDate = $this->parseDateFromText($node->attr('datetime'));
            if ($parsedDate) return $parsedDate;
        }

        $nodeText = $node->text();
        if (!empty(trim($nodeText))) {
            $parsedDate = $this->parseDateFromText($nodeText);
            if ($parsedDate) return $parsedDate;
        }

        $childText = $node->filter('*')->each(function(Crawler $child) { return $child->text(); });
        if(!empty($childText)) {
            $parsedDate = $this->parseDateFromText(implode(' ', $childText));
            if ($parsedDate) return $parsedDate;
        }

        return null;
    }

    /**
     * [REWORK v1.24] Mesin parsing tanggal diperkuat untuk mengenali format "ago".
     */
    private function parseDateFromText(?string $text): ?string
    {
        if (empty(trim($text))) {
            return null;
        }

        // [MODIFIKASI v1.24] Regex utama untuk *mengekstrak* tanggal, kini mengenali 'ago'.
        $dateExtractionPattern = '/'.
            '(\d{1,2}\s+(?:Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember|Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s+\d{4})|'.
            '(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{4})|'.
            '(kemarin|hari\s*ini|\d+\s*(?:jam|menit|detik|second|minute|hour)s?\s*(?:yang\s*lalu|ago))'.
        '/i';

        $dateStringToParse = $text;

        if (preg_match($dateExtractionPattern, $text, $matches)) {
            $dateStringToParse = $matches[0];
        }

        $cleanedText = str_ireplace(
            ['|', 'Dipublikasikan pada', 'Published:', 'Posted on', 'tanggal:', ':', ' WIB', 'WITA', 'WIT'],
            '',
            $dateStringToParse
        );
        $cleanedText = trim($cleanedText);

        $translations = [
            'januari' => 'january', 'februari' => 'february', 'maret' => 'march', 'april' => 'april',
            'mei' => 'may', 'juni' => 'june', 'juli' => 'july', 'agustus' => 'august',
            'september' => 'september', 'oktober' => 'october', 'november' => 'november', 'desember' => 'december',
            'jan' => 'jan', 'feb' => 'feb', 'mar' => 'mar', 'apr' => 'apr', 'mei' => 'may', 'jun' => 'jun',
            'jul' => 'jul', 'agu' => 'aug', 'sep' => 'sep', 'okt' => 'oct', 'nov' => 'nov', 'des' => 'dec',
            'hari ini' => 'today', 'kemarin' => 'yesterday',
            'jam' => 'hours', 'menit' => 'minutes', 'detik' => 'seconds',
            'yang lalu' => 'ago', 'second' => 'second', 'minute' => 'minute', 'hour' => 'hour',
        ];
        
        $englishText = str_ireplace(array_keys($translations), array_values($translations), strtolower($cleanedText));

        try {
            return Carbon::parse($englishText)->toDateTimeString();
        } catch (\Exception $e) {
            Log::warning("Gagal mem-parsing tanggal dari teks: '{$text}'", ['original' => $text, 'extracted' => $dateStringToParse, 'english' => $englishText]);
            return null;
        }
    }
}