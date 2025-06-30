<?php

namespace App\Http\Controllers;
use App\Models\Tracker; // TAMBAHKAN INI di bagian atas
use Illuminate\Http\Request;
use App\Models\Region; // TAMBAHKAN INI
use App\Models\CrawledArticle; // TAMBAHKAN INI
use Illuminate\Support\Facades\DB; // TAMBAHKAN INI

class TrackerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $trackers = Tracker::orderBy('created_at', 'desc')->get();
        return view('trackers.index', compact('trackers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('trackers.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // [MODIFIKASI v1.27.1] Menambahkan validasi untuk search_mode
        $validatedData = $request->validate([
            'title' => 'required|string|max:255|unique:trackers,title',
            'keywords' => 'required|string|max:255',
            'search_mode' => 'required|in:OR,AND', // Validasi baru
            'description' => 'nullable|string',
            'status' => 'required|in:Aktif,Arsip',
        ], [
            'title.required' => 'Judul pantauan wajib diisi.',
            'title.unique' => 'Judul pantauan ini sudah ada, silakan gunakan judul lain.',
            'keywords.required' => 'Kata kunci wajib diisi.',
        ]);

        $tracker = Tracker::create($validatedData);

        log_activity("Pantauan baru '{$tracker->title}' telah dibuat.", 'success', 'tracker-management');

        return redirect()->route('trackers.index')
                         ->with('notify', ['success', 'Pantauan baru berhasil dibuat!']);
    }

    /**
     * Display the specified resource.
     */
    public function show(Tracker $tracker)
    {
        $provinces = Region::where('type', 'Provinsi')
            ->with(['children', 'monitoringSources.region', 'children.monitoringSources.region'])
            ->orderBy('name', 'asc')
            ->get();

        $allSourceIds = $provinces->flatMap(function ($province) {
            $sourceIds = $province->monitoringSources->pluck('id');
            $childSourceIds = $province->children->flatMap(fn($kabkota) => $kabkota->monitoringSources->pluck('id'));
            return $sourceIds->merge($childSourceIds);
        })->unique();

        // [REWORK v1.27.1] Logika pencarian diperbarui untuk mode AND/OR dan Whole Word
        $relevantArticlesQuery = CrawledArticle::whereIn('monitoring_source_id', $allSourceIds)
            ->where(function ($query) use ($tracker) {
                if ($tracker->search_mode === 'AND') {
                    // Mode AND: Semua kata kunci harus ada
                    foreach ($tracker->keywords as $keyword) {
                        // Menggunakan REGEXP untuk pencarian "whole word"
                        $query->where('title', 'REGEXP', '[[:<:]]' . preg_quote($keyword) . '[[:>:]]');
                    }
                } else {
                    // Mode OR: Cukup salah satu kata kunci ada
                    foreach ($tracker->keywords as $keyword) {
                        $query->orWhere('title', 'REGEXP', '[[:<:]]' . preg_quote($keyword) . '[[:>:]]');
                    }
                }
            })
            ->select('monitoring_source_id', 'url', 'title')
            ->groupBy('monitoring_source_id', 'url', 'title')
            ->orderBy('published_date', 'desc');

        $foundArticles = $relevantArticlesQuery->get()->keyBy('monitoring_source_id');
        
        $totalSources = $allSourceIds->count();
        $foundCount = $foundArticles->count();
        $percentage = ($totalSources > 0) ? round(($foundCount / $totalSources) * 100, 2) : 0;

        $stats = [
            'total' => $totalSources,
            'found' => $foundCount,
            'percentage' => $percentage,
        ];

        return view('trackers.show', compact('tracker', 'provinces', 'foundArticles', 'stats'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Tracker $tracker)
    {
        return view('trackers.edit', compact('tracker'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Tracker $tracker)
    {
        // [MODIFIKASI v1.27.1] Menambahkan validasi untuk search_mode
        $validatedData = $request->validate([
            'title' => 'required|string|max:255|unique:trackers,title,' . $tracker->id,
            'keywords' => 'required|string|max:255',
            'search_mode' => 'required|in:OR,AND', // Validasi baru
            'description' => 'nullable|string',
            'status' => 'required|in:Aktif,Arsip',
        ]);

        $oldTitle = $tracker->title;
        $tracker->update($validatedData);

        log_activity("Pantauan '{$oldTitle}' telah diperbarui menjadi '{$tracker->title}'.", 'info', 'tracker-management');

        return redirect()->route('trackers.index')
                         ->with('notify', ['success', 'Pantauan berhasil diperbarui!']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tracker $tracker)
    {
        $title = $tracker->title;
        $tracker->delete();

        log_activity("Pantauan '{$title}' telah dihapus.", 'warning', 'tracker-management');

        return redirect()->route('trackers.index')
                         ->with('notify', ['warning', 'Pantauan berhasil dihapus.']);
    }
}
