<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB; // Impor DB
use App\Models\SuggestionSelector; // Impor Model

class SuggestionSelectorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Kosongkan tabel terlebih dahulu untuk menghindari duplikasi
        DB::table('suggestion_selectors')->truncate();

        // Daftar selector untuk Judul
        $titleSelectors = [
            // WordPress & Umum (Prioritas Tinggi)
            ['selector' => 'h1.entry-title a', 'priority' => 10],
            ['selector' => 'h2.entry-title a', 'priority' => 10],
            ['selector' => '.entry-title a', 'priority' => 9],
            ['selector' => 'h1.post-title a', 'priority' => 10],
            ['selector' => 'h2.post-title a', 'priority' => 9],
            ['selector' => '.post-title a', 'priority' => 8],
            ['selector' => 'h1.page-title', 'priority' => 8],
            ['selector' => 'h1 a', 'priority' => 7],
            ['selector' => 'h2 a', 'priority' => 6],
            ['selector' => 'h3 a', 'priority' => 5],
            
            // Struktur Artikel Umum
            ['selector' => '.article-title a', 'priority' => 8],
            ['selector' => '.news-title a', 'priority' => 8],
            ['selector' => '.entry-header h2 a', 'priority' => 9],
            ['selector' => 'header.entry-header h1', 'priority' => 9],
            ['selector' => '.td-module-thumb a', 'priority' => 7],
            ['selector' => '.td-block-span6 h3 a', 'priority' => 7],
            ['selector' => 'a.post-link', 'priority' => 6],
            
            // Lain-lain
            ['selector' => '.media-heading a', 'priority' => 5],
        ];

        // Daftar selector untuk Tanggal
        $dateSelectors = [
            ['selector' => 'time[datetime]', 'priority' => 10],
            ['selector' => 'meta[property="article:published_time"]', 'priority' => 10],
            ['selector' => '.published', 'priority' => 9],
            ['selector' => '.post-date', 'priority' => 9],
            ['selector' => '.entry-date', 'priority' => 8],
            ['selector' => '.meta-date', 'priority' => 8],
            ['selector' => 'span.date', 'priority' => 7],
            ['selector' => 'div.date', 'priority' => 7],
            ['selector' => '.entry-meta .posted-on time', 'priority' => 6],
            ['selector' => 'span.posted-on', 'priority' => 6],
            ['selector' => 'p.post-meta', 'priority' => 5],
        ];

        // Masukkan data judul ke database
        foreach ($titleSelectors as $item) {
            SuggestionSelector::create([
                'type' => 'title',
                'selector' => $item['selector'],
                'priority' => $item['priority'],
            ]);
        }
        
        // Masukkan data tanggal ke database
        foreach ($dateSelectors as $item) {
            SuggestionSelector::create([
                'type' => 'date',
                'selector' => $item['selector'],
                'priority' => $item['priority'],
            ]);
        }
    }
}