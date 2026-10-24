<?php

return [
    // Region seeding is bounded to these provinces so the site never grows a
    // thin doorway-page farm; add more via REGION_SEED_PROVINCES.
    'seed_provinces' => array_filter(array_map(
        'trim',
        explode(',', (string) env('REGION_SEED_PROVINCES', 'Jawa Timur'))
    )),
    // Public set of BPS wilayah data (source: trainingahlik3umum master file).
    'data_path' => env('REGION_DATA_PATH', base_path('database/data/master-wilayah-bps.json')),
];
