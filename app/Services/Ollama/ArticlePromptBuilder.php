<?php

namespace App\Services\Ollama;

use App\Models\CatalogItem;
use App\Models\City;
use App\Models\Kecamatan;
use App\Services\Catalog\Content\ServiceContentBuilder;
use App\Services\Catalog\Content\TrainingContentBuilder;
use App\Services\Catalog\RelatedItems;

class ArticlePromptBuilder
{
    public function __construct(
        protected RelatedItems $relatedItems = new RelatedItems,
    ) {}

    /**
     * Build system prompt tailored to the item category, regional context, and factual guardrails.
     */
    public function buildSystemPrompt(
        ?CatalogItem $catalogItem,
        ?City $city,
        ?Kecamatan $kecamatan,
        int $wordCount
    ): string {
        $category = $catalogItem?->category ?? 'pelatihan';
        $categoryRole = $category === 'jasa'
            ? 'konsultan teknis, manajemen operasional, dan kepatuhan lingkungan profesional di Indonesia'
            : 'konsultan dan instruktur K3 (Keselamatan dan Kesehatan Kerja) serta pengembangan kompetensi kerja industri di Indonesia';

        $categoryFocus = $this->buildCategoryFocus($catalogItem, $category);
        $regionalFocus = $this->buildRegionalGuidance($city, $kecamatan);
        $internalLinks = $this->buildInternalLinksGuidance($catalogItem, $city);

        return <<<PROMPT
Anda adalah {$categoryRole}.
Tugas Anda adalah menulis artikel edukatif, mendalam, praktis, dan otoritatif dalam Bahasa Indonesia formal untuk portal Varilevke.
Struktur artikel wajib mencakup: Pembuka (pendahuluan dan urgensi), Landasan & Kerangka Konseptual, Pembahasan Teknis Rinci (Langkah kerja/SOP, analisis risiko, studi kasus penerapan industri), Matriks/Tabel Terstruktur, Tanya Jawab (FAQ), dan Penutup (kesimpulan serta arahan konsultasi).

{$categoryFocus}

{$regionalFocus}

{$internalLinks}

BATASAN FAKTUAL KETAT (DILARANG KERAS MENGARANG):
1. DILARANG mengarang lembaga sertifikasi, nomor lisensi resmi, atau akreditasi sepihak (jangan mengklaim sertifikasi Kemnaker RI, BNSP, LSP, atau ISO kecuali tercantum secara pasti di data resmi).
2. DILARANG mengarang harga/biaya, durasi pelatihan/layanan yang kaku (misal: "Rp 3.500.000" atau "3 hari 2 malam"), atau jadwal kalender tertentu.
3. DILARANG mengarang alamat kantor fisik cabang lokal, nomor telepon lokal palsu, atau nama instruktur fiktif.
4. DILARANG menjamin kepatuhan hukum instan atau kelulusan mutlak terhadap perizinan pemerintah (misal SLF, UKL-UPL, izin alat); arahkan konfirmasi resmi melalui konsultasi WhatsApp.
5. DILARANG membuat testimoni fiktif, angka statistik keberhasilan tanpa dasar (misal "99.8% lulus"), atau klaim klien palsu.
6. Bila suatu rincian tidak tersedia pada data rujukan, jelaskan secara objektif atau nyatakan bahwa rincian tersebut ditetapkan melalui kesepakatan tertulis saat konsultasi.

KUALITAS PENULISAN & FORMAT HTML:
1. Nada bahasa: Profesional, mengalir alami, terstruktur rapi, tanpa klise pembuka AI seperti "Di era modern saat ini", "Tak dapat dipungkiri", atau paragraf berulang.
2. Gunakan tag HTML semantik lengkap: <h2>, <h3>, <p>, <ul>, <ol>, <li>, <strong>, <em>, <blockquote>, dan <table> (dengan <thead><tr><th>...</th></tr></thead> dan <tbody><tr><td>...</td></tr></tbody>).
3. Buat minimal satu tabel data/perbandingan teknis dalam format <table> yang rapi dan kontekstual.
4. Target panjang konten: Sekitar {$wordCount} kata (antara 6.000 hingga 10.000 kata). Tulis setiap bagian secara mendalam, rinci, dan substantif.

OUTPUT WAJIB:
Kembalikan HANYA dokumen JSON murni tanpa pembungkus markdown ```json ```.
Format skema JSON:
{
  "title": "Judul Artikel Informatif dan Otoritatif",
  "slug": "slug-url-ramah-seo",
  "excerpt": "Ringkasan eksekutif 2-3 kalimat mengenai intisari artikel.",
  "content": "<p>Isi artikel lengkap dalam HTML semantik...</p>",
  "seo_title": "SEO Title Max 60 Karakter | Varilevke",
  "meta_description": "Meta description persuasif max 155 karakter.",
  "suggested_faqs": [
    {"q": "Pertanyaan 1...", "a": "Jawaban ringkas 1..."},
    {"q": "Pertanyaan 2...", "a": "Jawaban ringkas 2..."}
  ],
  "image_brief": {
    "concept": "Konsep visual foto fotografi realistis dalam Bahasa Indonesia",
    "english_prompt": "Detailed descriptive photo prompt in English for Google Flow: realistic photography, modern Indonesian workplace, industrial context, professional safety equipment, sharp focus, no text overlays, 8k",
    "aspect_ratio": "16:9",
    "recommended_alt": "Alt text ramah SEO dalam Bahasa Indonesia"
  }
}
PROMPT;
    }

