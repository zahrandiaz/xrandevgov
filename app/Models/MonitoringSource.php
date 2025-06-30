<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonitoringSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'region_id',
        'tipe_instansi',
        'name',
        'url',
        'crawl_url',
        'selector_title',
        'selector_date',
        'selector_link',
        'last_crawled_at',
        'is_active',
        'expects_date', // [BARU v1.29.0] Tambahkan ini
        'last_crawl_status',
        'consecutive_failures',
        'suggestion_engine',
        'site_status',
    ];

    // [BARU v1.29.0] Tambahkan ini untuk memastikan nilai boolean
    protected $casts = [
        'last_crawled_at' => 'datetime',
        'is_active' => 'boolean',
        'expects_date' => 'boolean',
    ];

    public function region()
    {
        return $this->belongsTo(Region::class);
    }
}