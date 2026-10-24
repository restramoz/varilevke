<?php

namespace App\Services\Catalog\Content;

use App\Models\CatalogItem;

/**
 * Builds structured editorial fields for service items ("jasa"). The exact
 * catalog name stays the anchor; all claims stay cautious: no legal
 * guarantee, authority or approval-rate is asserted, because none of those
 * facts exist in the catalog source.
 */
final class ServiceContentBuilder
{
    /** Match order matters: the first needle that hits decides the family. */
    private const PATTERNS = [
        'technical-cert' => ['slf', 'slo', 'silo', 'sia ', 'sia)', 'riksa uji', 'nidi', 'sertifikat laik fungsi', 'sertifikat laik operasi', 'izin alat'],
        'publication' => ['publikasi jurnal', 'scopus', 'sinta'],
        'mgmt-system' => ['iso', 'haccp', 'gmp', 'fssc', 'bim', 'enms'],
        'audit-finance' => ['audit keuangan'],
        'kajian-assess' => ['kajian', 'studi kelayakan', 'tarif medis'],
        'env-permits' => ['ukl', 'upl', 'amdal', 'pertek', 'rintek', 'uji emisi', 'emisi', 'limbah b3', 'lingkungan'],
        'business-admin' => ['lpse', 'lkpm', 'rups', 'sbu', 'sertifikat standar', 'iujk', 'skk', 'ska', 'skt', 'serkom', 'pkkpr', 'inu', 'migas', 'spda', 'siupkk', 'siujpt', 'izin', 'agen'],
    ];

    private static ?array $profiles = null;

