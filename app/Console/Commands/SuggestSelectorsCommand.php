<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MonitoringSource;
use App\Services\CrawlerService;
use App\Services\SelectorSuggestionService;
use Illuminate\Support\Facades\Log;

class SuggestSelectorsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitoring:suggest-selectors {source_id : ID dari situs monitoring yang akan dianalisis}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menganalisis sebuah situs dan memberikan saran selector CSS yang mungkin berfungsi';

    /**
     * The service for crawling.
     * @var \App\Services\CrawlerService
     */
    protected $crawlerService;

    /**
     * The service for selector suggestions.
     * @var \App\Services\SelectorSuggestionService
     */
    protected $suggestionService;

    /**
     * Create a new command instance.
     *
     * @param \App\Services\CrawlerService $crawlerService
     * @param \App\Services\SelectorSuggestionService $suggestionService
     */
    public function __construct(CrawlerService $crawlerService, SelectorSuggestionService $suggestionService)
    {
        parent::__construct();
        $this->crawlerService = $crawlerService;
        $this->suggestionService = $suggestionService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $sourceId = $this->argument('source_id');
        $source = MonitoringSource::find($sourceId);

        if (!$source) {
            $this->error("Situs monitoring dengan ID {$sourceId} tidak ditemukan.");
            return 1; // Return non-zero for error
        }

        $this->info("Menganalisis situs: {$source->name} ({$source->url})");
        $this->line('--------------------------------------------------');

        $successfulTitleSelectors = [];

        // 1. Cari Selector Judul yang Berhasil
        $this->info("Mencari selector JUDUL yang valid...");
        $titleSelectors = $this->suggestionService->getTitleSelectors();
        $progressBar = $this->output->createProgressBar(count($titleSelectors));
        $progressBar->start();

        foreach ($titleSelectors as $selector) {
            try {
                // Kita hanya butuh tahu apakah ia menemukan sesuatu, tidak perlu hasilnya
                $this->crawlerService->parseArticles($source->url, $source->crawl_url, $selector, null, null);
                $successfulTitleSelectors[] = $selector; // Jika tidak ada exception, berarti berhasil
            } catch (\Exception $e) {
                // Abaikan exception, berarti selector tidak berfungsi
                Log::channel('daily')->debug("SuggestSelector: Selector '{$selector}' gagal untuk source ID {$source->id}.");
            }
            $progressBar->advance();
        }
        $progressBar->finish();
        $this->newLine(2);

        // 2. Tampilkan Hasil Selector Judul
        if (empty($successfulTitleSelectors)) {
            $this->warn("Tidak ditemukan selector judul yang berfungsi dari kamus kami.");
        } else {
            $this->info("[HASIL] Ditemukan selector judul yang mungkin berfungsi:");
            foreach ($successfulTitleSelectors as $selector) {
                $this->line("  - {$selector}");
            }
        }
        
        $this->line('--------------------------------------------------');
        $this->info("Silakan salin dan coba selector di atas pada form edit situs.");

        return 0;
    }
}