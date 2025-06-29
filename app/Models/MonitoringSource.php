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
        'last_crawl_status',
        'consecutive_failures',
        'suggestion_engine', // [BARU v1.26.0]
        'site_status',       // [BARU v1.26.0]
    ];

    protected $casts = [
        'last_crawled_at' => 'datetime',
    ];

    public function region()
    {
        return $this->belongsTo(Region::class);
    }
}