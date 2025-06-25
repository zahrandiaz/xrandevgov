<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class CrawledArticle extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'monitoring_source_id',
        'title',
        'url',
        'published_date',
        'crawled_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'published_date' => 'datetime',
        'crawled_at' => 'datetime',
    ];

    /**
     * Get the monitoring source that owns the crawled article.
     */
    public function source()
    {
        return $this->belongsTo(MonitoringSource::class, 'monitoring_source_id');
    }
}