    private static function profiles(): array
    {
        if (self::$profiles !== null) {
            return self::$profiles;
        }

        // s = subject, a = audience, b = blurb, scope, deliver, purpose, deps
        self::$profiles = [
            'technical-cert' => [
                's' => 'kepatuhan teknis bangunan/alat terhadap kelayakan fungsi dan operasi',
                'a' => 'pemilik/pengelola gedung, pabrik, fasilitas umum, dan operator alat yang memerlukan penilaian kelayakan',
                'b' => 'Pendampingan tertib-administrasi dan pengenalan tahapan pemeriksaan yang umum diperlukan institusi yang berwenang.',
                'scope' => [
                    'Penjajakan kebutuhan: jenis fasilitas/alat, dokumen yang sudah dimiliki, dan luaran yang dibutuhkan',
                    'Pemeriksaan kelengkapan dokumen pendukung',
                    'Kerja sama pengkoordinasian pemeriksaan/pengujian dengan pihak terkait  (saat kebutuhannya disepakati)',
                    'Pendampingan pengurusan perpanjangan/ulang pada periode berikutnya',
                ],
                'deliver' => [
                    'Gambaran langkah pengurusan yang disepakati saat konsultasi',
                    'Dokumen hasil pengurusan sesuai skema yang berlaku pada saat pelaksanaan',
                    'Ringkasan status dan catatan lanjutan untuk pemegang dokumen',
                ],
                'purpose' => 'Membantu pemilik fasilitas memenuhi kewajiban kelengkapan dokumen teknis dengan alur yang jelas dan terkendali.',
                'deps' => 'Kecepatan proses bergantung pada kelengkapan dokumen klien, jadwal pemeriksaan pihak ter kait, serta ketentuan yang berlaku saat pelaksanaan. Mohon konfirmasi lingkup akhir lewat WhatsApp.',
            ],
            'env-permits' => [
                's' => 'kepatuhan lingkungan: persyaratan lingkungan, emisi, dan limbah',
                'a' => 'perusahaan industri, pengelola fasilitas, dan instansi yang memerlukan dokumen lingkungan maupun hasil pengukuran',
                'b' => 'Pendampingan penyusunan dokumen, koordinasi pengukuran/pengambilan sampel, dan rangkaian administrasi terkait.',
                'scope' => [
                    'Penjajakan lingkup kebutuhan lingkungan fasilitas Anda',
                    'Pengumpulan data baseline & dokumen pendukung',
                    'Penyusunan draf dokumen / pengkoordinasian pengukuran sesuai skema yang disepakati',
                    'Pendampingan hingga dokumen diterima/diproses pihak terkait',
                ],
                'deliver' => [
                    'Draf dokumen lingkungan atau hasil matrik pengukuran yang disepakati',
                    'Ringkasan status pengurusan serta langkah lanjutan',
                    'Catatan rekomendasi untuk kepatuhan operasional harian',
                ],
                'purpose' => 'Membantu fasilitas memelihara dokumen lingkungan yang tertib dengan alur kerja yang terukur.',
                'deps' => 'Ruang lingkup bergantung pada jenis usaha, dokumen lama yang dimiliki, dan ketentuan yang berlaku saat pelaksanaan. Detail lengkap dikonfirmasi saat konsultasi.',
            ],
            'kajian-assess' => [
                's' => 'kajian/penilaian kebutuhan khusus (keselamatan, lingkungan, sosial, bisnis)',
                'a' => 'perusahaan, pemerintah daerah, RS/BLUD, institusi pendidikan, serta organisasi yang memerlukan dasar data untuk pengambilan keputusan',
                'b' => 'Pengerjaan analisis terstruktur: pengumpulan data lapangan/dokumen, analisis, dan penyusunan laporan rekomendasi.',
                'scope' => [
                    'Tolok ukur tujuan kajian dan batas wilayah/objek yang dianalisis',
                    'Pengumpulan data: dokumen, wawancara, pengamatan, dan/atau pengukuran (sesuai kebutuhan kajian)',
                    'Analisis data dengan pendekatan metodologi yang disepakati',
                    'Penyusunan laporan dan pemaparan hasil kepada pengambil keputusan',
                ],
                'deliver' => [
                    'Laporan kajian berisi metodologi, temuan, dan rekomendasi',
                    'Pemaparan hasil (presentasi) kepada pihak klien bila diperlukan',
                    'Data pendukung dan lampiran analisis sesuai apa yang disepakati',
                ],
                'purpose' => 'Memberikan dasar bukti yang terstruktur untuk keputusan organisasi: dari penilaian risiko sampai perencanaan bisnis.',
                'deps' => 'Kelengkapan data dari klien menentukan kedalaman kajian. Lingkup final (unit analisis, periode data, kelengkapan lampiran) ditetapkan saat konsultasi.',
            ],
            'publication' => [
                's' => 'pendampingan publikasi artikel ilmiah pada jurnal terindeks',
                'a' => 'dosen, peneliti, mahasiswa pascasarjana, dan penulis naskah ilmiah yang butuh perawatan proses publikasi',
                'b' => 'Pendampingan teknis publikasi: pengecekan kesesuaian naskah/target jurnal, pengurusan proses, dan komunikasi editorial.',
                'scope' => [
                    'Evaluasi awal naskah (aksesibilitas topik terhadap target jurnal)',
                    'Rekomendasi target jurnal yang sesuai setelah naskah diperiksa',
                    'Pendampingan proses administrasi publikasi step-by-step',
                    'Informasi berjenjang per tahap (submisi, review, keputusan)',
                ],
                'deliver' => [
                    'Ringkasan evaluasi naskah dengan catatan perbaikan',
                    'Daftar/rencana target jurnal yang disepakati',
                    'Laporan status proses pada setiap tahap',
                ],
                'purpose' => 'Memudahkan penulis memahami proses publikasi ilmiah dengan pendampingan dari awal sampai hasil.',
                'deps' => 'Hasil publish akhir juga dipengaruhi proses jurnal (peer review) di luar kendali kami. Ketentuan biaya dan lembaga indeks cek saat konsultasi.',
            ],
            'mgmt-system' => [
                's' => 'penerapan atau pengelolaan sistem manajemen berdasarkan kerangka berstandar',
                'a' => 'perusahaan yang sedang menyiapkan, menerapkan, atau memelihara sistem manajemen tertentu',
                'b' => 'Pendampingan memahami kerangka, menyusun dokumen implementasi, dan menyiapkan audit/evaluasi internal.',
                'scope' => [
                    'Penjajakan kondisi awal organisasi terhadap kerangka yang dituju',
                    'Analisis gap terhadap kerangka dan tindakan prioritas',
                    'Pendampingan penyusunan dokumen (kebijakan, prosedur, catatan)',
                    'Persiapan evaluasi/audit internal dan tindak lanjut',
                ],
                'deliver' => [
                    'Analisis gap kondisi awal organisasi',
                    'Draf dokumen sistem sesuai lingkup yang disepakati',
                    'Panduan persiapan audit internal/eksternal',
                ],
                'purpose' => 'Membantu organisasi mempelajari dan menerapkan kerangka sistem manajemen secara bertahap dan terukur.',
                'deps' => 'Kecepatan penerapan bergantung komitman manajemen, sumber daya internal, dan jadwal yang disepakati. Lingkup final dibicarakan saat konsultasi.',
            ],
            'audit-finance' => [
                's' => 'penilaian kewajaran dan keterbatasan pelapotan keuangan perusahaan',
                'a' => 'pemilik perusahaan, manajemen, investor/pemegang saham, dan pihak yang memerlukan tinjauan data keuangan',
                'b' => 'Pengerjaan tahapan audit bertahap: perencanaan, pengujian sampel, evaluasi, dan penyajian hasil.',
                'scope' => [
                    'Konsultasi lingkup audit: periode, unit, dan tujuan penggunaan hasil',
                    'Perencanaan dan pengujian dokumen/rekaman keuangan yang relevan',
                    'Evaluasi temuan beserta implikasinya',
                    'Penyajian laporan hasil audit sesuai tingkat keterlibatan yang disepakati',
                ],
                'deliver' => [
                    'Laporan hasil audit beserta lampiran kerja',
                    'Rekomendasi perbaikan kepada manajemen',
                    'Rangkuman temuan berdasarkan prioritas',
                ],
                'purpose' => 'Memberi tinjauan independen atas kesehatan pelaporan keuangan perusahaan.',
                'deps' => 'Jangkauan audit dibatasi oleh ketersediaan dan kualitas dokumen klien. Keputusan penggunaan hasil audit sepenuhnya milik manajemen.',
            ],
            'business-admin' => [
                's' => 'layanan administrasi dan pendampingan pengurusan dokumen perizinan/usaha',
                'a' => 'pengusaha, perusahaan baru/berkembang, peserta pengadaan, dan unit yang memerlukan kelengkapan dokumen administrasi',
                'b' => 'Kerja sama pengumpulan requirements, pengisian formulir, serta pemantauan proses sampai dokumen diterbitkan.',
                'scope' => [
                    'Penjajakan jenis dokumen yang dibutuhkan dan kondisi dokumen saat ini',
                    'Penyusunan daftar syarat dan pengumpulan berkas',
                    'Pengisian/pengajuan formulir sesuai prosedur baku yang berlaku',
                    'Pemantauan status dan penyampaian hasil kepada klien',
                ],
                'deliver' => [
                    'Dokumen yang diproses, beserta bukti status secara ringkas (sesuai jenis layanan)',
                    'Ringkasan status dan catatan langkah berikutnya',
                    'Arsip digital ringkas dari berkas yang diproses',
                ],
                'purpose' => 'Meringankan perusahaan dari proses administrasi bertumpuk dan memangkas risiko pengajuan ditolak karena kelengkapan.',
                'deps' => 'Setiap jenis dokumen mempunyai ketentuan syarat sendiri yang dapat berubah sewакtu-waktu. Daftar syarat final dikonfirmasi saat konsultasi.',
            ],
        ];

        return self::$profiles;
    }

