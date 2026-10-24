<?php

namespace App\Models;

use Database\Factories\KecamatanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kecamatan extends Model
{
    /** @use HasFactory<KecamatanFactory> */
    use HasFactory;

    protected $fillable = ['city_id', 'name', 'slug', 'bps_code'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function articles()
    {
        return $this->hasMany(Article::class);
    }
}
