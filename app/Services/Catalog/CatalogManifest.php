<?php

namespace App\Services\Catalog;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

/**
 * Immutable, validated view of docs/source/catalog-manifest.json.
 * Construction fails loudly when the manifest is unusable — the importer then
 * writes nothing.
 */
final class CatalogManifest
{
    public const CATEGORIES = ['pelatihan', 'jasa'];

    /**
     * @param  array<int, array{name: string, category: string, source_order: int, review_required: bool, review_note: ?string}>  $items
     * @param  array{pelatihan: int, jasa: int, total: int}  $counts
     */
    private function __construct(
        private readonly array $items,
        private readonly array $counts,
    ) {}

    public static function fromFile(string $path): self
    {
        if (! is_readable($path)) {
            throw new CatalogManifestException("Catalog manifest is not readable: {$path}");
        }

        $raw = file_get_contents($path);
        $data = json_decode((string) $raw, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($data)) {
            $reason = json_last_error_msg();
            throw new CatalogManifestException("Catalog manifest is not valid JSON: {$reason}");
        }

        if (! is_array($data['counts'] ?? null) || ! is_array($data['items'] ?? null)) {
            throw new CatalogManifestException('Catalog manifest must contain "counts" and "items" arrays.');
        }

        $declaredCounts = $data['counts'];
        $declaredItems = collect($data['items']);

        foreach (self::CATEGORIES as $category) {
            if (! is_int($declaredCounts[$category] ?? null)) {
                throw new CatalogManifestException("Catalog manifest counts[\"{$category}\"] must be an integer.");
            }
        }

        if (! is_int($declaredCounts['total'] ?? null)) {
            throw new CatalogManifestException('Catalog manifest counts["total"] must be an integer.');
        }

        $validated = Validator::make(
            ['items' => $declaredItems->all()],
            self::itemRules(),
        );

        if ($validated->fails()) {
            throw new CatalogManifestException('Catalog manifest items failed validation: '.implode('; ', $validated->errors()->all()));
        }

        $items = $declaredItems
            ->map(fn (array $item): array => [
                'name' => $item['name'],
                'category' => $item['category'],
                'source_order' => (int) $item['source_order'],
                'review_required' => (bool) $item['review_required'],
                'review_note' => $item['review_note'] ?? null,
            ])
            ->values();

        $actualCounts = [
            'pelatihan' => $items->where('category', 'pelatihan')->count(),
            'jasa' => $items->where('category', 'jasa')->count(),
            'total' => $items->count(),
        ];

        foreach (self::CATEGORIES as $category) {
            if ($actualCounts[$category] !== $declaredCounts[$category]) {
                throw new CatalogManifestException(
                    "Declared counts[\"{$category}\"]={$declaredCounts[$category]} but counted {$actualCounts[$category]} items."
                );
            }
        }

        if ($actualCounts['total'] !== $declaredCounts['total']) {
            throw new CatalogManifestException(
                "Declared counts[\"total\"]={$declaredCounts['total']} but counted {$actualCounts['total']} items."
            );
        }

        // Source ordering within each category must be unique.
        if ($items->groupBy('category')->contains(
            fn (Collection $rows, string $category): bool => $rows->pluck('source_order')->unique()->count() !== $rows->count()
        )) {
            throw new CatalogManifestException('source_order values must be unique within each category.');
        }

        return new self($items->all(), $actualCounts);
    }

    /**
     * @return array<string, mixed>
     */
    private static function itemRules(): array
    {
        return [
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string|min:2|max:255',
            'items.*.category' => ['required', 'in:'.implode(',', self::CATEGORIES)],
            'items.*.source_order' => 'required|integer|min:1',
            'items.*.review_required' => 'required|boolean',
            'items.*.review_note' => 'nullable|string|max:1000',
        ];
    }

    /**
     * @return Collection<int, array{name: string, category: string, source_order: int, review_required: bool, review_note: ?string}>
     */
    public function items(): Collection
    {
        return collect($this->items);
    }

    /** @return array{pelatihan: int, jasa: int, total: int} */
    public function counts(): array
    {
        return $this->counts;
    }
}
