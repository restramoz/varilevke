<?php

namespace App\Services\Ai;

use App\Models\CatalogItem;
use App\Models\City;
use App\Models\Kecamatan;

/**
 * Immutable request for a generation run. Draft-only: nothing here touches
 * the database; persistence happens only through the admin create/edit forms.
 */
final class GenerationRequest
{
    public function __construct(
        public readonly string $topic,
        public readonly ?CatalogItem $catalogItem = null,
        public readonly ?City $city = null,
        public readonly ?Kecamatan $kecamatan = null,
        public readonly ?int $wordCount = null,
        public readonly string $language = 'id',
    ) {
        if (trim((string) $this->topic) === '') {
            throw new GenerationException('Topic is required.');
        }

        if ($this->wordCount !== null && ($this->wordCount < 8000 || $this->wordCount > 10000)) {
            throw new GenerationException('Word count must be between 8000 and 10000.');
        }
    }

    public function targetWords(): int
    {
        return $this->wordCount ?? 8000;
    }

    /**
     * Regional context that may be written about — never invented.
     */
    public function regionLabel(): ?string
    {
        if ($this->kecamatan !== null) {
            $city = $this->kecamatan->city;

            return $city !== null
                ? "{$this->kecamatan->name}, {$city->label()}, {$city->province}"
                : $this->kecamatan->name;
        }

        if ($this->city !== null) {
            return "{$this->city->label()}, {$this->city->province}";
        }

        return null;
    }
}