    /**
     * Build user prompt directing the article creation.
     */
    public function buildUserPrompt(
        string $topic,
        ?CatalogItem $catalogItem,
        ?City $city,
        ?Kecamatan $kecamatan,
        int $wordCount
    ): string {
        $category = $catalogItem?->category ?? 'pelatihan';
        $location = $this->buildLocationString($city, $kecamatan);
        $itemName = $catalogItem?->name ?? 'Layanan & Pelatihan Terpadu';

        $specificDetails = '';
        if ($catalogItem !== null) {
            $specificDetails = "- Layanan/Program Resmi: {$itemName}\n- Kategori Katalog: {$category}\n";
        }

        return <<<PROMPT
Silakan susun artikel lengkap dan mendalam dengan rincian parameter:
- Topik / Judul Panduan: {$topic}
{$specificDetails}- Wilayah Target: {$location}
- Target Panjang Konten: Sekitar {$wordCount} kata (rentang minimum 6.000 - maksimum 10.000 kata).

Pastikan seluruh uraian berfokus pada materi spesifik terkait dan patuhi seluruh batasan faktual.
Keluarkan HANYA dokumen JSON valid sesuai skema yang telah ditentukan.
PROMPT;
    }

    /**
     * Build continuation prompt for chunked generation when word count is below threshold.
     */
    public function buildContinuationPrompt(
        string $existingHtml,
        int $targetWords,
        ?CatalogItem $catalogItem,
        ?City $city,
        ?Kecamatan $kecamatan
    ): array {
        $category = $catalogItem?->category ?? 'pelatihan';
        $brand = $catalogItem?->name ?? 'Varilevke';
        $location = $this->buildLocationString($city, $kecamatan);

        $systemPrompt = "Anda adalah editor ahli {$brand} berbahasa Indonesia. Tugas Anda melanjutkan draf artikel yang sudah ada dengan menambahkan bab dan subbab baru sebanyak kira-kira {$targetWords} kata. Dilarang mengulang judul atau paragraf yang sudah ditulis sebelumnya. Output HANYA fragmen HTML semantik (<h2>, <h3>, <p>, <ul>, <ol>, <li>, <table>). Jangan mengeluarkan JSON, markdown fences, atau komentar.";

        $userPrompt = "Konten artikel yang sudah ada (jadikan konteks, jangan ditulis ulang):\n"
            .mb_substr(strip_tags($existingHtml), -1200)
            ."\n\nTambahkan bagian lanjutan substantif baru (seperti: prosedur inspeksi bertahap, matriks mitigasi risiko lanjutan, panduan integrasi sistem di lapangan {$location}, checklist kepatuhan, atau studi kasus praktis) sebanyak ~{$targetWords} kata. Output HANYA fragmen HTML valid tambahan.";

        return [
            'system' => $systemPrompt,
            'user' => $userPrompt,
        ];
    }

    /**
     * Generate location label based on City and Kecamatan.
     */
    public function buildLocationString(?City $city, ?Kecamatan $kecamatan): string
    {
        if ($kecamatan !== null && $city !== null) {
            return "Kecamatan {$kecamatan->name}, {$city->label()}, Provinsi {$city->province}";
        }

        if ($city !== null) {
            return "{$city->label()}, Provinsi {$city->province}";
        }

        return 'Indonesia (Skala Nasional)';
    }

