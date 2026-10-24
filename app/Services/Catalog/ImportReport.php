<?php

namespace App\Services\Catalog;

final class ImportReport
{
    /** @param list<int> $inserted */
    public function __construct(
        public readonly array $inserted,
        public readonly int $unchanged,
        public readonly int $updated,
    ) {}

    public function summary(): string
    {
        $insertedCount = count($this->inserted);

        return "catalog import OK: inserted={$insertedCount}, unchanged={$this->unchanged}, updated={$this->updated}";
    }
}
