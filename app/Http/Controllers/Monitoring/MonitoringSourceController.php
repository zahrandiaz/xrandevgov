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

class MonitoringSourceController extends Controller
{
    // ... (metode index hingga suggestSelectorsAjax tetap sama persis) ...
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
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url:http,https|unique:monitoring_sources,url',
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
            'crawl_url' => 'nullable|string', 'selector_title' => 'required|string',
            'selector_date' => 'nullable|string', 'selector_link' => 'nullable|string',
        ]);
        
        if (!preg_match("~^(?:f|ht)tps?://~i", $validatedData['url'])) { $validatedData['url'] = "https://" . $validatedData['url']; }
        if (empty($validatedData['crawl_url'])) { $validatedData['crawl_url'] = '/'; }

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
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url:http,https|unique:monitoring_sources,url,'.$source->id,
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
            'crawl_url' => 'nullable|string', 'selector_title' => 'required|string',
            'selector_date' => 'nullable|string', 'selector_link' => 'nullable|string',
        ]);
        
        if (!preg_match("~^(?:f|ht)tps?://~i", $validatedData['url'])) { $validatedData['url'] = "https://" . $validatedData['url']; }
        if (empty($validatedData['crawl_url'])) { $validatedData['crawl_url'] = '/'; }
        
        $validatedData['is_active'] = $request->has('is_active');
        $source->update($validatedData);

        return redirect()->route('monitoring.sources.index')->with('success', 'Situs monitoring berhasil diperbarui!');
    }

    public function destroy(MonitoringSource $source)
    {
        $source->delete();
        return redirect()->route('monitoring.sources.index')
                         ->with('success', 'Situs monitoring berhasil dihapus!');
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

    /**
     * [MODIFIKASI] Handle AJAX request to suggest selectors for a given URL for both title and date.
     */
    public function suggestSelectorsAjax(Request $request, CrawlerService $crawlerService, SelectorSuggestionService $suggestionService)
    {
        $validatedData = $request->validate([
            'url' => 'required|url:http,https',
            'crawl_url' => 'nullable|string',
        ]);

        // 1. Cari Selector Judul
        $successfulTitleSelectors = [];
        $titleSelectors = $suggestionService->getTitleSelectors();
        foreach ($titleSelectors as $selector) {
            try {
                $crawlerService->parseArticles($validatedData['url'], $validatedData['crawl_url'] ?? '/', $selector, null, null);
                $successfulTitleSelectors[] = $selector;
            } catch (\Exception $e) {
                continue;
            }
        }

        if (empty($successfulTitleSelectors)) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada selector judul yang cocok ditemukan dari kamus kami.'
            ], 404);
        }

        // 2. [BARU] Jika Judul ditemukan, lanjutkan cari Selector Tanggal
        $bestTitleSelector = $successfulTitleSelectors[0]; // Gunakan selector judul terbaik sebagai acuan
        $successfulDateSelectors = [];
        $dateSelectors = $suggestionService->getDateSelectors();
        
        foreach ($dateSelectors as $selector) {
            try {
                $articles = $crawlerService->parseArticles($validatedData['url'], $validatedData['crawl_url'] ?? '/', $bestTitleSelector, $selector, null);
                // Cek apakah setidaknya satu artikel memiliki tanggal yang berhasil diparsing
                $dateFound = false;
                foreach($articles as $article) {
                    if (!empty($article['date'])) {
                        $dateFound = true;
                        break;
                    }
                }
                if ($dateFound) {
                    $successfulDateSelectors[] = $selector;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Analisis selesai.',
            'title_selectors' => $successfulTitleSelectors,
            'date_selectors' => $successfulDateSelectors // Kirim juga hasil selector tanggal
        ]);
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
        
        // [MODIFIKASI] Gunakan sistem notifikasi toast
        $message = 'Proses crawling untuk ' . $sources->count() . ' situs aktif telah dimulai.';
        return redirect()->route('monitoring.sources.index')
                         ->with('notify', ['info', $message]);
    }

    public function crawlSingle(MonitoringSource $source)
    {
        CrawlSourceJob::dispatch($source);
        
        // [MODIFIKASI] Gunakan sistem notifikasi toast
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