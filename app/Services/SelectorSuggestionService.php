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
        return $this->suggestViaHeuristics($baseUrl, $crawlUrlPath);
    }

    private function suggestViaDictionary(string $baseUrl, ?string $crawlUrlPath): array
    {
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
     * [REWORK FINAL v1.19.4] Logika heuristik dengan pemahaman konteks.
     */
    private function suggestViaHeuristics(string $baseUrl, ?string $crawlUrlPath): array
    {
        try {
            $crawler = $this->crawlerService->fetchHtmlAsCrawler($baseUrl, $crawlUrlPath ?? '/');

            // 1. Buang semua bagian yang tidak relevan terlebih dahulu (header, footer, nav, sidebar)
            $crawler->filter('header, footer, nav, aside, .sidebar, #sidebar, .footer, #footer')->each(function (Crawler $node) {
                foreach ($node as $n) { $n->parentNode->removeChild($n); }
            });

            $candidateLinks = [];
            // 2. Kumpulkan link dari area konten yang sudah bersih
            $crawler->filter('a')->each(function (Crawler $node) use (&$candidateLinks) {
                $text = trim($node->text());
                $href = $node->attr('href');
                if (!empty($href) && !str_starts_with($href, '#') && mb_strlen($text) > 25 && mb_strlen($text) < 200) {
                    $candidateLinks[] = $node;
                }
            });

            if (count($candidateLinks) < 3) {
                throw new \Exception("Tidak cukup kandidat link valid setelah filter.");
            }

            $selectorPatterns = [];
            foreach ($candidateLinks as $linkNode) {
                if ($selectorPath = $this->generateSelectorPath($linkNode)) $selectorPatterns[] = $selectorPath;
            }
            
            if (empty($selectorPatterns)) throw new \Exception("Tidak dapat menghasilkan pola selector.");

            $patternCounts = array_count_values($selectorPatterns);
            arsort($patternCounts);
            $bestPattern = key($patternCounts);
            
            $dateSelectors = $this->findHeuristicDateSelector($crawler, $bestPattern);

            return [
                'success' => true,
                'title_selectors' => [$bestPattern],
                'date_selectors' => $dateSelectors,
                'method' => 'heuristic'
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Analisis heuristik gagal: ' . $e->getMessage(), 'method' => 'heuristic'];
        }
    }

    private function findHeuristicDateSelector(Crawler $crawler, string $titleSelector): array
    {
        // Kode ini tidak perlu diubah, sudah cukup baik dari versi sebelumnya.
        $firstTitleNode = $crawler->filter($titleSelector)->first();
        if ($firstTitleNode->count() === 0) return [];
        $searchContainer = $firstTitleNode->closest('article, .post, .item, li, div, tr') ?? $crawler->filter('body')->first();
        $dateSelectorCandidates = [];
        $searchContainer->filter('*')->each(function (Crawler $node) use (&$dateSelectorCandidates) {
            $text = '';
            foreach ($node->getNode(0)->childNodes as $child) {
                if ($child instanceof \DOMText) $text .= $child->nodeValue;
            }
            if (preg_match('/(\d{1,2}\s+[a-zA-Z\s]+\s+\d{4})|(\d{1,2}[\/\-.]\d{1,2}[\/\-.]\d{2,4})/i', trim($text))) {
                $dateSelectorCandidates[] = $this->generateSelectorPath($node);
            }
        });
        if (empty($dateSelectorCandidates)) return [];
        $patternCounts = array_count_values(array_filter($dateSelectorCandidates));
        arsort($patternCounts);
        return [key($patternCounts)];
    }

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

    private function generateSelectorPath(Crawler $node): ?string
    {
        if ($node->count() === 0) return null;
        $path = [];
        $currentNode = $node;
        for ($i = 0; $i < 10; $i++) {
            if ($currentNode === null || $currentNode->count() === 0 || $currentNode->nodeName() === 'body') break;
            $nodeName = $currentNode->nodeName();
            $id = $currentNode->attr('id');
            $selectorPart = $nodeName;
            if ($id) {
                $selectorPart .= '#' . $id;
                array_unshift($path, $selectorPart);
                break;
            } else {
                $classNames = trim($currentNode->attr('class'));
                if ($classNames) $selectorPart .= '.' . implode('.', preg_split('/\s+/', $classNames));
            }
            array_unshift($path, $selectorPart);
            $domNode = $currentNode->getNode(0);
            $parentNode = $domNode ? $domNode->parentNode : null;
            $currentNode = $parentNode ? new Crawler($parentNode) : null;
        }
        return implode(' > ', $path);
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