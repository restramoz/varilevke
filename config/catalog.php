<?php

return [
    // Deterministic source of truth for the catalog importer.
    'manifest_path' => env('CATALOG_MANIFEST_PATH', base_path('docs/source/catalog-manifest.json')),

    // Importer aborts unless the manifest matches these exact counts.
    'expected_counts' => [
        'pelatihan' => 227,
        'jasa' => 45,
        'total' => 272,
    ],
];
