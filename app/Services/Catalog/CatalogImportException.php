<?php

namespace App\Services\Catalog;

use RuntimeException;

/**
 * Thrown when the importer refuses to write. The message contains every
 * conflict found — user data is never silently overwritten.
 */
class CatalogImportException extends RuntimeException
{
    /** @param list<string> $conflicts */
    public function __construct(
        string $message,
        public readonly array $conflicts = [],
    ) {
        parent::__construct($message);
    }
}
