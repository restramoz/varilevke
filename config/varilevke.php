<?php

// Admin-editable settings registry: keys, labels, and validation rules.
// New settings must be declared here to stay validated.

return [
    'site_name' => [
        'label' => 'Nama situs',
        'rules' => ['required', 'string', 'max:60'],
        'default' => 'Varilevke',
    ],
    'site_tagline' => [
        'label' => 'Tagline',
        'rules' => ['required', 'string', 'max:160'],
        'default' => 'Pelatihan dan jasa K3 untuk kebutuhan industri',
    ],
    'whatsapp_numbers' => [
        'label' => 'Nomor WhatsApp ditampilkan (pilih dari config/contacts.php)',
        'rules' => ['nullable', 'string', 'max:200'],
        'default' => null,
    ],
    'article_default_word_count' => [
        'label' => 'Jumlah kata default artikel (8000–10000)',
        'rules' => ['nullable', 'integer', 'min:8000', 'max:10000'],
        'default' => '8000',
    ],
];
