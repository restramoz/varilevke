<?php

namespace App\Models;

use Database\Factories\CatalogItemFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CatalogItem extends Model
{
    /** @use HasFactory<CatalogItemFactory> */
    use HasFactory;

    public const CATEGORY_PELATIHAN = 'pelatihan';

    public const CATEGORY_JASA = 'jasa';

    protected $fillable = ['name', 'slug', 'category', 'source_order', 'review_required', 'review_note'];

    protected $casts = [
        'review_required' => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where('name', 'like', '%'.addcslashes($term, '%_\\').'%');
    }

    public function articles()
    {
        return $this->hasMany(Article::class);
    }

    public function content()
    {
        return $this->hasOne(CatalogContent::class, 'catalog_item_id');
    }
}
