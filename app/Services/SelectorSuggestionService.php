<?php

namespace App\Services;

use App\Models\SuggestionSelector; // [BARU] Impor model kita
use Illuminate\Support\Facades\Cache; // [BARU] Impor Cache untuk performa

/**
 * Class SelectorSuggestionService
 * [MODIFIKASI] Menyediakan "kamus" selector CSS dinamis dari database.
 */
class SelectorSuggestionService
{
    /**
     * [MODIFIKASI] Mengambil daftar selector judul dari database.
     * Hasilnya di-cache untuk mengurangi query ke database.
     *
     * @return array
     */
    public function getTitleSelectors(): array
    {
        // Cache data selama 60 menit untuk performa.
        // Jika ada perubahan di database, cache akan diperbarui setelah 60 menit.
        return Cache::remember('suggestion_selectors_title', 3600, function () {
            return SuggestionSelector::where('type', 'title')
                ->orderBy('priority', 'desc') // Urutkan berdasarkan prioritas
                ->pluck('selector') // Ambil hanya kolom 'selector'
                ->all(); // Konversi menjadi array biasa
        });
    }

    /**
     * [MODIFIKASI] Mengambil daftar selector tanggal dari database.
     *
     * @return array
     */
    public function getDateSelectors(): array
    {
        return Cache::remember('suggestion_selectors_date', 3600, function () {
            return SuggestionSelector::where('type', 'date')
                ->orderBy('priority', 'desc')
                ->pluck('selector')
                ->all();
        });
    }
}