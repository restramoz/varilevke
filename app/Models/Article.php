<?php

namespace App\Models;

use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    protected $fillable = [
        'title', 'slug', 'excerpt', 'content', 'reading_time', 'word_count', 'status',
        'featured_image', 'image_brief', 'faqs', 'seo_title', 'meta_description',
        'catalog_item_id', 'city_id', 'kecamatan_id', 'published_at',
    ];

    protected $casts = [
        'faqs' => 'array',
        'image_brief' => 'array',
        'published_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function catalogItem()
    {
        return $this->belongsTo(CatalogItem::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function kecamatan()
    {
        return $this->belongsTo(Kecamatan::class);
    }

    /**
     * Fallback chain for meta description: explicit meta, then excerpt.
     */
    public function metaDescription(): string
    {
        return (string) ($this->meta_description ?: mb_substr(trim(strip_tags($this->excerpt ?: $this->title)), 0, 157));
    }

    public function refreshComputedFields(): void
    {
        $this->word_count = str_word_count(strip_tags((string) $this->content));
        $this->reading_time = (int) max(1, ceil($this->word_count / 200));
    }
}
