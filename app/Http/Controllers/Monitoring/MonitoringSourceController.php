<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use App\Models\MonitoringSource;
use App\Models\SelectorPreset;
use App\Models\CrawledArticle;
use App\Models\Region;
use App\Jobs\CrawlSourceJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log; // Kita masih butuh Log
use App\Services\CrawlerService; // [BARU] Impor service kita

class MonitoringSourceController extends Controller
{
    /**
     * Display a listing of the monitoring sources and a link to add new.
     */
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
            ->orderBy('name', 'asc')
            ->get();
        
        $uncategorizedSources = MonitoringSource::whereNull('region_id')->orderBy('name', 'asc')->get();
        
        return view('sources.index', compact('provinces', 'uncategorizedSources'));
    }

    /**
     * Show the form for creating a new monitoring source.
     */
    public function create()
    {
        $presets = SelectorPreset::all();
        $provinces = Region::where('type', 'Provinsi')->orderBy('name')->get();
        $kabkotas = Region::where('type', 'Kabupaten/Kota')->orderBy('name')->get();
        
        return view('sources.create', compact('presets', 'provinces', 'kabkotas'));
    }

    /**
     * Store a newly created monitoring source in storage (from create form).
     */
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

    /**
     * Show the form for editing the specified monitoring source.
     */
    public function edit(MonitoringSource $source)
    {
        $presets = SelectorPreset::all();
        $provinces = Region::where('type', 'Provinsi')->orderBy('name')->get();
        $kabkotas = Region::where('type', 'Kabupaten/Kota')->orderBy('name')->get();

        return view('sources.edit', compact('source', 'presets', 'provinces', 'kabkotas'));
    }

    /**
     * Update the specified monitoring source in storage.
     */
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

    /**
     * Remove the specified monitoring source from storage.
     */
    public function destroy(MonitoringSource $source)
    {
        $source->delete();
        return redirect()->route('monitoring.sources.index')
                         ->with('success', 'Situs monitoring berhasil dihapus!');
    }

    /**
     * [REFAKTOR] Test given selectors by using the centralized CrawlerService.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Services\CrawlerService $crawlerService
     * @return \Illuminate\Http\JsonResponse
     */
    public function testSelector(Request $request, CrawlerService $crawlerService)
    {
        // [REFAKTOR] Hapus validasi 'ends_with:.go.id' untuk konsistensi
        $validatedData = $request->validate([
            'url' => 'required|url:http,https',
            'crawl_url' => 'nullable|string',
            'selector_title' => 'required|string',
            'selector_date' => 'nullable|string',
            'selector_link' => 'nullable|string',
        ]);

        try {
            // Panggil service untuk melakukan pekerjaan berat
            $sampleArticles = $crawlerService->parseArticles(
                $validatedData['url'],
                $validatedData['crawl_url'] ?? '/',
                $validatedData['selector_title'],
                $validatedData['selector_date'],
                $validatedData['selector_link']
            );
            
            // Batasi hasil hanya untuk 3 sampel di frontend
            $sampleArticles = array_slice($sampleArticles, 0, 3);

            return response()->json([
                'success' => true,
                'message' => "Berhasil! Ditemukan " . count($sampleArticles) . " artikel sampel.",
                'articles' => $sampleArticles
            ]);

        } catch (\Exception $e) {
            // Tangkap semua jenis exception dari CrawlerService
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422); // 422 Unprocessable Entity
        }
    }


    /**
     * Perform web crawling on active monitoring sources and display results.
     */
    public function crawl(Request $request)
    {
        $sources = MonitoringSource::where('is_active', true)->get();

        if ($sources->isEmpty()) {
            return redirect()->route('monitoring.sources.index')
                             ->with('info', 'Tidak ada situs monitoring yang aktif untuk di-crawl.');
        }

        foreach ($sources as $source) {
            CrawlSourceJob::dispatch($source);
        }
        
        return redirect()->route('monitoring.sources.index')
                         ->with('success', 'Proses crawling untuk ' . $sources->count() . ' situs aktif telah dimulai di latar belakang.');
    }

    /**
     * Display a listing of crawled articles.
     */
    public function listArticles()
    {
        $articles = CrawledArticle::with('source')
                                ->orderBy('published_date', 'desc')
                                ->paginate(15);

        return view('articles.index', compact('articles'));
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

        return view('dashboard', compact(
            'totalSources', 'activeSources', 'inactiveSources', 'totalArticles',
            'newArticlesToday', 'newArticlesLast7Days', 'latestCrawls',
            'crawlSuccessRate', 'problematicSources'
        ));
    }
}