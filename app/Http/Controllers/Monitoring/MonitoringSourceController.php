<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use App\Models\MonitoringSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient; // Tambahkan ini

class MonitoringSourceController extends Controller
{
    /**
     * Display a listing of the monitoring sources and a link to add new.
     */
    public function index()
    {
        $sources = MonitoringSource::orderBy('name')->get();
        return view('sources.index', compact('sources'));
    }

    /**
     * Show the form for creating a new monitoring source.
     */
    public function create() // Tambah metode ini
    {
        return view('sources.create'); // View untuk form tambah situs
    }

    /**
     * Store a newly created monitoring source in storage (from create form).
     */
    public function store(Request $request) // Modifikasi metode ini
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url:http,https|ends_with:.go.id|unique:monitoring_sources,url',
            'crawl_url' => 'nullable|string',
            'selector_title' => 'required|string', // Wajib ada sekarang
            'selector_date' => 'nullable|string',
            'selector_link' => 'nullable|string',
        ]);

        // Pastikan URL utama memiliki skema
        if (!preg_match("~^(?:f|ht)tps?://~i", $validatedData['url'])) {
            $validatedData['url'] = "https://" . $validatedData['url'];
        }

        // Default crawl_url jika kosong
        if (empty($validatedData['crawl_url'])) {
            $validatedData['crawl_url'] = '/';
        }

        MonitoringSource::create($validatedData);

        return redirect()->route('monitoring.sources.index')
                         ->with('success', 'Situs monitoring berhasil ditambahkan!');
    }

    /**
     * Show the form for editing the specified monitoring source.
     */
    public function edit(MonitoringSource $source) // Tambah metode ini
    {
        return view('sources.edit', compact('source')); // View untuk form edit situs
    }

    /**
     * Update the specified monitoring source in storage.
     */
    public function update(Request $request, MonitoringSource $source)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url:http,https|ends_with:.go.id|unique:monitoring_sources,url,'.$source->id, // Unique kecuali ID ini
            'crawl_url' => 'nullable|string',
            'selector_title' => 'required|string',
            'selector_date' => 'nullable|string',
            'selector_link' => 'nullable|string',
            // 'is_active' => 'boolean', // Hapus validasi ini karena kita akan menanganinya secara manual
        ]);

        // Pastikan URL utama memiliki skema
        if (!preg_match("~^(?:f|ht)tps?://~i", $validatedData['url'])) {
            $validatedData['url'] = "https://" . $validatedData['url'];
        }

        // Default crawl_url jika kosong
        if (empty($validatedData['crawl_url'])) {
            $validatedData['crawl_url'] = '/';
        }

        // --- TANGANANI CHECKBOX 'is_active' SECARA EKSPLISIT ---
        // Jika checkbox dicentang, request akan memiliki 'is_active'
        // Jika tidak dicentang, request tidak akan memiliki 'is_active' sama sekali
        $validatedData['is_active'] = $request->has('is_active');
        // --- AKHIR PENANGANAN CHECKBOX ---

        $source->update($validatedData);

        return redirect()->route('monitoring.sources.index')
                         ->with('success', 'Situs monitoring berhasil diperbarui!');
    }

    /**
     * Remove the specified monitoring source from storage.
     */
    public function destroy(MonitoringSource $source) // Tambah metode ini
    {
        $source->delete();
        return redirect()->route('monitoring.sources.index')
                         ->with('success', 'Situs monitoring berhasil dihapus!');
    }

    /**
     * Perform web crawling on active monitoring sources and display results.
     */
    public function crawl(Request $request)
    {
        $httpClient = HttpClient::create(['verify_peer' => false, 'verify_host' => false]);
        $client = new HttpBrowser($httpClient);

        $articles = [];
        $sources = MonitoringSource::where('is_active', true)->orderBy('name')->get();
        $errorMessages = []; // Tambahkan array ini untuk menyimpan pesan error

        foreach ($sources as $source) {
            try {
                $source->update(['last_crawled_at' => now()]);

                $fullCrawlUrl = $source->crawl_url;
                if (strpos($fullCrawlUrl, 'http') !== 0) {
                    $fullCrawlUrl = rtrim($source->url, '/') . '/' . ltrim($fullCrawlUrl, '/');
                }

                $crawler = $client->request('GET', $fullCrawlUrl);
                $sourceArticles = [];

                $titleSelector = $source->selector_title ?: 'h1 a, h2 a, h3 a, .post-title a, .entry-title a';
                $dateSelector = $source->selector_date ?: 'time, .date, .post-date, .entry-date';
                $linkSelector = $source->selector_link ?: 'a';

                $crawler->filter($titleSelector)->each(function ($node) use (&$sourceArticles, $source, $dateSelector, $linkSelector, &$errorMessages) { // Tambahkan &errorMessages
                    try {
                        $title = trim($node->text());
                        $link = null;

                        // ... (logika pengambilan link yang sudah ada) ...
                        if (!empty($linkSelector) && $node->filter($linkSelector)->count() > 0) {
                            $firstLinkNodeCrawler = $node->filter($linkSelector)->first();
                            $domElement = $firstLinkNodeCrawler->getNode(0);

                            if ($domElement instanceof \DOMElement && $domElement->hasAttribute('href')) {
                                $link = $domElement->getAttribute('href');
                            } else {
                                $linkObject = $firstLinkNodeCrawler->link();
                                if ($linkObject) {
                                    $link = $linkObject->getUri();
                                }
                            }
                        }
                        elseif ($node->getNode(0) instanceof \DOMElement && $node->getNode(0)->nodeName === 'a' && $node->getNode(0)->hasAttribute('href')) {
                            $link = $node->getNode(0)->getAttribute('href');
                        }
                        else {
                            $readMoreNode = $node->closest('div, p, article, li')->filter('a[href*="berita"], a[href*="artikel"], a[href*="read"], a.btn-primary')->first();
                            if ($readMoreNode->count() > 0) {
                                $readMoreDomElement = $readMoreNode->getNode(0);
                                if ($readMoreDomElement instanceof \DOMElement && $readMoreDomElement->hasAttribute('href')) {
                                    $link = $readMoreDomElement->getAttribute('href');
                                }
                            }
                        }
                        // ... (akhir logika pengambilan link) ...

                        // ... (logika pengambilan tanggal yang sudah ada) ...
                        $date = 'Tanggal Tidak Ditemukan';
                        $dateCandidateNode = null;

                        if (!empty($dateSelector) && $node->filter($dateSelector)->count() > 0) {
                            $dateCandidateNode = $node->filter($dateSelector)->first();
                        } else {
                            $dateCandidateNode = $node->closest('li, div, article, p, span')->filter($dateSelector)->first();
                        }

                        if ($dateCandidateNode && $dateCandidateNode->count() > 0) {
                            $dateDomElement = $dateCandidateNode->getNode(0);
                            if ($dateDomElement instanceof \DOMElement) {
                                $dateText = trim($dateDomElement->textContent);
                                $parsedDate = strtotime($dateText);
                                if ($parsedDate !== false) {
                                    $date = date('Y-m-d', $parsedDate);
                                } else {
                                    if ($dateDomElement->hasAttribute('datetime')) {
                                        $date = date('Y-m-d', strtotime($dateDomElement->getAttribute('datetime')));
                                    }
                                }
                            }
                        }
                        // ... (akhir logika pengambilan tanggal) ...

                        if ($link && strpos($link, 'http') !== 0) {
                            $link = rtrim($source->url, '/') . '/' . ltrim($link, '/');
                        }

                        $parsedSourceUrl = parse_url($source->url, PHP_URL_HOST);
                        $parsedArticleLinkHost = $link ? parse_url($link, PHP_URL_HOST) : null;

                        if (filter_var($link, FILTER_VALIDATE_URL) && $title && $parsedSourceUrl === $parsedArticleLinkHost) {
                            $sourceArticles[] = [
                                'title' => $title,
                                'link' => $link,
                                'date' => $date,
                                'source' => $source->name,
                            ];
                        } else {
                            // Logika jika artikel tidak valid setelah parsing
                            Log::warning('Invalid article data from ' . $source->url . ': Title: ' . $title . ' Link: ' . $link);
                            $errorMessages[] = "Artikel tidak lengkap atau tidak valid dari {$source->name} ({$source->url}).";
                        }
                    } catch (\Exception $e) {
                        Log::warning('Error parsing single article from ' . $source->url . ': ' . $e->getMessage());
                        $errorMessages[] = "Gagal memproses satu artikel dari {$source->name} ({$source->url}).";
                    }
                });

                $articles = array_merge($articles, array_slice($sourceArticles, 0, 5));

            } catch (\GuzzleHttp\Exception\ConnectException $e) {
                Log::error("Could not connect to {$source->url}: " . $e->getMessage());
                $errorMessages[] = "Gagal terhubung ke {$source->name} ({$source->url}). Pastikan URL benar dan situs aktif.";
            } catch (\Exception $e) {
                Log::error("Error crawling {$source->url}: " . $e->getMessage());
                $errorMessages[] = "Terjadi kesalahan saat mengambil data dari {$source->name} ({$source->url}). Error: " . $e->getMessage();
            }
        }

        // Flash semua pesan error ke session
        if (!empty($errorMessages)) {
            session()->flash('crawl_errors', $errorMessages);
        }

        return view('sources.index', compact('articles', 'sources'))
                    ->with('crawling_done', true);
    }
}