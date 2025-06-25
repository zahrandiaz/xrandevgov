<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use App\Models\MonitoringSource;
use App\Models\SelectorPreset;
use App\Models\CrawledArticle;
use App\Models\Region;
use App\Jobs\CrawlSourceJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient; // Tambahkan ini
use Carbon\Carbon;

class MonitoringSourceController extends Controller
{
    /**
     * Display a listing of the monitoring sources and a link to add new.
     */
    public function index()
    {
        // [MODIFIKASI] Gunakan with('region') untuk Eager Loading
        $sources = MonitoringSource::with('region')->orderBy('name')->get();
        return view('sources.index', compact('sources'));
    }

    /**
     * Show the form for creating a new monitoring source.
     */
    public function create()
    {
        $presets = SelectorPreset::all();
        // [BARU] Ambil semua wilayah, diurutkan berdasarkan tipe lalu nama
        $regions = Region::orderBy('type')->orderBy('name')->get(); 
        return view('sources.create', compact('presets', 'regions'));
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
    public function edit(MonitoringSource $source)
    {
        $presets = SelectorPreset::all();
        // [BARU] Ambil semua wilayah
        $regions = Region::orderBy('type')->orderBy('name')->get();
        return view('sources.edit', compact('source', 'presets', 'regions'));
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
     * Test given selectors against a URL and return sample articles.
     * This method is used by the frontend to validate selectors in real-time.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function testSelector(Request $request)
    {
        $validatedData = $request->validate([
            'url' => 'required|url:http,https|ends_with:.go.id',
            'crawl_url' => 'nullable|string',
            'selector_title' => 'required|string',
            'selector_date' => 'nullable|string',
            'selector_link' => 'nullable|string',
        ]);

        $url = $validatedData['url'];
        $crawlUrlPath = $validatedData['crawl_url'] ?? '/';
        $primaryTitleSelector = $validatedData['selector_title']; // Ganti nama variabel
        $primaryDateSelector = $validatedData['selector_date'];   // Ganti nama variabel
        $primaryLinkSelector = $validatedData['selector_link'];   // Ganti nama variabel

        // Pastikan URL utama memiliki skema
        if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
            $url = "https://" . $url;
        }

        $fullCrawlUrl = rtrim($url, '/') . '/' . ltrim($crawlUrlPath, '/');

        $httpClient = HttpClient::create(['verify_peer' => false, 'verify_host' => false]);
        $client = new HttpBrowser($httpClient);

        $sampleArticles = [];
        $errorMessage = '';
        $usedFallback = false; // Flag untuk menandai apakah fallback digunakan

        // [BARU] Definisi Fallback Selectors
        $fallbackSelectors = [
            'title' => [
                'h1 a', '.entry-title a', 'h2 a', 'h3 a', // Umum
                'div.post-title a', '.news-title a', '.article-title a',
                '.media-heading a', 'a.post-link',
            ],
            'date' => [
                'time', '.entry-date', '.post-date', '.article-date',
                '.publish-date', 'span.date', '.info-meta li',
            ],
            'link' => [
                'a.read-more', 'a.btn-read-more', 'a[rel="bookmark"]',
                '.post-item a', '.article-item a',
            ]
        ];

        try {
            $crawler = $client->request('GET', $fullCrawlUrl);

            // [MODIFIKASI LOGIKA] Coba selector utama, jika gagal coba fallback
            $currentTitleSelector = $primaryTitleSelector;
            $currentDateSelector = $primaryDateSelector;
            $currentLinkSelector = $primaryLinkSelector;

            // Logika untuk mencoba selector judul
            if ($crawler->filter($primaryTitleSelector)->count() === 0) {
                $foundTitleWithFallback = false;
                foreach ($fallbackSelectors['title'] as $fbTitleSelector) {
                    if ($crawler->filter($fbTitleSelector)->count() > 0) {
                        $currentTitleSelector = $fbTitleSelector;
                        $foundTitleWithFallback = true;
                        $usedFallback = true;
                        break;
                    }
                }
                if (!$foundTitleWithFallback) {
                    $errorMessage = "Selector Judul utama dan fallback tidak menemukan hasil di halaman ini.";
                }
            }

            // Logika untuk mencoba selector tanggal (jika primary date selector kosong atau tidak ditemukan)
            if (empty($currentDateSelector) || ($crawler->filter($currentTitleSelector)->filter($currentDateSelector)->count() === 0 && $crawler->filter($currentDateSelector)->count() === 0)) {
                $foundDateWithFallback = false;
                foreach ($fallbackSelectors['date'] as $fbDateSelector) {
                     // Prioritaskan mencari di dalam konteks node judul, lalu di seluruh halaman jika tidak ditemukan
                    if ($crawler->filter($currentTitleSelector)->filter($fbDateSelector)->count() > 0) {
                        $currentDateSelector = $fbDateSelector;
                        $foundDateWithFallback = true;
                        $usedFallback = true;
                        break;
                    } elseif ($crawler->filter($fbDateSelector)->count() > 0) {
                        $currentDateSelector = $fbDateSelector;
                        $foundDateWithFallback = true;
                        $usedFallback = true;
                        break;
                    }
                }
                // Jika tidak ada fallback yang ditemukan, date selector akan tetap kosong atau nilai sebelumnya
            }

            // Logika untuk mencoba selector link (jika primary link selector kosong atau tidak ditemukan)
            if (empty($currentLinkSelector) || ($crawler->filter($currentTitleSelector)->filter($currentLinkSelector)->count() === 0 && $crawler->filter($currentLinkSelector)->count() === 0)) {
                $foundLinkWithFallback = false;
                foreach ($fallbackSelectors['link'] as $fbLinkSelector) {
                    // Prioritaskan mencari di dalam konteks node judul, lalu di seluruh halaman jika tidak ditemukan
                    if ($crawler->filter($currentTitleSelector)->filter($fbLinkSelector)->count() > 0) {
                        $currentLinkSelector = $fbLinkSelector;
                        $foundLinkWithFallback = true;
                        $usedFallback = true;
                        break;
                    } elseif ($crawler->filter($fbLinkSelector)->count() > 0) {
                        $currentLinkSelector = $fbLinkSelector;
                        $foundLinkWithFallback = true;
                        $usedFallback = true;
                        break;
                    }
                }
                // Jika tidak ada fallback yang ditemukan, link selector akan tetap kosong atau nilai sebelumnya
            }

            // Hanya lanjutkan parsing jika ada judul yang ditemukan (baik utama atau fallback)
            if (!empty($currentTitleSelector) && $crawler->filter($currentTitleSelector)->count() > 0) {
                 $crawler->filter($currentTitleSelector)->each(function ($node) use (&$sampleArticles, $url, $currentDateSelector, $currentLinkSelector) {
                    if (count($sampleArticles) >= 3) { // Batasi hanya 3 sampel untuk efisiensi
                        return;
                    }

                    $title = trim($node->text());
                    $link = null;
                    $date = null;

                    // Logika pengambilan link (disesuaikan dari metode crawl dan menggunakan $currentLinkSelector)
                    if (!empty($currentLinkSelector) && $node->filter($currentLinkSelector)->count() > 0) {
                        $firstLinkNodeCrawler = $node->filter($currentLinkSelector)->first();
                        $domElement = $firstLinkNodeCrawler->getNode(0);

                        if ($domElement instanceof \DOMElement && $domElement->hasAttribute('href')) {
                            $link = $domElement->getAttribute('href');
                        } else {
                            $linkObject = $firstLinkNodeCrawler->link();
                            if ($linkObject) {
                                $link = $linkObject->getUri();
                            }
                        }
                    } elseif ($node->getNode(0) instanceof \DOMElement && $node->getNode(0)->nodeName === 'a' && $node->getNode(0)->hasAttribute('href')) {
                        $link = $node->getNode(0)->getAttribute('href');
                    } else {
                        $readMoreNode = $node->closest('div, p, article, li')->filter('a[href*="berita"], a[href*="artikel"], a[href*="read"], a.btn-primary')->first();
                        if ($readMoreNode->count() > 0) {
                            $readMoreDomElement = $readMoreNode->getNode(0);
                            if ($readMoreDomElement instanceof \DOMElement && $readMoreDomElement->hasAttribute('href')) {
                                $link = $readMoreDomElement->getAttribute('href');
                            }
                        }
                    }

                    // Pastikan link absolut
                    if ($link && strpos($link, 'http') !== 0) {
                        $link = rtrim($url, '/') . '/' . ltrim($link, '/');
                    }

                    // Logika pengambilan tanggal (disesuaikan dari metode crawl dan menggunakan $currentDateSelector)
                    $dateCandidateNode = null;
                    if (!empty($currentDateSelector) && $node->filter($currentDateSelector)->count() > 0) {
                        $dateCandidateNode = $node->filter($currentDateSelector)->first();
                    } else {
                        // Coba cari di elemen terdekat dari node judul jika tidak ditemukan langsung
                        $dateCandidateNode = $node->closest('li, div, article, p, span')->filter($currentDateSelector)->first();
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

                    // Verifikasi bahwa link berasal dari domain yang sama atau subdomain
                    $parsedSourceUrl = parse_url($url, PHP_URL_HOST);
                    $parsedArticleLinkHost = $link ? parse_url($link, PHP_URL_HOST) : null;

                    if (filter_var($link, FILTER_VALIDATE_URL) && $title && $parsedSourceUrl === $parsedArticleLinkHost) {
                        $sampleArticles[] = [
                            'title' => $title,
                            'link' => $link,
                            'date' => $date,
                        ];
                    }
                });
            }


            if (empty($sampleArticles) && empty($errorMessage)) {
                $errorMessage = "Tidak ada artikel yang ditemukan dengan kombinasi selector yang diberikan. Pastikan selector dan URL sudah benar.";
            }

        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            Log::error("Could not connect to " . $fullCrawlUrl . ": " . $e->getMessage());
            $errorMessage = "Gagal terhubung ke URL. Pastikan URL benar dan situs aktif. Error: " . $e->getMessage();
        } catch (\Exception $e) {
            Log::error("Error testing selector on " . $fullCrawlUrl . ": " . $e->getMessage());
            $errorMessage = "Terjadi kesalahan saat menguji selector. Error: " . $e->getMessage();
        }

        if (!empty($errorMessage)) {
            return response()->json(['success' => false, 'message' => $errorMessage], 400); // 400 Bad Request untuk error validasi/logic
        } else {
            $message = $usedFallback ? "Selector utama tidak menemukan hasil, menggunakan fallback selector. " : "";
            $message .= "Ditemukan " . count($sampleArticles) . " artikel sampel.";
            return response()->json([
                'success' => true,
                'message' => $message, // Tambahkan pesan informasi penggunaan fallback
                'articles' => $sampleArticles
            ]);
        }
    }


    /**
     * Perform web crawling on active monitoring sources and display results.
     * Stores new articles and updates existing ones to prevent duplication.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function crawl(Request $request)
    {
        // 1. Ambil semua situs yang aktif
        $sources = MonitoringSource::where('is_active', true)->get();

        // 2. Jika tidak ada situs aktif, kembali dengan pesan info
        if ($sources->isEmpty()) {
            return redirect()->route('monitoring.sources.index')
                             ->with('info', 'Tidak ada situs monitoring yang aktif untuk di-crawl.');
        }

        // 3. Loop setiap situs dan kirim tugasnya ke queue
        foreach ($sources as $source) {
            CrawlSourceJob::dispatch($source);
        }
        
        // 4. Kembali ke halaman index dengan pesan sukses
        return redirect()->route('monitoring.sources.index')
                         ->with('success', 'Proses crawling untuk ' . $sources->count() . ' situs aktif telah dimulai di latar belakang.');
    }

    /**
     * Display a listing of crawled articles.
     *
     * @return \Illuminate\View\View
     */
    public function listArticles()
    {
        $articles = CrawledArticle::with('source') // Eager load relasi source untuk mendapatkan nama situs
                                ->orderBy('published_date', 'desc') // Urutkan berdasarkan tanggal publikasi terbaru
                                ->paginate(15); // Tambahkan paginasi

        return view('articles.index', compact('articles'));
    }

    public function showDashboard()
    {
        // Total jumlah situs monitoring
        $totalSources = MonitoringSource::count();
        // Jumlah situs aktif
        $activeSources = MonitoringSource::where('is_active', true)->count();
        // Jumlah situs nonaktif
        $inactiveSources = $totalSources - $activeSources;

        // Total artikel yang di-crawl
        $totalArticles = CrawledArticle::count();

        // Jumlah artikel baru hari ini (diasumsikan 'crawled_at' hari ini)
        $newArticlesToday = CrawledArticle::whereDate('crawled_at', Carbon::today())->count();
        // Jumlah artikel baru 7 hari terakhir
        $newArticlesLast7Days = CrawledArticle::where('crawled_at', '>=', Carbon::now()->subDays(7))->count();

        // Data terakhir crawling per situs (opsional, bisa ditampilkan di dashboard atau halaman lain)
        // Ambil beberapa situs terakhir yang di-crawl untuk ringkasan
        $latestCrawls = MonitoringSource::orderBy('last_crawled_at', 'desc')->take(5)->get();


        return view('dashboard', compact(
            'totalSources',
            'activeSources',
            'inactiveSources',
            'totalArticles',
            'newArticlesToday',
            'newArticlesLast7Days',
            'latestCrawls'
        ));
    }
}