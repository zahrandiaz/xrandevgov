<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class ExperimentalSuggestionService extends BaseSuggestionEngine
{
    /**
     * [REFAKTOR v1.23] Metode utama untuk menjalankan mesin heuristik eksperimental v4.
     */
    public function suggest(string $baseUrl, ?string $crawlUrlPath): array
    {
        try {
            $crawler = $this->crawlerService->fetchHtmlAsCrawler($baseUrl, $crawlUrlPath ?? '/');
            $crawler->filter('header, footer, nav, aside, .sidebar, #sidebar, .footer, #footer, script, style')->each(function (Crawler $node) {
                foreach ($node as $n) { if ($n->parentNode) { $n->parentNode->removeChild($n); } }
            });

            $linkPatterns = $this->findAllLinkPatterns($crawler);
            if (empty($linkPatterns)) { throw new \Exception("Analisis Gagal: Tidak ada kandidat link yang ditemukan."); }
            
            $patternCounts = array_count_values($linkPatterns);
            arsort($patternCounts);
            $bestTitlePattern = key($patternCounts);
            $bestDatePattern = null;

            $blockSelector = $this->findArticleBlockSelector($crawler);
            if ($blockSelector) {
                $articleBlocks = $crawler->filter($blockSelector)->each(fn ($node) => $node);
                if (count($articleBlocks) >= 3) {
                    $bestDatePattern = $this->findDatePatternExhaustivelyInBlocks($articleBlocks, $bestTitlePattern);
                }
            }
            
            if (!$bestDatePattern) {
                Log::info("Heuristik v4.2: Gagal menemukan tanggal di blok, mencoba analisis Sibling.");
                $bestDatePattern = $this->findDatePatternViaSiblingAnalysis($crawler, $bestTitlePattern);
            }

            if (!$bestDatePattern) {
                Log::info("Heuristik v4.2: Analisis Sibling gagal, mencoba pencarian Global.");
                $bestDatePattern = $this->findAnyDatePatternInDocument($crawler);
            }

            return ['success' => true, 'title_selectors' => [$bestTitlePattern], 'date_selectors' => $bestDatePattern ? [$bestDatePattern] : [], 'method' => 'heuristic_v4.2'];
        } catch (\Exception $e) {
            Log::error("Heuristik v4.2 (Eksperimental) Gagal Total: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage(), 'method' => 'heuristic_v4.2'];
        }
    }

    private function findDatePatternExhaustivelyInBlocks(array $blocks, string $titleSelector): ?string
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
                    if ($path = $this->generateSelectorPath($node, $block)) { $datePatterns[] = $path; }
                }
            });
        }
        if (empty($datePatterns)) { return null; }
        $patternCounts = array_count_values($datePatterns);
        arsort($patternCounts);
        return key($patternCounts);
    }

    private function findDatePatternViaSiblingAnalysis(Crawler $crawler, string $titleSelector): ?string
    {
        $datePatterns = [];
        $titleNodes = $crawler->filter($titleSelector);
        if ($titleNodes->count() == 0) { return null; }

        $titleContainer = $titleNodes->first()->ancestors()->first();
        if (!$titleContainer || $titleContainer->count() === 0) { return null; }
        
        $nodesToScan = [];
        $titleContainer->previousAll()->slice(0, 3)->each(function (Crawler $node) use (&$nodesToScan) { $nodesToScan[] = $node; });
        $titleContainer->nextAll()->slice(0, 3)->each(function (Crawler $node) use (&$nodesToScan) { $nodesToScan[] = $node; });

        foreach ($nodesToScan as $scanNode) {
            $scanNode->filter('*')->each(function (Crawler $node) use (&$datePatterns) {
                $text = '';
                foreach ($node->getNode(0)->childNodes as $child) {
                    if ($child instanceof \DOMText) { $text .= " " . $child->nodeValue; }
                }
                if (preg_match('/(\d{1,2}\s+[a-zA-Z\s]+\s+\d{4})|(\d{1,2}[\/\-.]\d{1,2}[\/\-.]\d{2,4})|kemarin|hari\s*ini/i', trim($text))) {
                    if ($path = $this->generateSelectorPath($node)) { $datePatterns[] = $path; }
                }
            });
        }
        
        if (empty($datePatterns)) { return null; }
        $patternCounts = array_count_values($datePatterns);
        arsort($patternCounts);
        return key($patternCounts);
    }

    private function findAnyDatePatternInDocument(Crawler $crawler): ?string
    {
        $datePatterns = [];
        $crawler->filter('*')->each(function (Crawler $node) use (&$datePatterns) {
            $text = '';
            foreach ($node->getNode(0)->childNodes as $child) {
                if ($child instanceof \DOMText) { $text .= " " . $child->nodeValue; }
            }
            if (preg_match('/(\d{1,2}\s+[a-zA-Z\s]+\s+\d{4})|(\d{1,2}[\/\-.]\d{1,2}[\/\-.]\d{2,4})|kemarin|hari\s*ini/i', trim($text))) {
                if ($path = $this->generateSelectorPath($node)) { $datePatterns[] = $path; }
            }
        });
        if (empty($datePatterns)) { return null; }
        $patternCounts = array_count_values($datePatterns);
        arsort($patternCounts);
        return key($patternCounts);
    }
}