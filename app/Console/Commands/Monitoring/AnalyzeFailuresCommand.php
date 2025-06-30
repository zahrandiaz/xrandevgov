<?php

namespace App\Console\Commands\Monitoring;

use Illuminate\Console\Command;
use App\Models\Region;
use App\Models\MonitoringSource; // Impor model
use App\Services\CrawlerService;
use App\Services\SelectorSuggestionService;
use App\Services\ExperimentalSuggestionService;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpClient\Exception\TransportException;

class AnalyzeFailuresCommand extends Command
{
    protected $signature = 'monitoring:analyze-failures 
                            {--id= : ID spesifik dari situs yang akan dianalisis}
                            {--province= : Nama provinsi yang akan dianalisis}
                            {--engine=v3 : Versi engine AI yang akan diuji (v3 atau v4)}';

    protected $description = 'Menganalisis kegagalan saran selector untuk semua situs di sebuah provinsi atau untuk satu situs spesifik';

    protected $crawlerService;
    protected $stableAIService;
    protected $experimentalAIService;

    public function __construct(
        CrawlerService $crawlerService,
        SelectorSuggestionService $stableAIService,
        ExperimentalSuggestionService $experimentalAIService
    ) {
        parent::__construct();
        $this->crawlerService = $crawlerService;
        $this->stableAIService = $stableAIService;
        $this->experimentalAIService = $experimentalAIService;
    }

    public function handle()
    {
        $sourceId = $this->option('id');
        $provinceName = $this->option('province');
        $engineVersion = $this->option('engine');

        if (empty($sourceId) && empty($provinceName)) {
            $this->error('Opsi --id atau --province wajib diisi.');
            return 1;
        }

        if (!in_array($engineVersion, ['v3', 'v4'])) {
            $this->error('Opsi --engine hanya boleh v3 atau v4.');
            return 1;
        }
        
        $sources = collect();
        if ($sourceId) {
            $source = \App\Models\MonitoringSource::find($sourceId);
            if (!$source) {
                $this->error("Situs dengan ID {$sourceId} tidak ditemukan.");
                return 1;
            }
            $sources->push($source);
            $this->info("Memulai analisis untuk situs: {$source->name} menggunakan Engine AI {$engineVersion}");
        } else {
            $province = Region::where('name', $provinceName)->where('type', 'Provinsi')->first();
            if (!$province) {
                $this->error("Provinsi '{$provinceName}' tidak ditemukan di database.");
                return 1;
            }
            $this->info("Memulai analisis kegagalan untuk provinsi: {$provinceName} menggunakan Engine AI {$engineVersion}");
            $sourceIds = $province->monitoringSources()->pluck('id')
                ->merge($province->children()->with('monitoringSources')->get()->flatMap(fn($kabkota) => $kabkota->monitoringSources->pluck('id')));
            $sources = \App\Models\MonitoringSource::findMany($sourceIds);
        }

        if ($sources->isEmpty()) {
            $this->warn("Tidak ada situs monitoring yang ditemukan untuk dianalisis.");
            return 0;
        }

        $progressBar = $this->output->createProgressBar($sources->count());
        $progressBar->start();

        $failures = [];
        $successes = 0;

        foreach ($sources as $source) {
            if ($this->getOutput()->isVerbose()) {
                $this->newLine();
                $this->line("<fg=cyan>Processing: ({$source->id}) {$source->name}</>");
            }

            $result = $this->analyzeSingleSource($source, $engineVersion);

            if (!$result['status']) {
                $failures[] = [
                    'name' => $source->name,
                    'reason' => $result['reason']
                ];
            } else {
                $successes++;
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("Analisis Selesai.");
        $this->line("========================================");
        $this->line("Total Situs Dianalisis: " . $sources->count());
        $this->info("Sukses: {$successes}");
        $this->error("Gagal: " . count($failures));
        $this->line("========================================");

        if (!empty($failures)) {
            $this->warn("Daftar Situs yang Gagal:");
            $this->table(
                ['Nama Situs', 'Alasan Kegagalan'],
                $failures
            );
        } elseif ($successes > 0) {
            $this->info("Luar biasa! Semua situs yang diuji berhasil dianalisis.");
        }

        return 0;
    }

    private function analyzeSingleSource(MonitoringSource $source, $engineVersion): array
    {
        try {
            $aiService = ($engineVersion === 'v4') ? $this->experimentalAIService : $this->stableAIService;
            
            $suggestionResult = $aiService->suggest($source->url, $source->crawl_url);
            if (!$suggestionResult['success']) {
                return ['status' => false, 'reason' => 'Suggestion Failure: AI tidak bisa menemukan pola awal.'];
            }

            $titleSelector = $suggestionResult['title_selectors'][0] ?? null;
            $dateSelector = $suggestionResult['date_selectors'][0] ?? null;

            if (empty($titleSelector)) {
                return ['status' => false, 'reason' => 'Incomplete Suggestion: Selector Judul tidak ditemukan.'];
            }
            
            // [MODIFIKASI v1.29.0] Logika validasi tanggal yang baru
            if (empty($dateSelector)) {
                // Jika selector tanggal kosong, periksa apakah situs ini memang ditandai tidak punya tanggal
                if (!$source->expects_date) {
                    // Jika memang tidak punya tanggal, ini bukan kegagalan. Anggap sukses.
                    return ['status' => true, 'reason' => 'Success (Mode Tanpa Tanggal)'];
                } else {
                    // Jika seharusnya punya tanggal tapi AI tidak menemukan, ini kegagalan.
                    return ['status' => false, 'reason' => "Gagal temukan selector tanggal. (Verifikasi manual & gunakan mode 'tanpa tanggal' jika perlu)"];
                }
            }
            
            $articles = $this->crawlerService->parseArticles($source->url, $source->crawl_url, $titleSelector, $dateSelector, null, 3);

            if (empty($articles)) {
                return ['status' => false, 'reason' => 'Validation Failure: Selector tidak menghasilkan artikel.'];
            }
            
            $hasValidDate = collect($articles)->contains(fn ($article) => !empty($article['date']));

            if (!$hasValidDate) {
                return ['status' => false, 'reason' => 'Validation Failure: Semua artikel sampel tidak memiliki tanggal (Tanpa Tanggal).'];
            }

        } catch (TransportException $e) {
            return ['status' => false, 'reason' => 'HTTP Timeout: Situs tidak merespons dalam 20 detik.'];
        } catch (\Exception $e) {
            return ['status' => false, 'reason' => 'Validation Failure: ' . $e->getMessage()];
        }
        
        return ['status' => true, 'reason' => 'Success'];
    }
}