    /**
     * Build category-specific guidance (Training vs Service).
     */
    protected function buildCategoryFocus(?CatalogItem $item, string $category): string
    {
        if ($category === 'jasa') {
            $serviceGuidance = '';
            if ($item !== null) {
                $profile = ServiceContentBuilder::matchProfile($item->name);
                $serviceGuidance = "Rujukan Layanan Resmi \"{$item->name}\":\n"
                    .'- Bidang Spesifik: '.$profile['s']."\n"
                    .'- Klien Utama: '.$profile['a']."\n"
                    .'- Tujuan Layanan: '.$profile['purpose']."\n"
                    .'- Lingkup Kerja Standar: '.implode('; ', array_slice($profile['scope'], 0, 4))."\n"
                    .'- Luaran Pekerjaan (Deliverables): '.implode('; ', array_slice($profile['deliver'], 0, 3))."\n"
                    .'- Dependensi / Catatan Keberhasilan: '.$profile['deps']."\n";
            }

            return <<<TEXT
PEDOMAN KONTEN ARTIKEL JASA / LAYANAN TEKNIS:
{$serviceGuidance}
Fokus utama artikel jasa adalah memberikan pemahaman menyeluruh bagi pengambil keputusan mengenai kebutuhan layanan, cakupan evaluasi/kajian teknis, tahapan kerja sama profesional, dokumen persiapan yang diperlukan dari klien, serta faktor-faktor penentu keberhasilan pekerjaan. Tekankan transparansi alur kerja dan mitigasi risiko operasional klien.
TEXT;
        }

        // Training ("pelatihan")
        $trainingGuidance = '';
        if ($item !== null) {
            $profile = TrainingContentBuilder::matchProfile($item->name);
            $trainingGuidance = "Rujukan Program Pelatihan Resmi \"{$item->name}\":\n"
                .'- Pokok Bahasan: '.$profile['s']."\n"
                .'- Sasaran Peserta: '.$profile['a']."\n"
                .'- Gambaran Kompetensi: '.$profile['b']."\n"
                .'- Tujuan Pembelajaran Inti: '.implode('; ', array_slice($profile['o'], 0, 4))."\n"
                .'- Materi / Silabus Pokok: '.implode('; ', array_slice($profile['c'], 0, 5))."\n";
        }

        return <<<TEXT
PEDOMAN KONTEN ARTIKEL PELATIHAN K3 & KOMPETENSI KERJA:
{$trainingGuidance}
Fokus utama artikel pelatihan adalah edukasi kompetensi lapangan: tujuan pembelajaran terukur, pemahaman regulasi dan bahaya kerja, silabus materi teknis, tata cara pemeriksaan peralatan, penggunaan APD yang benar, serta budaya keselamatan kerja. Tekankan peningkatan keahlian personel praktisi maupun pengawas.
TEXT;
    }

    /**
     * Build regional factual guidance.
     */
    protected function buildRegionalGuidance(?City $city, ?Kecamatan $kecamatan): string
    {
        if ($city === null) {
            return 'KONTEKS WILAYAH: Nasional. Uraikan konteks industri dan keselamatan kerja yang berlaku secara umum di seluruh wilayah Indonesia.';
        }

        $locationLabel = $this->buildLocationString($city, $kecamatan);

        return <<<TEXT
KONTEKS WILAYAH ({$locationLabel}):
Artikel ini dikhususkan untuk pembaca di {$locationLabel}.
Jelaskan relevansi industri, kegiatan operasional, atau proyek yang umum berkembang di wilayah {$city->province} dan {$city->name} (seperti manufaktur, kawasan industri, infrastruktur sipil, pergudangan, maritim, atau komersial yang relevan dengan daerah tersebut).
PENTING: Jangan mengarang alamat kantor cabang fiktif atau nomor kontak lokal palsu di {$city->name}. Seluruh konsultasi dan koordinasi penugasan tim ahli di wilayah ini diarahkan melalui perwakilan resmi Varilevke via WhatsApp.
TEXT;
    }

    /**
     * Provide valid internal linking suggestions.
     */
    protected function buildInternalLinksGuidance(?CatalogItem $catalogItem, ?City $city): string
    {
        $links = [];

        if ($catalogItem !== null) {
            $related = $this->relatedItems->for($catalogItem, 3);
            foreach ($related as $rel) {
                $path = $rel->category === 'jasa' ? "/jasa/{$rel->slug}" : "/pelatihan/{$rel->slug}";
                $links[] = "- <a href=\"{$path}\">{$rel->name}</a>";
            }
        }

        if ($city !== null) {
            $links[] = "- <a href=\"/wilayah/{$city->slug}\">Layanan K3 & Konsultasi di {$city->name}</a>";
        }

        if (empty($links)) {
            return '';
        }

        $linkList = implode("\n", $links);

        return <<<TEXT
TAUTAN INTERNAL YANG DAPAT DIGUNAKAN DI DALAM ARTIKEL:
Bila relevan dengan alur penjelasan, sematkan tautan internal berikut menggunakan tag <a> yang valid:
{$linkList}
TEXT;
    }
}
