<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonitoringSource extends Model
{
    use HasFactory;

    // Tambahkan ini agar kolom bisa diisi secara massal
    protected $fillable = [
        'region_id', // Tambahkan ini
        'name',
        'url',
        'crawl_url',
        'selector_title',
        'selector_date',
        'selector_link',
        'last_crawled_at',
        'is_active',
        'last_crawl_status',    // [BARU] Tambahkan ini
        'consecutive_failures', // [BARU] Tambahkan ini
    ];

    protected $casts = [
        'last_crawled_at' => 'datetime', // TAMBAHKAN BARIS INI
    ];

    /**
     * Get the region that the monitoring source belongs to.
     */
    public function region()
    {
        return $this->belongsTo(Region::class);
    }
}