<?php

namespace App\Services\Catalog\Content;

/**
 * The single contract for structured editorial content: which fields exist per
 * content type, their human labels, types and length bounds. Builders fill,
 * the admin form edits, templates render and the coverage command audits — all
 * from this one map.
 */
final class FieldBlueprint
{
    /**
     * Field type constants.
     */
    public const TYPE_TEXT = 'text';

    public const TYPE_LIST = 'list';

    public const TYPE_FAQS = 'faqs';

    /**
     * Field keys the detail page and coverage report understand.
     */
    public const KEYS = [
        'intro', 'purpose', 'audience', 'objectives', 'curriculum',
        'prerequisites', 'scope', 'deliverables', 'documents', 'process',
        'dependencies', 'format', 'assessment', 'faqs',
    ];

    /**
     * Definitions per item category (training names vs service names).
     * "always" = builder produces it from the name; anything else ("if
     * confirmed") is optional and left null when not verifiable.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function definitions(string $category): array
    {
        return $category === 'pelatihan' ? self::training() : self::service();
    }

    /**
     * @return array<string, array{label: string, type: string, max: int, required: bool, help: string}>
     */
    public static function training(): array
    {
        return array_merge([
            'intro' => [
                'label' => 'Pengantar program',
                'type' => self::TYPE_TEXT,
                'max' => 2000,
                'required' => true,
                'help' => 'Paragraf pengantar yang menjelaskan bidang kompetensi program ini.',
            ],
            'purpose' => [
                'label' => 'Tujuan utama',
                'type' => self::TYPE_TEXT,
                'max' => 1000,
                'required' => true,
                'help' => 'Satu-dua kalimat: untuk apa program ini diikuti.',
            ],
            'audience' => [
                'label' => 'Sasaran peserta',
                'type' => self::TYPE_TEXT,
                'max' => 1200,
                'required' => true,
                'help' => 'Peran/peserta yang paling relevan mengikuti program.',
            ],
            'objectives' => [
                'label' => 'Tujuan pembelajaran',
                'type' => self::TYPE_LIST,
                'max' => 400,
                'required' => true,
                'help' => 'Satu butir tiap baris: kompetensi yang diharapkan.',
            ],
            'curriculum' => [
                'label' => 'Materi pokok (kerangka)',
                'type' => self::TYPE_LIST,
                'max' => 400,
                'required' => true,
                'help' => 'Satu butir tiap baris: bidang bahasan program, bukan rincian silabus resmi.',
            ],
            'prerequisites' => [
                'label' => 'Prasyarat umum (bila ada)',
                'type' => self::TYPE_TEXT,
                'max' => 1200,
                'required' => false,
                'help' => 'Kosongkan bila tidak dinyatakan di dokumen sumber; jangan mengarang.',
            ],
        ], self::shared());
    }

    /**
     * @return array<string, array{label: string, type: string, max: int, required: bool, help: string}>
     */
    public static function service(): array
    {
        return array_merge([
            'intro' => [
                'label' => 'Pengantar layanan',
                'type' => self::TYPE_TEXT,
                'max' => 2000,
                'required' => true,
                'help' => 'Paragraf pengantar: apa yang dikerjakan layanan ini.',
            ],
            'purpose' => [
                'label' => 'Tujuan utama',
                'type' => self::TYPE_TEXT,
                'max' => 1000,
                'required' => true,
                'help' => 'Satu-dua kalimat: kebutuhan apa yang dilayani.',
            ],
            'audience' => [
                'label' => 'Klien potensial & penggunaan',
                'type' => self::TYPE_TEXT,
                'max' => 1200,
                'required' => true,
                'help' => 'Siapa yang biasanya membutuhkan layanan ini.',
            ],
            'scope' => [
                'label' => 'Ruang lingkup kerja (umum)',
                'type' => self::TYPE_LIST,
                'max' => 400,
                'required' => true,
                'help' => 'Satu butir tiap baris: hal yang umum masuk lingkup.',
            ],
            'deliverables' => [
                'label' => 'Hasil kerja yang biasanya diterima klien',
                'type' => self::TYPE_LIST,
                'max' => 400,
                'required' => true,
                'help' => 'Hati-hati: gunakan rumusan "umumnya/ditetapkan bersama" bila tidak terkonfirmasi.',
            ],
            'dependencies' => [
                'label' => 'Dependensi & hal yang perlu dikonfirmasi',
                'type' => self::TYPE_TEXT,
                'max' => 1200,
                'required' => false,
                'help' => 'Kondisi/keputusan yang menentukan lingkup akhir; wajib jujur bila ada.',
            ],
        ], self::shared());
    }

    /**
     * @return array<string, array{label: string, type: string, max: int, required: bool, help: string}>
     */
    private static function shared(): array
    {
        return [
            'documents' => [
                'label' => 'Persiapan yang diminta dari Anda',
                'type' => self::TYPE_LIST,
                'max' => 300,
                'required' => true,
                'help' => 'Satu butir tiap baris: dokumen/informasi yang umum perlu disiapkan.',
            ],
            'process' => [
                'label' => 'Proses hubungi-kerja sama',
                'type' => self::TYPE_LIST,
                'max' => 300,
                'required' => true,
                'help' => 'Satu butir tiap baris: langkah dari konsultasi awal hingga pelaksanaan.',
            ],
            'format' => [
                'label' => 'Format pelaksanaan (hanya bila terkonfirmasi)',
                'type' => self::TYPE_TEXT,
                'max' => 500,
                'required' => false,
                'help' => 'Kosong bila tidak terkonfirmasi dari dokumen sumber — jangan mengarang.',
            ],
            'assessment' => [
                'label' => 'Evaluasi/keterangan sertifikat (hanya bila terkonfirmasi)',
                'type' => self::TYPE_TEXT,
                'max' => 500,
                'required' => false,
                'help' => 'Kosong bila tidak terkonfirmasi dari dokumen sumber — jangan mengarang.',
            ],
            'faqs' => [
                'label' => 'FAQ spesifik',
                'type' => self::TYPE_FAQS,
                'max' => 400,
                'required' => true,
                'help' => 'Dua s/d empat baris dalam format "Pertanyaan | Jawaban".',
            ],
        ];
    }

    /**
     * Keys a detail page renders for a category, ordered.
     *
     * @return list<string>
     */
    public static function displayOrder(string $category): array
    {
        return array_keys(self::definitions($category));
    }
}
