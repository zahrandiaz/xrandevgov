<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use App\Models\MonitoringSource;
use App\Models\SelectorPreset;
use App\Models\CrawledArticle;
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
        $sources = MonitoringSource::orderBy('name')->get();
        return view('sources.index', compact('sources'));
    }

    /**
     * Show the form for creating a new monitoring source.
     */
    public function create() // Modifikasi metode ini
    {
        $presets = SelectorPreset::all(); // Ambil semua preset
        return view('sources.create', compact('presets')); // Teruskan ke view
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
    public function edit(MonitoringSource $source) // Modifikasi metode ini
    {
        $presets = SelectorPreset::all(); // Ambil semua preset
        return view('sources.edit', compact('source', 'presets')); // Teruskan ke view
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
        $httpClient = HttpClient::create(['verify_peer' => false, 'verify_host' => false]);
        $client = new HttpBrowser($httpClient);

        $articles = []; // Artikel yang akan ditampilkan di view (baru dicrawl)
        $sources = MonitoringSource::where('is_active', true)->orderBy('name')->get();
        $errorMessages = []; // Tambahkan array ini untuk menyimpan pesan error
        $newlyCrawledCount = 0; // Menghitung artikel baru yang disimpan

        foreach ($sources as $source) {
            try {
                // Update last_crawled_at sebelum crawling untuk menandai proses dimulai
                $source->update(['last_crawled_at' => now()]);

                $fullCrawlUrl = $source->crawl_url;
                if (strpos($fullCrawlUrl, 'http') !== 0) {
                    $fullCrawlUrl = rtrim($source->url, '/') . '/' . ltrim($fullCrawlUrl, '/');
                }

                $crawler = $client->request('GET', $fullCrawlUrl);
                $sourceArticlesRaw = []; // Artikel mentah dari satu sumber

                $titleSelector = $source->selector_title ?: 'h1 a, h2 a, h3 a, .post-title a, .entry-title a';
                $dateSelector = $source->selector_date ?: 'time, .date, .post-date, .entry-date';
                $linkSelector = $source->selector_link ?: 'a';

                $crawler->filter($titleSelector)->each(function ($node) use (&$sourceArticlesRaw, $source, $dateSelector, $linkSelector, &$errorMessages) {
                    try {
                        $title = trim($node->text());
                        $link = null;
                        $date = null; // Ubah menjadi null untuk memungkinkan parsing tanggal yang gagal

                        // Logika pengambilan link (disesuaikan dari metode crawl)
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

                        // Pastikan link absolut
                        if ($link && strpos($link, 'http') !== 0) {
                            $link = rtrim($source->url, '/') . '/' . ltrim($link, '/');
                        }

                        // Logika pengambilan tanggal (disesuaikan dari metode crawl)
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
                                    $date = date('Y-m-d H:i:s', $parsedDate); // Simpan sebagai datetime
                                } else {
                                    if ($dateDomElement->hasAttribute('datetime')) {
                                        $date = date('Y-m-d H:i:s', strtotime($dateDomElement->getAttribute('datetime')));
                                    }
                                }
                            }
                        }

                        // Verifikasi bahwa link berasal dari domain yang sama atau subdomain
                        $parsedSourceUrl = parse_url($source->url, PHP_URL_HOST);
                        $parsedArticleLinkHost = $link ? parse_url($link, PHP_URL_HOST) : null;

                        if (filter_var($link, FILTER_VALIDATE_URL) && $title && $parsedSourceUrl === $parsedArticleLinkHost) {
                            $sourceArticlesRaw[] = [
                                'title' => $title,
                                'link' => $link,
                                'date' => $date, // Bisa null jika tidak ditemukan/parse
                                'source_name' => $source->name, // Untuk ditampilkan di view sementara
                                'monitoring_source_id' => $source->id, // Untuk disimpan ke DB
                            ];
                        } else {
                            Log::warning('Invalid article data from ' . $source->url . ': Title: ' . $title . ' Link: ' . $link);
                            $errorMessages[] = "Artikel tidak lengkap atau tidak valid dari {$source->name} ({$source->url}). Judul: \"{$title}\" Link: \"{$link}\"";
                        }
                    } catch (\Exception $e) {
                        Log::warning('Error parsing single article from ' . $source->url . ': ' . $e->getMessage());
                        $errorMessages[] = "Gagal memproses satu artikel dari {$source->name} ({$source->url}). Error: " . $e->getMessage();
                    }
                });

                // --- LOGIKA PENYIMPANAN DAN PENGATURAN DUPLIKASI ARTIKEL ---
                foreach ($sourceArticlesRaw as $articleData) {
                    // Cek duplikasi berdasarkan URL
                    // Jika URL adalah unique key di DB, maka updateOrCreate akan mengelola duplikasi dengan baik
                    $crawledArticle = CrawledArticle::updateOrCreate(
                        ['url' => $articleData['link']], // Kriteria untuk menemukan record yang sudah ada
                        [
                            'monitoring_source_id' => $articleData['monitoring_source_id'],
                            'title' => $articleData['title'],
                            'published_date' => $articleData['date'], // Bisa null
                            'crawled_at' => now(), // Update timestamp crawled_at setiap kali di-crawl
                        ]
                    );

                    // Jika artikel baru dibuat (bukan diperbarui), tambahkan ke daftar yang akan ditampilkan
                    if ($crawledArticle->wasRecentlyCreated) {
                        $articles[] = [ // Hanya tambahkan yang baru ke $articles untuk ditampilkan
                            'title' => $articleData['title'],
                            'link' => $articleData['link'],
                            'date' => $articleData['date'] ? \Carbon\Carbon::parse($articleData['date'])->format('Y-m-d') : 'Tidak Diketahui', // Format untuk tampilan
                            'source' => $articleData['source_name'],
                        ];
                        $newlyCrawledCount++; // Hitung artikel baru
                    }
                }
                // --- AKHIR LOGIKA PENYIMPANAN DAN PENGATURAN DUPLIKASI ARTIKEL ---

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

        // Flash pesan sukses jika ada artikel baru
        if ($newlyCrawledCount > 0) {
            session()->flash('success', "Berhasil mengambil dan menyimpan {$newlyCrawledCount} artikel baru.");
        } elseif (empty($errorMessages)) {
            // Jika tidak ada artikel baru, dan tidak ada error, berarti tidak ada yang baru ditemukan
            session()->flash('info', "Tidak ada artikel baru yang ditemukan dari situs aktif yang terdaftar.");
        }


        // Redirect ke index, bisa menampilkan artikel yang baru di-crawl jika ingin
        // Atau cukup biarkan view index memuat dari database nanti
        // Untuk sekarang, kita tetap lewatkan artikel yang baru di-crawl untuk tampilan segera
        return view('sources.index', compact('articles', 'sources'))
                    ->with('crawling_done', true); // Tetap gunakan ini untuk menunjukkan proses selesai
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