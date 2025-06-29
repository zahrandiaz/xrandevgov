<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tracker extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'keywords',
        'description',
        'status',
    ];

    /**
     * Mengubah string keywords menjadi array saat diakses.
     *
     * @param  string  $value
     * @return array
     */
    public function getKeywordsAttribute($value)
    {
        return array_filter(array_map('trim', explode(',', $value)));
    }

    /**
     * Mengubah array keywords menjadi string saat disimpan.
     *
     * @param  array  $value
     * @return void
     */
    public function setKeywordsAttribute($value)
    {
        $this->attributes['keywords'] = is_array($value) ? implode(',', $value) : $value;
    }
}