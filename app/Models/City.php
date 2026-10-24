<?php

namespace App\Models;

use Database\Factories\CityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    /** @use HasFactory<CityFactory> */
    use HasFactory;

    protected $fillable = ['name', 'slug', 'province', 'classification', 'bps_code', 'kecamatan_count'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function kecamatans(): HasMany
    {
        return $this->hasMany(Kecamatan::class);
    }

    public function articles()
    {
        return $this->hasMany(Article::class);
    }

    public function label(): string
    {
        return $this->classification && $this->name
            ? $this->classification.' '.$this->name
            : $this->name;
    }
}
