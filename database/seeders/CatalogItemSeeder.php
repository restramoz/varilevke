<?php

namespace Database\Seeders;

use App\Services\Catalog\CatalogImporter;
use App\Services\Catalog\CatalogManifest;
use Illuminate\Database\Seeder;

class CatalogItemSeeder extends Seeder
{
    public function run(): void
    {
        // Seeding is exactly the same deterministic import as catalog:import —
        // one code path, validated counts, idempotent.
        $manifest = CatalogManifest::fromFile((string) config('catalog.manifest_path'));
        (new CatalogImporter($manifest))->import();
    }
}