    // ------------------------------------------------------------------
    // Public API
    // ------------------------------------------------------------------

    /**
     * Build the editorial fields for one service item. Format/assessment-like
     * claims do not exist for services; anything that needs a live decision
     * (price timeline, party doing the inspection, legal guarantee) is routed
     * to the WhatsApp CTA and/or the dependencies field.
     *
     * @return array<string, mixed>
     */
    public static function build(CatalogItem $item): array
    {
        $profile = self::matchProfile($item->name);

        return [
            'intro' => self::intro($item->name, $profile),
            'purpose' => $profile['purpose'],
            'audience' => self::audience($item->name, $profile),
            'scope' => $profile['scope'],
            'deliverables' => self::deliverables($item->name, $profile),
            'documents' => self::documents($item->name, $profile),
            'process' => self::processSteps($item->name),
            'dependencies' => $profile['deps'],
            'faqs' => self::faqs($item->name),
        ];
    }

    /**
     * @return array{domain: string, s: string, a: string, b: string, scope: list<string>, deliver: list<string>, purpose: string, deps: string}
     */
    public static function matchProfile(string $name): array
    {
        $lower = mb_strtolower($name);

        foreach (self::PATTERNS as $domain => $needles) {
            foreach ($needles as $needle) {
                if (str_contains($lower, mb_strtolower($needle))) {
                    return ['domain' => $domain] + self::profiles()[$domain];
                }
            }
        }

        return ['domain' => 'business-admin'] + self::profiles()['business-admin'];
    }

