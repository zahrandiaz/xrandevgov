<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;
use Carbon\Carbon; // <-- [v1.20] Tambahkan use statement untuk Carbon

class CrawlerService
{
    private $httpClient;

    public function __construct()
    {
        // Set default timezone ke Asia/Jakarta agar parsing tanggal relatif akurat
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

    /**
     * [MODIFIKASI v1.20] Logika parsing tidak diubah di sini, fokus ada di `parseDateFromText`.
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
                // Mencari container terdekat yang paling logis untuk sebuah item artikel
                $container = $titleNode->closest('article, .post, .item, li, div, tr');
                if ($container && $container->count() > 0) {
                    $dateNode = $container->filter($dateSelector)->first();
                    if ($dateNode->count() > 0) {
                        $date = $this->extractDateFromCandidateNode($dateNode);
                    }
                }
            }
            
            // Fallback jika selector tanggal tidak disediakan atau gagal
            if (!$date) {
                $date = $this->parseDateFromText($title);
            }

            $linkNode = $titleNode->closest('a') ?? $titleNode;
            $link = $linkNode->count() > 0 ? $linkNode->attr('href') : null;

            if ($link) {
                if (!preg_match('~^https?://~i', $link)) $link = rtrim($baseUrl, '/') . '/' . ltrim($link, '/');
                if (filter_var($link, FILTER_VALIDATE_URL)) {
                    // Hanya tambahkan jika judul dan link tidak kosong
                    if(!empty($title) && !empty($link)) {
                       $foundArticles[] = ['title' => $title, 'link' => $link, 'date' => $date];
                    }
                }
            }
        });

        if (empty($foundArticles)) throw new \Exception("Tidak ada artikel valid yang berhasil diparsing.");
        return $foundArticles;
    }
    
    /**
     * Metode ini tidak diubah. Sudah cukup andal.
     */
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
     * [REWORK TOTAL v1.20] Mesin parsing tanggal yang jauh lebih andal menggunakan Carbon.
     */
    private function parseDateFromText(?string $text): ?string
    {
        if (empty(trim($text))) {
            return null;
        }

        // 1. Bersihkan string dari noise yang umum
        $cleanedText = str_ireplace(
            ['|', 'Dipublikasikan pada', 'Published:', 'Posted on', 'tanggal:', ':', ' WIB', 'WITA', 'WIT'],
            '',
            $text
        );
        $cleanedText = trim($cleanedText);

        // 2. Daftar prioritas pola regex dan format
        $formats = [
            // Format umum: Senin, 28 Juni 2025 15:30
            '/\b(senin|selasa|rabu|kamis|jumat|sabtu|minggu),\s*(\d{1,2})\s*([a-z]+)\s*(\d{4})\s*(\d{2}:\d{2})\b/i' => function($m) { return "{$m[2]} {$m[3]} {$m[4]} {$m[5]}"; },
            // Format umum: 28 Juni 2025 15:30
            '/(\d{1,2})\s+([a-z]+)\s+(\d{4})\s+(\d{2}:\d{2})/i' => function($m) { return "{$m[1]} {$m[2]} {$m[3]} {$m[4]}"; },
            // Format umum: 28 Juni 2025
            '/(\d{1,2})\s+([a-z]+)\s+(\d{4})/i' => function($m) { return "{$m[1]} {$m[2]} {$m[3]}"; },
            // Format dd/mm/yyyy atau dd-mm-yyyy
            '/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/i' => function($m) { return "{$m[1]}-{$m[2]}-{$m[3]}"; },
            // Format relatif: Kemarin, Hari ini, 2 jam yang lalu
            '/(kemarin|hari\s*ini|\d+\s*(jam|menit|detik)\s*yang\s*lalu)/i' => function($m) { return $m[1]; },
        ];
        
        // 3. Terjemahan bulan dan kata kunci
        $translations = [
            'januari' => 'january', 'februari' => 'february', 'maret' => 'march', 'april' => 'april',
            'mei' => 'may', 'juni' => 'june', 'juli' => 'july', 'agustus' => 'august',
            'september' => 'september', 'oktober' => 'october', 'november' => 'november', 'desember' => 'december',
            'jan' => 'jan', 'feb' => 'feb', 'mar' => 'mar', 'apr' => 'apr', 'mei' => 'may', 'jun' => 'jun',
            'jul' => 'jul', 'agu' => 'aug', 'sep' => 'sep', 'okt' => 'oct', 'nov' => 'nov', 'des' => 'dec',
            'hari ini' => 'today', 'kemarin' => 'yesterday', 'jam' => 'hours', 'menit' => 'minutes', 'detik' => 'seconds',
            'yang lalu' => 'ago'
        ];
        
        // Terjemahkan string ke Bahasa Inggris agar dapat diparsing Carbon
        $englishText = str_ireplace(array_keys($translations), array_values($translations), strtolower($cleanedText));

        // 4. Coba parsing dengan setiap pola
        foreach ($formats as $pattern => $handler) {
            if (preg_match($pattern, $englishText, $matches)) {
                try {
                    $dateString = $handler($matches);
                    return Carbon::parse(trim($dateString))->toDateTimeString();
                } catch (\Exception $e) {
                    continue; // Jika format gagal, coba pola berikutnya
                }
            }
        }

        // 5. Upaya terakhir jika tidak ada pola yang cocok, biarkan Carbon mencoba menebak
        try {
            // Kita coba parsing langsung string yang sudah diterjemahkan
            return Carbon::parse($englishText)->toDateTimeString();
        } catch (\Exception $e) {
            // Jika semua gagal, kembalikan null
            Log::warning("Gagal mem-parsing tanggal dari teks: '{$text}'", ['original' => $text, 'cleaned' => $cleanedText, 'english' => $englishText]);
            return null;
        }
    }
}