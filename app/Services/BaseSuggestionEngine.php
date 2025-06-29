<?php

namespace App\Services;

use Symfony\Component\DomCrawler\Crawler;

abstract class BaseSuggestionEngine
{
    protected $crawlerService;

    public function __construct(CrawlerService $crawlerService)
    {
        $this->crawlerService = $crawlerService;
    }

    /**
     * Metode abstract yang 'memaksa' setiap anak kelas (engine) untuk memiliki
     * metode suggest-nya sendiri.
     */
    abstract public function suggest(string $baseUrl, ?string $crawlUrlPath): array;

    /**
     * Menemukan kandidat blok artikel utama dengan menganalisis pola dari
     * elemen 'kakek' (grandparent) dari link-link yang ada.
     */
    protected function findArticleBlockSelector(Crawler $crawler): ?string
    {
        $grandparentSelectorPatterns = [];
        $crawler->filter('a')->each(function (Crawler $link) use (&$grandparentSelectorPatterns) {
            $text = trim($link->text());
            if (mb_strlen($text) > 20 && mb_strlen($text) < 250 && !str_starts_with($link->attr('href') ?? '#', '#')) {
                $parent = $link->ancestors()->first();
                if ($parent && $parent->count() > 0 && $parent->nodeName() !== 'body') {
                    $grandparent = $parent->ancestors()->first();
                    if ($grandparent && $grandparent->count() > 0 && $grandparent->nodeName() !== 'body') {
                        if ($selector = $this->generateSelectorPath($grandparent)) {
                            $grandparentSelectorPatterns[] = $selector;
                        }
                    }
                }
            }
        });

        if (count($grandparentSelectorPatterns) < 3) {
            return null;
        }

        $patternCounts = array_count_values($grandparentSelectorPatterns);
        arsort($patternCounts);
        return (reset($patternCounts) > 1) ? key($patternCounts) : null;
    }

    /**
     * Menemukan semua pola selector untuk link artikel potensial di dalam crawler.
     */
    protected function findAllLinkPatterns(Crawler $crawler): array
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

    /**
     * Membuat sebuah selector path CSS sederhana dari sebuah node,
     * bergerak ke atas hingga 5 level atau mencapai batas.
     */
    protected function generateSelectorPath(Crawler $node, ?Crawler $boundaryNode = null): ?string
    {
        if ($node->count() === 0) {
            return null;
        }

        $boundaryDomNode = $boundaryNode ? $boundaryNode->getNode(0) : null;
        $path = [];
        $currentNode = $node;

        for ($i = 0; $i < 5; $i++) {
            $currentNodeDom = $currentNode->getNode(0);
            if ($currentNode === null || $currentNode->count() === 0 || $currentNodeDom === $boundaryDomNode || $currentNode->nodeName() === 'body') {
                break;
            }
            $nodeName = $currentNode->nodeName();
            $selectorPart = $nodeName;
            $classNames = trim($currentNode->attr('class'));
            if ($classNames) {
                $classList = preg_split('/\s+/', $classNames);
                $selectorPart .= '.' . $classList[0];
            }
            array_unshift($path, $selectorPart);
            $parentNode = $currentNode->ancestors()->first();
            $currentNode = ($parentNode && $parentNode->count() > 0) ? $parentNode : null;
        }
        return implode(' > ', $path);
    }
}