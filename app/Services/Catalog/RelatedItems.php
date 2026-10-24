<?php

namespace App\Services\Catalog;

use App\Models\CatalogItem;
use Illuminate\Support\Collection;

/**
 * Meaningful "related items": same category, ranked by shared name tokens
 * (case-insensitive, digits preserved), then by source order. Cheap scoring
 * over at most 272 rows; no need for full-text search infrastructure.
 */
final class RelatedItems
{
    private const STOPWORDS = [
        'jasa', 'serta', 'dan', 'atau', 'untuk', 'kelas', 'tingkat', 'dengan', 'pada',
        'sertifikasi', 'program', 'training', 'operator', 'penanggung', 'jawab',
    ];

    /**
     * @return Collection<int, CatalogItem>
     */
    public function for(CatalogItem $item, int $take = 5): Collection
    {
        $tokens = $this->tokens($item->name);

        return CatalogItem::query()
            ->category($item->category)
            ->whereKeyNot($item->getKey())
            ->orderBy('source_order')
            ->get()
            ->map(fn (CatalogItem $candidate): array => [
                'item' => $candidate,
                'score' => count(array_intersect($tokens, $this->tokens($candidate->name))),
            ])
            ->filter(fn (array $row): bool => $row['score'] >= 1)
            ->sortByDesc('score')
            ->values()
            ->take($take)
            ->map(fn (array $row): CatalogItem => $row['item']);
    }

    /**
     * @return list<string>
     */
    private function tokens(string $name): array
    {
        $raw = preg_split('/[^A-Za-z0-9]+/', mb_strtolower($name)) ?: [];

        return array_values(array_unique(array_filter($raw, function (string $token): bool {
            if ($token === '') {
                return false;
            }

            if (ctype_digit($token)) {
                return true;
            }

            return mb_strlen($token) >= 3 && ! in_array($token, self::STOPWORDS, true);
        })));
    }
}
