<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Editorial content for one catalog item. The structured field names live in
 * App\Services\Catalog\Content\FieldBlueprint; unverified facts are simply
 * absent from `fields` rather than invented.
 */
class CatalogContent extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_REVIEW = 'review';

    public const STATUS_PUBLISHED = 'published';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_REVIEW,
        self::STATUS_PUBLISHED,
    ];

    protected $fillable = ['catalog_item_id', 'status', 'fields', 'generated_at', 'edited_at'];

    protected $casts = [
        'fields' => 'array',
        'generated_at' => 'datetime',
        'edited_at' => 'datetime',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class, 'catalog_item_id');
    }

    /**
     * Single string field with length-bounded fallback.
     */
    public function text(string $key): string
    {
        return (string) ($this->fields[$key] ?? '');
    }

    /**
     * @return list<string>
     */
    public function list(string $key): array
    {
        $value = $this->fields[$key] ?? null;

        if (is_array($value)) {
            return array_values(array_filter($value, fn ($entry): bool => is_string($entry) && $entry !== ''));
        }

        if (is_string($value) && $value !== '') {
            return [$value];
        }

        return [];
    }

    /**
     * @return list<array{question: string, answer: string}>
     */
    public function faqs(): array
    {
        $faqs = $this->listFaqs();

        return array_values(array_filter($faqs, fn (array $faq): bool => ($faq['question'] ?? '') !== '' && ($faq['answer'] ?? '') !== '' && is_string($faq['question']) && is_string($faq['answer'])));
    }

    /**
     * @return list<mixed>
     */
    private function listFaqs(): array
    {
        $value = $this->fields['faqs'] ?? [];

        return is_array($value) ? array_values($value) : [];
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }
}
