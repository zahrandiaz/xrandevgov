<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class ExperimentalSuggestionService extends BaseSuggestionEngine
{
    public function suggest(string $baseUrl, ?string $crawlUrlPath): array
    {
        try {
            $crawler = $this->crawlerService->fetchHtmlAsCrawler($baseUrl, $crawlUrlPath ?? '/');
            
            $crawler->filter('header, footer, nav, aside, .sidebar, #sidebar, .footer, #footer, script, style, .breaking-news, .news-ticker')->each(function (Crawler $node) {
                foreach ($node as $n) { if ($n->parentNode) { $n->parentNode->removeChild($n); } }
            });

            $linkPatterns = $this->findAllLinkPatterns($crawler);
            if (empty($linkPatterns)) { throw new \Exception("Analisis Gagal: Tidak ada kandidat link yang ditemukan setelah pembersihan."); }
            
            $patternCounts = array_count_values($linkPatterns);
            arsort($patternCounts);
            $bestTitlePattern = key($patternCounts);
            $bestDatePattern = null;

            if ($bestTitlePattern) {
                // Prioritas 1: Cari di blok meta standar.
                $bestDatePattern = $this->findDatePatternInMetaBlocks($crawler, $bestTitlePattern);

                // Prioritas 2: Cari berdasarkan class name umum.
                if (!$bestDatePattern) {
                    Log::info("Heuristik v4.12: Gagal di blok meta, mencoba via Class Name.");
                    $bestDatePattern = $this->findDatePatternViaCommonClassNames($crawler, $bestTitlePattern);
                }
                
                // Prioritas 3: Cari di seluruh blok pembungkus utama (Grandparent).
                if (!$bestDatePattern) {
                    Log::info("Heuristik v4.12: Gagal via Class Name, mencoba pemindaian Grandparent Block.");
                    $bestDatePattern = $this->findDateInGrandparentBlock($crawler, $bestTitlePattern);
                }
            }

            // Prioritas 4: Fallback ke analisis blok menyeluruh jika semua gagal.
            if (!$bestDatePattern) {
                Log::info("Heuristik v4.12: Gagal di semua metode, mencoba analisis blok artikel menyeluruh.");
                $blockSelector = $this->findArticleBlockSelector($crawler);
                if ($blockSelector) {
                    $articleBlocks = $crawler->filter($blockSelector)->each(fn ($node) => $node);
                    if (count($articleBlocks) >= 3) {
                        $bestDatePattern = $this->findDatePatternExhaustivelyInBlocks($articleBlocks, $bestTitlePattern);
                    }
                }
            }

            return ['success' => true, 'title_selectors' => [$bestTitlePattern], 'date_selectors' => $bestDatePattern ? [$bestDatePattern] : [], 'method' => 'heuristic_v4.12'];
        } catch (\Exception $e) {
            Log::error("Heuristik v4.12 (Eksperimental) Gagal Total: " . $e->getMessage() . " on line " . $e->getLine());
            return ['success' => false, 'message' => $e->getMessage(), 'method' => 'heuristic_v4.12'];
        }
    }
    
    /**
     * [REWORK v1.29.0 - v4.12] Logika Grandparent yang paling presisi
     */
    private function findDateInGrandparentBlock(Crawler $crawler, string $titleSelector): ?string
    {
        try {
            $firstTitleNode = $crawler->filter($titleSelector)->first();
            if ($firstTitleNode->count() === 0) return null;
        } catch (\Exception $e) {
            Log::warning("Selector judul '{$titleSelector}' tidak valid saat mencari Grandparent.");
            return null;
        }

        // [FIX v4.12] Logika pencarian container yang lebih definitif
        // Coba cari container ideal dulu
        $container = $firstTitleNode->closest('article, .post, .media, .item-list, .blog-item');
        // Jika tidak ketemu, naik dua level dari judul (a -> h5 -> div)
        if (!$container || $container->count() === 0) {
            $parent = $firstTitleNode->ancestors()->first();
            if ($parent && $parent->count() > 0) {
                $container = $parent->ancestors()->first();
            }
        }
        if (!$container || $container->count() === 0) return null;

        $dateComponentRegex = '/(jan|feb|mar|apr|mei|jun|jul|agu|sep|okt|nov|des|january|february|march|april|may|june|july|august|september|october|november|december|\d{4}|\d{1,2})/i';
        
        $bestCandidateNode = null;
        $highestScore = 0;

        $container->filter('*')->each(function (Crawler $node) use ($dateComponentRegex, &$bestCandidateNode, &$highestScore, $firstTitleNode) {
            if ($node->getNode(0) === $firstTitleNode->getNode(0)) return; 
            
            $text = trim($node->text());
            if (strlen($text) > 2 && strlen($text) < 50) { 
                preg_match_all($dateComponentRegex, strtolower($text), $matches);
                $score = count($matches[0]);
                
                if (preg_match('/(jan|feb|mar|apr|mei|jun|jul|agu|sep|okt|nov|des)/i', $text)) {
                    $score += 2;
                }

                if ($score > $highestScore) {
                    $highestScore = $score;
                    $bestCandidateNode = $node;
                }
            }
        });
        
        if ($bestCandidateNode && $highestScore >= 2) {
            return $this->generateSelectorPath($bestCandidateNode, $container);
        }

        return null;
    }

    private function findDatePatternInMetaBlocks(Crawler $crawler, string $titleSelector): ?string
    {
        try {
            $firstTitleNode = $crawler->filter($titleSelector)->first();
            if ($firstTitleNode->count() === 0) return null;
        } catch(\Exception $e) { return null; }

        $container = $firstTitleNode->closest('article, .post, .item, li, div');
        if(!$container || $container->count() === 0) return null;

        $metaBlock = $container->filter('.post-meta, .entry-meta, .meta-info, .byline, .post-info, .posted-on')->first();
        if($metaBlock->count() > 0){
            $datePatterns = [];
            
            $metaBlock->filter('*')->each(function (Crawler $node) use (&$datePatterns, $container) {
                 if (preg_match('/(\d{1,2}\s+[a-zA-Z\s]+\s+\d{4})|(\d{1,2}[\/\-.]\d{1,2}[\/\-.]\d{2,4})|kemarin|hari\s*ini|\d+\s*.*\s*(ago|lalu)/i', trim($node->text()))) {
                    if ($path = $this->generateSelectorPath($node, $container)) {
                        $datePatterns[] = $path;
                    }
                }
            });

            if (empty($datePatterns)) return null;
            
            $longestPath = '';
            foreach($datePatterns as $path) {
                if(strlen($path) > strlen($longestPath)) {
                    $longestPath = $path;
                }
            }
            return $longestPath;
        }

        return null;
    }

    private function findDatePatternViaCommonClassNames(Crawler $crawler, string $titleSelector): ?string
    {
        try {
            $firstTitleNode = $crawler->filter($titleSelector)->first();
            if ($firstTitleNode->count() === 0) return null;
        } catch(\Exception $e) { return null; }

        $container = $firstTitleNode->closest('article, .post, .item, li, div, .post-content, .post-item');
        if(!$container || $container->count() === 0) return null;
        
        $commonDateClasses = ['.date', '.post-date', '.entry-date', '.meta-date', 'time', '.posted-on'];
        foreach($commonDateClasses as $class) {
            $dateNode = $container->filter($class)->first();
            if ($dateNode->count() > 0) {
                 if (preg_match('/(\d{1,2}[\s\S]*[a-zA-Z]+[\s\S]*\d{4})|(\d{1,2}[\/\-.]\d{1,2}[\/\-.]\d{2,4})|kemarin|hari\s*ini/i', trim($dateNode->text()))) {
                     return $this->generateSelectorPath($dateNode, $container);
                 }
            }
        }
        return null;
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
        $patternCounts = array_count_values(array_filter($datePatterns));
        arsort($patternCounts);
        return key($patternCounts);
    }
}