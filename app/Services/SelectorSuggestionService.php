<?php

namespace App\Services;

use App\Models\SuggestionSelector;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

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
            return $dictionaryResult;
        }
        
        // [v1.20.3] Panggil logika heuristik Hibrida Level 3.3
        return $this->suggestViaHeuristicsV3_3($baseUrl, $crawlUrlPath);
    }

    private function suggestViaDictionary(string $baseUrl, ?string $crawlUrlPath): array
    {
        // Fungsi ini tidak berubah
        $titleSelectors = $this->getTitleSelectors();
        foreach ($titleSelectors as $selector) {
            try {
                $this->crawlerService->parseArticles($baseUrl, $crawlUrlPath ?? '/', $selector, null, null, 1);
                $dateSelectors = $this->findMatchingDateSelectorFromDictionary($baseUrl, $crawlUrlPath, $selector);
                return ['success' => true, 'title_selectors' => [$selector], 'date_selectors' => $dateSelectors, 'method' => 'dictionary'];
            } catch (\Exception $e) { continue; }
        }
        return ['success' => false];
    }
    
    /**
     * [REWORK TOTAL v1.20.3 - Final Hotfix] Logika Heuristik Hibrida Level 3.3.
     */
    private function suggestViaHeuristicsV3_3(string $baseUrl, ?string $crawlUrlPath): array
    {
        try {
            $crawler = $this->crawlerService->fetchHtmlAsCrawler($baseUrl, $crawlUrlPath ?? '/');

            $crawler->filter('header, footer, nav, aside, .sidebar, #sidebar, .footer, #footer, script, style')->each(function (Crawler $node) {
                foreach ($node as $n) { if($n->parentNode) {$n->parentNode->removeChild($n);} }
            });

            // --- STRATEGI 1: Analisis Blok (v3.3) ---
            $blockSelector = $this->findArticleBlockSelectorViaReversePatternV2($crawler);
            
            if ($blockSelector) {
                $articleBlocks = $crawler->filter($blockSelector)->each(fn($node) => $node);
                if (count($articleBlocks) >= 3) {
                    $bestTitlePattern = $this->findBestTitlePatternInBlocks($articleBlocks);
                    if ($bestTitlePattern) {
                        $bestDatePattern = $this->findBestDatePatternInBlocks($articleBlocks, $bestTitlePattern);
                        return [
                            'success' => true, 'title_selectors' => [$bestTitlePattern],
                            'date_selectors' => $bestDatePattern ? [$bestDatePattern] : [],
                            'method' => 'heuristic_v3.3 (Block Analysis)'
                        ];
                    }
                }
            }

            // --- STRATEGI 2: Fallback ke Analisis Pola Link Langsung ---
            Log::info("Heuristik v3.3: Gagal menemukan blok, fallback ke analisis pola link langsung.");
            $linkPatterns = $this->findAllLinkPatterns($crawler);
            if (empty($linkPatterns)) {
                throw new \Exception("Analisis Gagal: Tidak ada kandidat link yang ditemukan.");
            }
            
            $patternCounts = array_count_values($linkPatterns);
            arsort($patternCounts);
            $bestTitlePattern = key($patternCounts);
            
            $bestDatePattern = $this->findBestDatePatternInBlocks($crawler->filter('body')->each(fn($n)=>$n), $bestTitlePattern);

            return [
                'success' => true,
                'title_selectors' => [$bestTitlePattern],
                'date_selectors' => $bestDatePattern ? [$bestDatePattern] : [],
                'method' => 'heuristic_v3.3 (Direct Pattern Fallback)'
            ];

        } catch (\Exception $e) {
            Log::error("Heuristik v3.3 Gagal Total: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage(), 'method' => 'heuristic_v3.3'];
        }
    }

    /**
     * [MODIFIKASI v1.20.3] Metode ini sekarang melihat 2 level ke atas (kakek) untuk menemukan blok yang lebih akurat.
     */
    private function findArticleBlockSelectorViaReversePatternV2(Crawler $crawler): ?string
    {
        $grandparentSelectorPatterns = [];

        $crawler->filter('a')->each(function(Crawler $link) use (&$grandparentSelectorPatterns) {
            $text = trim($link->text());
            if (mb_strlen($text) > 20 && mb_strlen($text) < 250 && !str_starts_with($link->attr('href') ?? '#', '#')) {
                // Ambil parent dari link
                $parent = $link->ancestors()->first();
                if ($parent && $parent->count() > 0 && $parent->nodeName() !== 'body') {
                    // Ambil "kakek"-nya (parent dari parent)
                    $grandparent = $parent->ancestors()->first();
                    if ($grandparent && $grandparent->count() > 0 && $grandparent->nodeName() !== 'body') {
                        if ($selector = $this->generateSelectorPath($grandparent)) {
                            $grandparentSelectorPatterns[] = $selector;
                        }
                    }
                }
            }
        });

        if (count($grandparentSelectorPatterns) < 3) return null;
        $patternCounts = array_count_values($grandparentSelectorPatterns);
        arsort($patternCounts);
        return (reset($patternCounts) > 1) ? key($patternCounts) : null;
    }

    private function findBestTitlePatternInBlocks(array $blocks): ?string
    {
        $patterns = [];
        foreach ($blocks as $block) {
            // [BUG FIX v1.20.2] Variabel $block sekarang di-pass ke dalam 'use'
            $block->filter('a')->each(function (Crawler $link) use (&$patterns, $block) {
                if (mb_strlen(trim($link->text())) > 25) {
                    $patterns[] = $this->generateSelectorPath($link, $block);
                }
            });
        }
        if (empty($patterns)) return null;
        $patternCounts = array_count_values(array_filter($patterns));
        arsort($patternCounts);
        return (reset($patternCounts) > 1) ? key($patternCounts) : null;
    }
    
    private function findBestDatePatternInBlocks(array $blocks, string $titleSelector): ?string
    {
        $datePatterns = [];
        foreach ($blocks as $block) {
            if ($block->filter($titleSelector)->count() === 0) continue;
            
            $block->filter('*')->each(function (Crawler $node) use (&$datePatterns, $block) {
                $text = '';
                foreach ($node->getNode(0)->childNodes as $child) {
                    if ($child instanceof \DOMText) { $text .= " " . $child->nodeValue; }
                }
                if (preg_match('/(\d{1,2}\s+[a-zA-Z\s]+\s+\d{4})|(\d{1,2}[\/\-.]\d{1,2}[\/\-.]\d{2,4})|kemarin|hari\s*ini/i', trim($text))) {
                    $datePatterns[] = $this->generateSelectorPath($node, $block);
                }
            });
        }
        if (empty($datePatterns)) return null;
        $patternCounts = array_count_values(array_filter($datePatterns));
        arsort($patternCounts);
        return key($patternCounts);
    }

    /**
     * [BARU v1.20.2] Fungsi helper untuk strategi fallback (mirip AI v2).
     */
    private function findAllLinkPatterns(Crawler $crawler): array
    {
        $patterns = [];
        $crawler->filter('a')->each(function (Crawler $link) use (&$patterns) {
             $text = trim($link->text());
            if (mb_strlen($text) > 25 && mb_strlen($text) < 250 && !str_starts_with($link->attr('href') ?? '#', '#')) {
                if ($selector = $this->generateSelectorPath($link)) {
                    $patterns[] = $selector;
                }
            }
        });
        return $patterns;
    }

    private function generateSelectorPath(Crawler $node, ?Crawler $boundaryNode = null): ?string
    {
        if ($node->count() === 0) return null;
        $boundaryDomNode = $boundaryNode ? $boundaryNode->getNode(0) : null;
        $path = [];
        $currentNode = $node;

        for ($i = 0; $i < 5; $i++) { // Batasi kedalaman agar tidak terlalu spesifik
            $currentNodeDom = $currentNode->getNode(0);
            if ($currentNode === null || $currentNode->count() === 0 || $currentNodeDom === $boundaryDomNode || $currentNode->nodeName() === 'body') break;
            $nodeName = $currentNode->nodeName();
            $selectorPart = $nodeName;

            $classNames = trim($currentNode->attr('class'));
            if ($classNames) {
                $classList = preg_split('/\s+/', $classNames);
                $selectorPart .= '.' . $classList[0]; // Ambil kelas pertama saja
            }
            array_unshift($path, $selectorPart);
            $parentNode = $currentNode->ancestors()->first();
            $currentNode = ($parentNode && $parentNode->count() > 0) ? $parentNode : null;
        }
        return implode(' > ', $path);
    }

    // --- METODE LAMA & METODE KAMUS (TIDAK BERUBAH) ---

    private function findMatchingDateSelectorFromDictionary(string $baseUrl, ?string $crawlUrlPath, string $titleSelector): array
    {
        $dateSelectors = $this->getDateSelectors();
        foreach ($dateSelectors as $selector) {
            try {
                $articles = $this->crawlerService->parseArticles($baseUrl, $crawlUrlPath ?? '/', $titleSelector, $selector, null, 5);
                if (collect($articles)->contains(fn($a) => !empty($a['date']))) return [$selector];
            } catch (\Exception $e) { continue; }
        }
        return [];
    }
    
    public function getTitleSelectors(): array
    {
        return Cache::remember('suggestion_selectors_title', 3600, function () {
            return SuggestionSelector::where('type', 'title')->orderBy('priority', 'desc')->pluck('selector')->all();
        });
    }

    public function getDateSelectors(): array
    {
        return Cache::remember('suggestion_selectors_date', 3600, function () {
            return SuggestionSelector::where('type', 'date')->orderBy('priority', 'desc')->pluck('selector')->all();
        });
    }
}