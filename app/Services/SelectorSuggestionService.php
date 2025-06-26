<?php

namespace App\Services;

/**
 * Class SelectorSuggestionService
 * Menyimpan dan menyediakan "kamus" selector CSS yang umum digunakan.
 */
class SelectorSuggestionService
{
    /**
     * Mengembalikan daftar (array) selector yang paling umum digunakan untuk judul artikel.
     * Daftar diurutkan dari yang paling umum/kuat hingga yang kurang umum.
     *
     * @return array
     */
    public function getTitleSelectors(): array
    {
        return [
            // WordPress & Umum
            'h1.entry-title a',
            'h2.entry-title a',
            '.entry-title a',
            'h1.post-title a',
            'h2.post-title a',
            '.post-title a',
            'h1.page-title', // Untuk judul yang tidak memiliki link
            'h1 a',
            'h2 a',
            'h3 a',

            // Struktur Artikel Umum
            '.article-title a',
            '.news-title a',
            '.entry-header h2 a',
            'header.entry-header h1',
            '.td-module-thumb a', // Tema Newspaper/Newsmag
            '.td-block-span6 h3 a',
            'a.post-link',
            
            // Lain-lain
            '.media-heading a',
        ];
    }

    /**
     * Mengembalikan daftar (array) selector yang paling umum digunakan untuk tanggal publikasi.
     *
     * @return array
     */
    public function getDateSelectors(): array
    {
        return [
            // Tag & Atribut Standar
            'time[datetime]',
            'meta[property="article:published_time"]', // Atribut 'content'

            // Kelas WordPress & Umum
            '.published',
            '.post-date',
            '.entry-date',
            '.meta-date',
            'span.date',
            'div.date',

            // Struktur Umum
            '.entry-meta .posted-on time',
            '.info-meta li', // Kadang berisi tanggal
            'span.posted-on',
            'p.post-meta',
        ];
    }
}