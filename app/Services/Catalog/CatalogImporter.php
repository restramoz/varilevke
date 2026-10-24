<?php

namespace App\Services\Catalog;

use App\Models\CatalogItem;
use App\Support\Slug;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Idempotent, non-destructive importer for the catalog. It never truncates or
 * deletes anything: rows are inserted when missing, verified when already
 * present, and every mismatch is reported instead of overwritten.
 */
final class CatalogImporter
{
    public function __construct(private readonly CatalogManifest $manifest) {}

    /**
     * @throws CatalogImportException when the manifest disagrees with the
     *                                configured expectations or existing data conflicts with the source.
     */
    public function import(): ImportReport
    {
        $this->assertExpectedCounts();

        $entries = $this->entries();

        $conflicts = [];
        $inserted = [];
        $unchanged = 0;
        $updated = 0;

        DB::transaction(function () use ($entries, &$conflicts, &$inserted, &$unchanged, &$updated): void {
            foreach ($entries as $entry) {
                ['item' => $item, 'slug' => $slug] = $entry;
                $key = "source_order {$item['source_order']} ({$item['category']}, \"{$item['name']}\")";

                $row = CatalogItem::query()
                    ->where('category', $item['category'])
                    ->where('source_order', $item['source_order'])
                    ->first();

                if ($row === null) {
                    $slugOwner = CatalogItem::query()->where('slug', $slug)->first();

                    if ($slugOwner !== null) {
                        $conflicts[] = "{$key}: slug \"{$slug}\" is already used by id {$slugOwner->id} ({$slugOwner->name})";

                        continue;
                    }

                    CatalogItem::query()->create($item + ['slug' => $slug]);
                    $inserted[] = $item['source_order'];

                    continue;
                }

                $reason = match (true) {
                    $row->name !== $item['name'] => 'name differs',
                    $row->category !== $item['category'] => 'category differs',
                    // The stored slug is written once and then owned by the
                    // row; it must never be regenerated (self-collision).
                    in_array($row->slug, ['', '0'], true) => 'slug is blank',
                    default => null,
                };

                if ($reason !== null) {
                    $conflicts[] = "{$key}: {$reason} (existing id {$row->id}, name \"".Str::limit($row->name, 60)."\", category \"{$row->category}\", slug \"{$row->slug}\")";

                    continue;
                }

                // Only reviewer metadata may drift; the source-defined values win.
                if ((int) $row->review_required !== (int) $item['review_required']
                    || (string) $row->review_note !== (string) $item['review_note']) {
                    $row->forceFill($item)->save();
                    $updated++;
                } else {
                    $unchanged++;
                }
            }

            // Nothing may be dropped behind the caller's back: rolling the
            // whole run back when conflicts were found.
            if ($conflicts !== []) {
                throw new CatalogImportException('Import aborted: conflicts with existing catalog data.', $conflicts);
            }
        });

        return new ImportReport($inserted, $unchanged, $updated);
    }

    private function assertExpectedCounts(): void
    {
        foreach (config('catalog.expected_counts') as $key => $expected) {
            $actual = $this->manifest->counts()[$key] ?? null;

            if ($actual !== $expected) {
                throw new CatalogImportException(
                    "Manifest count \"{$key}\" is {$actual}, expected {$expected}. Refusing to import."
                );
            }
        }
    }

    /**
     * Manifest items paired with their deterministic slug, in source order
     * (pelatihan items first, jasa items after, exactly as the file lists
     * them). In-manifest slug collisions resolve by appending the source
     * order, which is stable across runs.
     *
     * @return list<array{item: array{name: string, category: string, source_order: int, review_required: bool, review_note: ?string}, slug: string}>
     */
    private function entries(): array
    {
        $entries = [];
        $used = CatalogItem::query()->pluck('slug');
        $takenInRun = [];

        foreach ($this->manifest->items() as $item) {
            $exists = function (string $candidate) use ($used, $takenInRun): bool {
                return $used->contains($candidate) || in_array($candidate, $takenInRun, true);
            };

            $slug = Slug::unique($item['name'], $item['category'].'-'.$item['source_order'], $exists);
            $takenInRun[] = $slug;
            $entries[] = ['item' => $item, 'slug' => $slug];
        }

        return $entries;
    }
}
