<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonitoringSource extends Model
{
    use HasFactory;

    // Tambahkan ini agar kolom bisa diisi secara massal
    protected $fillable = [
        'name',
        'url',
        'crawl_url',
        'selector_title',
        'selector_date',
        'selector_link',
        'last_crawled_at',
        'is_active',
    ];

    protected $casts = [
        'last_crawled_at' => 'datetime', // TAMBAHKAN BARIS INI
    ];
}