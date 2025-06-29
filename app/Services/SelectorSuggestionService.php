<?php

namespace App\Services;

use App\Models\SuggestionSelector;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class SelectorSuggestionService extends BaseSuggestionEngine
{
    /**
     * [REFAKTOR v1.23] Metode utama yang hanya menjalankan strategi stabil.
     */
    public function suggest(string $baseUrl, ?string $crawlUrlPath): array
    {
        $foundTitleSelectors = [];
        $foundDateSelectors = [];

        $dictionaryResult = $this->suggestViaDictionary($baseUrl, $crawlUrlPath);

        if ($dictionaryResult['success']) {
            $foundTitleSelectors = $dictionaryResult['title_selectors'];
            $foundDateSelectors = $dictionaryResult['date_selectors'];
            
            if (!empty($foundTitleSelectors) && !empty($foundDateSelectors)) {
                return $dictionaryResult;
            }
        }

        Log::info("Memulai analisis dengan Mesin Stabil (v3)...");
        $heuristicResult = $this->suggestViaHeuristicsStableV3($baseUrl, $crawlUrlPath);

        if ($heuristicResult['success']) {
            $finalTitleSelectors = !empty($foundTitleSelectors) ? $foundTitleSelectors : $heuristicResult['title_selectors'];
            $finalDateSelectors = !empty($foundDateSelectors) ? $foundDateSelectors : $heuristicResult['date_selectors'];
            
            return [
                'success' => true,
                'title_selectors' => $finalTitleSelectors,
                'date_selectors' => $finalDateSelectors,
                'method' => 'hybrid_dictionary_heuristic_v3'
            ];
        }
        
        return $dictionaryResult;
    }

    private function suggestViaHeuristicsStableV3(string $baseUrl, ?string $crawlUrlPath): array
    {
        try {
            $crawler = $this->crawlerService->fetchHtmlAsCrawler($baseUrl, $crawlUrlPath ?? '/');
            $crawler->filter('header, footer, nav, aside, .sidebar, #sidebar, .footer, #footer, script, style')->each(function (Crawler $node) {
                foreach ($node as $n) { if ($n->parentNode) { $n->parentNode->removeChild($n); } }
            });

            $blockSelector = $this->findArticleBlockSelector($crawler);
            if ($blockSelector) {
                $articleBlocks = $crawler->filter($blockSelector)->each(fn ($node) => $node);
                if (count($articleBlocks) >= 3) {
                    $bestTitlePattern = $this->findBestTitlePatternInBlocks($articleBlocks);
                    if ($bestTitlePattern) {
                        $bestDatePattern = $this->findBestDatePatternInBlocks($articleBlocks, $bestTitlePattern);
                        return ['success' => true, 'title_selectors' => [$bestTitlePattern], 'date_selectors' => $bestDatePattern ? [$bestDatePattern] : [], 'method' => 'heuristic_v3.3 (Block Analysis)'];
                    }
                }
            }

            Log::info("Heuristik v3.3 (Stabil): Gagal menemukan blok, fallback ke analisis pola link langsung.");
            $linkPatterns = $this->findAllLinkPatterns($crawler);
            if (empty($linkPatterns)) { throw new \Exception("Analisis Gagal: Tidak ada kandidat link yang ditemukan."); }
            
            $patternCounts = array_count_values($linkPatterns);
            arsort($patternCounts);
            $bestTitlePattern = key($patternCounts);
            $bestDatePattern = $this->findBestDatePatternInBlocks($crawler->filter('body')->each(fn ($n)=>$n), $bestTitlePattern);

            return ['success' => true, 'title_selectors' => [$bestTitlePattern], 'date_selectors' => $bestDatePattern ? [$bestDatePattern] : [], 'method' => 'heuristic_v3.3 (Direct Pattern Fallback)'];
        } catch (\Exception $e) {
            Log::error("Heuristik v3.3 (Stabil) Gagal Total: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage(), 'method' => 'heuristic_v3.3'];
        }
    }

    private function findBestTitlePatternInBlocks(array $blocks): ?string
    {
        $patterns = [];
        foreach ($blocks as $block) {
            $block->filter('a')->each(function (Crawler $link) use (&$patterns, $block) {
                if (mb_strlen(trim($link->text())) > 25) {
                    $patterns[] = $this->generateSelectorPath($link, $block);
                }
            });
        }
        if (empty($patterns)) { return null; }
        $patternCounts = array_count_values(array_filter($patterns));
        arsort($patternCounts);
        return (reset($patternCounts) > 1) ? key($patternCounts) : null;
    }

    private function findBestDatePatternInBlocks(array $blocks, string $titleSelector): ?string
    {
        $datePatterns = [];
        foreach ($blocks as $block) {
            if ($block->filter($titleSelector)->count() === 0) { continue; }
            
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
        if (empty($datePatterns)) { return null; }
        $patternCounts = array_count_values(array_filter($datePatterns));
        arsort($patternCounts);
        return key($patternCounts);
    }
    
    private function suggestViaDictionary(string $baseUrl, ?string $crawlUrlPath): array
    {
        $titleSelector = $this->findMatchingSelectorFromDictionary($baseUrl, $crawlUrlPath, 'title');
        if (!$titleSelector) { return ['success' => false, 'title_selectors' => [], 'date_selectors' => []]; }
        $dateSelector = $this->findMatchingSelectorFromDictionary($baseUrl, $crawlUrlPath, 'date', $titleSelector);
        return ['success' => true, 'title_selectors' => [$titleSelector], 'date_selectors' => $dateSelector ? [$dateSelector] : [], 'method' => 'dictionary'];
    }
    
    private function findMatchingSelectorFromDictionary(string $baseUrl, ?string $crawlUrlPath, string $type, ?string $titleSelectorForDateCheck = null): ?string
    {
        $selectors = ($type === 'title') ? $this->getTitleSelectors() : $this->getDateSelectors();
        foreach ($selectors as $selector) {
            try {
                if ($type === 'date' && $titleSelectorForDateCheck) {
                    $articles = $this->crawlerService->parseArticles($baseUrl, $crawlUrlPath ?? '/', $titleSelectorForDateCheck, $selector, null, 3);
                    if (collect($articles)->contains(fn ($a) => !empty($a['date']))) { return $selector; }
                } else {
                    $this->crawlerService->parseArticles($baseUrl, $crawlUrlPath ?? '/', $selector, null, null, 1);
                    return $selector;
                }
            } catch (\Exception $e) { continue; }
        }
        return null;
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