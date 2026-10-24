<?php

namespace App\Services\Catalog\Content;

use App\Models\CatalogContent;
use App\Models\CatalogItem;

/**
 * Idempotent content writer: creates missing content rows, never deletes,
 * and never overwrites human-edited content unless asked explicitly.
 */
final class ContentGenerator
{
    /**
     * @return array{action: string, content: CatalogContent} action ∈ created|skipped|regenerated
     */
    public function generateFor(CatalogItem $item, ?string $status = null, bool $refresh = false): array
    {
        $existing = CatalogContent::query()->where('catalog_item_id', $item->id)->first();

        if ($existing !== null && $existing->edited_at !== null && ! $refresh) {
            return ['action' => 'skipped-human-edited', 'content' => $existing];
        }

        if ($existing !== null && ! $refresh) {
            return ['action' => 'skipped-existing', 'content' => $existing];
        }

        $fields = $item->category === CatalogItem::CATEGORY_PELATIHAN
            ? TrainingContentBuilder::build($item)
            : ServiceContentBuilder::build($item);

        $content = CatalogContent::query()->updateOrCreate(
            ['catalog_item_id' => $item->id],
            $existing !== null
                ? ['fields' => $fields, 'generated_at' => now()] + ($status !== null ? ['status' => $status] : [])
                : [
                    'catalog_item_id' => $item->id,
                    'fields' => $fields,
                    'status' => $status ?? CatalogContent::STATUS_REVIEW,
                    'generated_at' => now(),
                ]
        );

        return ['action' => $existing !== null ? 'regenerated' : 'created', 'content' => $content];
    }
}
