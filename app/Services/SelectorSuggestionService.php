<?php

namespace App\Services;

use App\Models\SuggestionSelector;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

/**
 * [v1.18] EVOLUSI: Mesin Saran Hibrida Otomatis.
 * Menggabungkan kecepatan kamus database dengan kekuatan analisis heuristik.
 */
class SelectorSuggestionService
{
    private $crawlerService;

    public function __construct(CrawlerService $crawlerService)
    {
        $this->crawlerService = $crawlerService;
    }

    public function suggest(string $baseUrl, ?string $crawlUrlPath): array
    {
        $dictionaryResult = $this->suggestViaDictionary($baseUrl, $crawlUrlPath);
        if ($dictionaryResult['success']) {
            Log::info("[Selector Suggestion] Sukses menemukan selector menggunakan metode Kamus untuk URL: " . $baseUrl);
            return $dictionaryResult;
        }

        Log::info("[Selector Suggestion] Metode kamus gagal, mencoba analisis Heuristik untuk URL: " . $baseUrl);
        $heuristicResult = $this->suggestViaHeuristics($baseUrl, $crawlUrlPath);

        return $heuristicResult;
    }

    private function suggestViaDictionary(string $baseUrl, ?string $crawlUrlPath): array
    {
        $successfulTitleSelectors = [];
        $titleSelectors = $this->getTitleSelectors();

        foreach ($titleSelectors as $selector) {
            try {
                $this->crawlerService->parseArticles($baseUrl, $crawlUrlPath ?? '/', $selector, null, null, 1);
                $successfulTitleSelectors[] = $selector;
            } catch (\Exception $e) {
                continue;
            }
        }

        if (empty($successfulTitleSelectors)) {
            return ['success' => false];
        }

        $bestTitleSelector = $successfulTitleSelectors[0];
        $successfulDateSelectors = $this->findMatchingDateSelector($baseUrl, $crawlUrlPath, $bestTitleSelector);

        return [
            'success' => true,
            'title_selectors' => $successfulTitleSelectors,
            'date_selectors' => $successfulDateSelectors,
            'method' => 'dictionary'
        ];
    }

    /**
     * [BARU v1.18] STRATEGI 2: Menganalisis konten HTML secara langsung (Heuristik - Level 1).
     * "Detektif Junior" yang mencari pola berulang pada link.
     */
    private function suggestViaHeuristics(string $baseUrl, ?string $crawlUrlPath): array
    {
        try {
            // 1. Ambil konten mentah halaman
            $crawler = $this->crawlerService->fetchHtmlAsCrawler($baseUrl, $crawlUrlPath ?? '/');

            $candidateLinks = [];

            // 2. Kumpulkan semua link sebagai kandidat
            $crawler->filter('a')->each(function (Crawler $node) use (&$candidateLinks) {
                $text = trim($node->text());
                $href = $node->attr('href');

                // 3. Terapkan Aturan Filter (Heuristik)
                if (
                    !empty($href) && !str_starts_with($href, '#') && // Bukan link anchor
                    mb_strlen($text) > 20 && mb_strlen($text) < 150 && // Aturan panjang judul
                    !preg_match('/(login|kontak|tentang|privacy|policy|selengkapnya|read more|next|prev)/i', $text) // Kata kunci negatif
                ) {
                    $candidateLinks[] = $node;
                }
            });

            if (count($candidateLinks) < 3) { // Butuh setidaknya 3 kandidat untuk menemukan pola
                throw new \Exception("Tidak cukup kandidat link yang valid untuk dianalisis.");
            }

            // 4. Identifikasi Pola Berulang
            $selectorPatterns = [];
            foreach ($candidateLinks as $linkNode) {
                $selectorPath = $this->generateSelectorPath($linkNode);
                if ($selectorPath) {
                    $selectorPatterns[] = $selectorPath;
                }
            }

            if (empty($selectorPatterns)) {
                throw new \Exception("Tidak dapat menghasilkan pola selector dari kandidat yang ada.");
            }

            // Hitung frekuensi setiap pola dan temukan yang paling umum
            $patternCounts = array_count_values($selectorPatterns);
            arsort($patternCounts);
            $bestPattern = key($patternCounts);

            // 5. Hasilkan Selector Unik dan Kembalikan Hasil
            $successfulTitleSelectors = [$bestPattern];
            $successfulDateSelectors = $this->findMatchingDateSelector($baseUrl, $crawlUrlPath, $bestPattern);

            return [
                'success' => true,
                'title_selectors' => $successfulTitleSelectors,
                'date_selectors' => $successfulDateSelectors,
                'method' => 'heuristic'
            ];

        } catch (\Exception $e) {
            Log::error("[Heuristic Suggestion] Gagal: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Analisis heuristik gagal: ' . $e->getMessage(),
                'method' => 'heuristic'
            ];
        }
    }

    /**
     * [BARU v1.18] Helper untuk menghasilkan path CSS selector unik untuk sebuah node.
     * Contoh: body > div.content > article.post > h2 > a
     */
    private function generateSelectorPath(Crawler $node): ?string
    {
        $path = [];
        $currentNode = $node;

        // Berjalan naik dari node saat ini hingga ke body
        while ($currentNode->count() > 0 && $currentNode->nodeName() !== 'body') {
            $nodeName = $currentNode->nodeName();
            $id = $currentNode->attr('id');
            $classNames = $currentNode->attr('class');

            $selectorPart = $nodeName;
            if ($id) {
                // Jika ada ID, itu sudah cukup unik, kita bisa berhenti.
                $selectorPart .= '#' . $id;
                $path[] = $selectorPart;
                break; // Keluar dari loop
            }
            if ($classNames) {
                $selectorPart .= '.' . implode('.', explode(' ', $classNames));
            }

            array_unshift($path, $selectorPart);
            $currentNode = $currentNode->getNode(0)->parentNode ? new Crawler($currentNode->getNode(0)->parentNode) : null;
        }

        return implode(' > ', $path);
    }

    private function findMatchingDateSelector(string $baseUrl, ?string $crawlUrlPath, string $titleSelector): array
    {
        $successfulDateSelectors = [];
        $dateSelectors = $this->getDateSelectors();

        foreach ($dateSelectors as $selector) {
            try {
                $articles = $this->crawlerService->parseArticles($baseUrl, $crawlUrlPath ?? '/', $titleSelector, $selector, null, 5);
                $dateFound = collect($articles)->contains(fn($article) => !empty($article['date']));
                if ($dateFound) {
                    $successfulDateSelectors[] = $selector;
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        return $successfulDateSelectors;
    }

    public function getTitleSelectors(): array
    {
        return Cache::remember('suggestion_selectors_title', 3600, function () {
            return SuggestionSelector::where('type', 'title')
                ->orderBy('priority', 'desc')
                ->pluck('selector')->all();
        });
    }

    public function getDateSelectors(): array
    {
        return Cache::remember('suggestion_selectors_date', 3600, function () {
            return SuggestionSelector::where('type', 'date')
                ->orderBy('priority', 'desc')
                ->pluck('selector')->all();
        });
    }
}