    // ------------------------------------------------------------------
    // Composers
    // ------------------------------------------------------------------

    private static function intro(string $name, array $profile): string
    {
        return "\"{$name}\" bergerak di bidang {$profile['s']}. {$profile['b']} Nama layanan dikutip persis dari katalog sumber; detail lingkup akhir disepakati bersama saat konsultasi lebih lanjut.";
    }

    private static function audience(string $name, array $profile): string
    {
        return 'Layanan ini biasanya dibutuhkan oleh '.$profile['a'].'.';
    }

    /**
     * Deliverable bullets are family-based and end with a cautious sentence
     * anchoring the exact service name.
     *
     * @return list<string>
     */
    private static function deliverables(string $name, array $profile): array
    {
        return array_merge($profile['deliver'], [
            "Semua luaran berlaku untuk layanan \"{$name}\" ini sebatas lingkup yang disepakati tertulis",
        ]);
    }

    /**
     * @return list<string>
     */
    private static function documents(string $name, array $profile): array
    {
        $baseline = [
            'Data identitas usaha/perusahaan dan info kontak yang aktif',
            'Dokumen pendukung yang sudah Anda miliki (izin lama, dokumen laporan, atau data dasar terkait)',
            'Gambaran linimasa kebutuhan Anda (tenggat/pengurutan) agar dapat diprioritaskan',
        ];

        if ($profile['domain'] === 'kajian-assess') {
            $baseline[] = "Data awal yang Anda miliki relevan dengan \"{$name}\" (menentukan kedalaman kajian)";
        }

        if ($profile['domain'] === 'publication') {
            $baseline[] = 'Naskah/manuskrip beserta target jurnal (bila sudah ada)';
        }

        if ($profile['domain'] === 'audit-finance') {
            $baseline[] = 'Catatan/laporan keuangan periode yang akan ditinjau';
        }

        return $baseline;
    }

    /**
     * @return list<string>
     */
    private static function processSteps(string $name): array
    {
        return [
            'Hubungi salah satu kanal WhatsApp resmi di halaman ini dan sebutkan layanan "'.$name.'"',
            'Ceritakan kebutuhan dan klausul khusus Anda (tenggat, kondisi dokumen, prioritas)',
            'Tim kami menjajaki kemampuan layanan serta langkah yang dapat dilakukan',
            'Kesepakatan lingkup dan jadwal disepakati lalu pekerjaan berjalan sesuai rencana',
        ];
    }

    /**
     * @return list<array{question: string, answer: string}>
     */
    private static function faqs(string $name): array
    {
        return [
            [
                'question' => "Bagaimana memulai layanan \"{$name}\"?",
                'answer' => 'Kirim pesan lewat tombol WhatsApp di halaman ini dan sebutkan nama layanannya. Tim kami memandu langkah penjajakan kebutuhan dan dokumen awal.',
            ],
            [
                'question' => 'Apakah layanan ini ada jaminan keberhasilan?',
                'answer' => 'Kami tidak menyatakan jaminan keberhasilan karena output sering dipengaruhi ketentuan pihak ketiga/instansi. Yang kami berikan adalah alur kerja jelas, keterlibatan info yang transparan, dan status terkini pada setiap tahap.',
            ],
            [
                'question' => 'Berapa biaya dan durasi kerjanya?',
                'answer' => 'Biaya dan durasi tidak tercantum di katalog sumber karena bergantung pada lingkup yang disepakati — silakan ajukan lewat WhatsApp untuk penawaran yang sesuai kondisi Anda.',
            ],
        ];
    }
}
