<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'type',
        'parent_id',
    ];

    /**
     * Mendefinisikan relasi "parent" (milik siapa).
     * Sebuah Kabupaten/Kota adalah milik sebuah Provinsi.
     */
    public function parent()
    {
        return $this->belongsTo(Region::class, 'parent_id');
    }

    /**
     * Mendefinisikan relasi "children" (memiliki apa).
     * Sebuah Provinsi memiliki banyak Kabupaten/Kota.
     */
    public function children()
    {
        return $this->hasMany(Region::class, 'parent_id');
    }

    /**
     * [BARU] Relasi ke MonitoringSource
     * Sebuah wilayah bisa memiliki banyak sumber monitoring.
     */
    public function monitoringSources()
    {
        return $this->hasMany(MonitoringSource::class);
    }
}