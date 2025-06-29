<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use App\Models\MonitoringSource;
use App\Models\SelectorPreset;
use App\Models\CrawledArticle;
use App\Models\Region;
use App\Models\SystemActivity;
use App\Jobs\CrawlSourceJob;
use Illuminate\Http\Request;
use App\Services\CrawlerService;
use App\Services\SelectorSuggestionService;
use App\Services\ExperimentalSuggestionService; // [BARU v1.22] Impor service baru
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MonitoringSourceController extends Controller
{
    // ... (metode index hingga testSelector tetap sama) ...
    public function index()
    {
        $provinces = Region::where('type', 'Provinsi')
            ->with([
                'monitoringSources' => function ($query) {
                    $query->with('region')->orderBy('name', 'asc');
                },
                'children' => function ($query) {
                    $query->orderBy('name', 'asc');
                },
                'children.monitoringSources' => function ($query) {
                    $query->with('region')->orderBy('name', 'asc');
                }
            ])
            ->withCount(['monitoringSources', 'children as kabkota_count'])
            ->orderBy('name', 'asc')
            ->get();
            
        foreach ($provinces as $province) {
            $childrenSitesCount = MonitoringSource::whereIn('region_id', $province->children->pluck('id'))->count();
            $province->total_sites_count = $province->monitoring_sources_count + $childrenSitesCount;
        }

        $uncategorizedSources = MonitoringSource::whereNull('region_id')->orderBy('name', 'asc')->get();

        return view('sources.index', compact('provinces', 'uncategorizedSources'));
    }

    public function create()
    {
        $presets = SelectorPreset::all();
        $provinces = Region::where('type', 'Provinsi')->orderBy('name')->get();
        $kabkotas = Region::where('type', 'Kabupaten/Kota')->orderBy('name')->get();
        
        return view('sources.create', compact('presets', 'provinces', 'kabkotas'));
    }

    public function store(Request $request)
    {
        $validatedData = $this->validateSourceData($request);
        
        if (!preg_match("~^(?:f|ht)tps?://~i", $validatedData['url'])) { $validatedData['url'] = "https://" . $validatedData['url']; }
        if (empty($validatedData['crawl_url'])) { $validatedData['crawl_url'] = '/'; }
        
        $validatedData['is_active'] = $request->has('is_active');

        MonitoringSource::create($validatedData);

        return redirect()->route('monitoring.sources.index')->with('success', 'Situs monitoring berhasil ditambahkan!');
    }

    public function edit(MonitoringSource $source)
    {
        $presets = SelectorPreset::all();
        $provinces = Region::where('type', 'Provinsi')->orderBy('name')->get();
        $kabkotas = Region::where('type', 'Kabupaten/Kota')->orderBy('name')->get();

        return view('sources.edit', compact('source', 'presets', 'provinces', 'kabkotas'));
    }

    public function update(Request $request, MonitoringSource $source)
    {
        $validatedData = $this->validateSourceData($request, $source->id);
        
        if (!preg_match("~^(?:f|ht)tps?://~i", $validatedData['url'])) { $validatedData['url'] = "https://" . $validatedData['url']; }
        if (empty($validatedData['crawl_url'])) { $validatedData['crawl_url'] = '/'; }
        
        $validatedData['is_active'] = $request->has('is_active');
        $source->update($validatedData);

        return redirect()->route('monitoring.sources.index')->with('success', 'Situs monitoring berhasil diperbarui!');
    }
    
    // [BARU v1.26.0] Helper untuk sentralisasi validasi
    private function validateSourceData(Request $request, $sourceId = null)
    {
        $urlRule = 'required|url:http,https';
        if ($sourceId) {
            $urlRule .= '|unique:monitoring_sources,url,' . $sourceId;
        } else {
            $urlRule .= '|unique:monitoring_sources,url';
        }

        return $request->validate([
            'name' => 'required|string|max:255',
            'url' => $urlRule,
            'tipe_instansi' => 'required|in:BKD,BKPSDM',
            'region_id' => ['required', 'exists:regions,id',
                function ($attribute, $value, $fail) use ($request) {
                    $region = Region::find($value);
                    if (!$region) return;
                    if ($request->input('tipe_instansi') == 'BKD' && $region->type !== 'Provinsi') {
                        $fail('Untuk tipe BKD, wilayah yang dipilih harus berupa Provinsi.');
                    }
                    if ($request->input('tipe_instansi') == 'BKPSDM' && $region->type !== 'Kabupaten/Kota') {
                        $fail('Untuk tipe BKPSDM, wilayah yang dipilih harus berupa Kabupaten/Kota.');
                    }
                },
            ],
            'crawl_url' => 'nullable|string',
            'selector_title' => 'required|string',
            'selector_date' => 'nullable|string',
            'selector_link' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'suggestion_engine' => ['nullable', 'string', Rule::in(['Manual', 'Kamus', 'Stabil v3', 'Eksperimental v4'])],
            'site_status' => ['required', 'string', Rule::in(['Aktif', 'URL Tidak Valid', 'Tanpa Halaman Berita', 'Lainnya'])],
        ]);
    }

    public function destroy(MonitoringSource $source)
    {
        $source->delete();
        return redirect()->route('monitoring.sources.index')
                         ->with('success', 'Situs monitoring berhasil dihapus!');
    }
    
    public function suggestSelectorsAjax(
        Request $request,
        SelectorSuggestionService $stableSuggestionService,
        ExperimentalSuggestionService $experimentalSuggestionService
    ) {
        $validatedData = $request->validate([
            'url' => 'required|url:http,https',
            'crawl_url' => 'nullable|string',
            'strategy' => 'required|in:stable,experimental'
        ]);

        $url = $validatedData['url'];
        $crawlUrl = $validatedData['crawl_url'];
        $result = [];

        if ($validatedData['strategy'] === 'experimental') {
            $result = $experimentalSuggestionService->suggest($url, $crawlUrl);
        } else {
            $result = $stableSuggestionService->suggest($url, $crawlUrl);
        }

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Tidak ada selector yang cocok ditemukan.'
            ], 404);
        }

        // [MODIFIKASI v1.26.0] Mengirimkan nama engine yang lebih ramah pengguna
        $engineName = 'Manual';
        if (str_contains($result['method'], 'dictionary')) {
            $engineName = 'Kamus';
        } elseif (str_contains($result['method'], 'v3')) {
            $engineName = 'Stabil v3';
        } elseif (str_contains($result['method'], 'v4')) {
            $engineName = 'Eksperimental v4';
        }

        return response()->json([
            'success' => true,
            'message' => 'Analisis ' . Str::studly($result['method']) . ' selesai.',
            'title_selectors' => $result['title_selectors'],
            'date_selectors' => $result['date_selectors'],
            'engine' => $engineName, // Kirim nama engine ke frontend
        ]);
    }


    public function testSelector(Request $request, CrawlerService $crawlerService)
    {
        $validatedData = $request->validate([
            'url' => 'required|url:http,https',
            'crawl_url' => 'nullable|string',
            'selector_title' => 'required|string',
            'selector_date' => 'nullable|string',
            'selector_link' => 'nullable|string',
        ]);

        try {
            $sampleArticles = $crawlerService->parseArticles(
                $validatedData['url'],
                $validatedData['crawl_url'] ?? '/',
                $validatedData['selector_title'],
                $validatedData['selector_date'],
                $validatedData['selector_link']
            );
            
            $sampleArticles = array_slice($sampleArticles, 0, 3);

            return response()->json([
                'success' => true,
                'message' => "Berhasil! Ditemukan " . count($sampleArticles) . " artikel sampel.",
                'articles' => $sampleArticles
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
    
    public function inspectHtml(Request $request, CrawlerService $crawlerService)
    {
        $validatedData = $request->validate([
            'url' => 'required|url:http,https',
            'crawl_url' => 'nullable|string',
            'selector_title' => 'required|string',
        ]);

        try {
            $crawler = $crawlerService->fetchHtmlAsCrawler($validatedData['url'], $validatedData['crawl_url'] ?? '/');
            $firstTitleNode = $crawler->filter($validatedData['selector_title'])->first();

            if ($firstTitleNode->count() === 0) {
                return response()->json(['success' => false, 'message' => 'Selector judul tidak ditemukan di halaman target.'], 404);
            }

            $block = $firstTitleNode->ancestors()->eq(1) ?? $firstTitleNode->ancestors()->first();
            
            if (!$block || $block->count() === 0) {
                 return response()->json(['success' => false, 'message' => 'Tidak dapat menemukan blok HTML induk dari judul.'], 404);
            }

            return response()->json([
                'success' => true,
                'html' => $block->outerHtml()
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal mengambil HTML: ' . $e->getMessage()], 500);
        }
    }

    public function crawl(Request $request)
    {
        $sources = MonitoringSource::where('is_active', true)->get();

        if ($sources->isEmpty()) {
            return redirect()->route('monitoring.sources.index')
                             ->with('notify', ['warning', 'Tidak ada situs monitoring yang aktif untuk di-crawl.']);
        }

        foreach ($sources as $source) {
            CrawlSourceJob::dispatch($source);
        }
        
        $message = 'Proses crawling untuk ' . $sources->count() . ' situs aktif telah dimulai.';
        return redirect()->route('monitoring.sources.index')
                         ->with('notify', ['info', $message]);
    }

    public function crawlSingle(MonitoringSource $source)
    {
        CrawlSourceJob::dispatch($source);
        
        $message = "Proses crawling untuk situs '{$source->name}' telah dimulai.";
        return redirect()->route('monitoring.sources.index')
                         ->with('notify', ['info', $message]);
    }
    
    public function listArticles(Request $request)
    {
        $query = CrawledArticle::query();
        if ($keyword = $request->input('keyword')) {
            $query->where('title', 'like', "%{$keyword}%");
        }
        if ($startDate = $request->input('start_date')) {
            $query->whereDate('published_date', '>=', $startDate);
        }
        if ($endDate = $request->input('end_date')) {
            $query->whereDate('published_date', '<=', $endDate);
        }
        $query->with('source')->orderBy('published_date', 'desc');
        $articles = $query->paginate(15)->withQueryString();
        return view('articles.index', [
            'articles' => $articles,
            'filters' => $request->only(['keyword', 'start_date', 'end_date'])
        ]);
    }

    public function destroyArticle(CrawledArticle $article)
    {
        $article->delete();
        return redirect()->route('monitoring.articles.index')
                         ->with('success', 'Artikel berhasil dihapus.');
    }

    public function showDashboard()
    {
        $totalSources = MonitoringSource::count();
        $activeSources = MonitoringSource::where('is_active', true)->count();
        $inactiveSources = $totalSources - $activeSources;
        $totalArticles = CrawledArticle::count();
        $newArticlesToday = CrawledArticle::whereDate('crawled_at', now())->count();
        $newArticlesLast7Days = CrawledArticle::where('crawled_at', '>=', now()->subDays(7))->count();
        $latestCrawls = MonitoringSource::orderBy('last_crawled_at', 'desc')->take(5)->get();

        $activeCrawledSources = MonitoringSource::where('is_active', true)->whereNotNull('last_crawl_status');
        $totalCrawled = $activeCrawledSources->count();
        $successfulCrawls = $activeCrawledSources->clone()->where('last_crawl_status', 'success')->count();
        $crawlSuccessRate = ($totalCrawled > 0) ? round(($successfulCrawls / $totalCrawled) * 100) : 100;
        $problematicSources = MonitoringSource::where('consecutive_failures', '>=', 3)
                                              ->orderBy('consecutive_failures', 'desc')
                                              ->get();
        
        $systemActivities = SystemActivity::latest()->take(10)->get();

        return view('dashboard', compact(
            'totalSources', 'activeSources', 'inactiveSources', 'totalArticles',
            'newArticlesToday', 'newArticlesLast7Days', 'latestCrawls',
            'crawlSuccessRate', 'problematicSources', 'systemActivities'
        ));
    }
}