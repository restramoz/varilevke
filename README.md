# Varilevke

Katalog **pelatihan K3** dan **jasa** (272 item dari dokumen sumber), artikel panjang
bantuan AI dengan alur *draft-only*, halaman wilayah kota/kecamatan, sanitasi HTML
sisi server, dan SEO sesuai sitemap/robots/canonical — dibangun di Laravel 13 (PHP 8.5, SQLite).

## Stack

- Laravel 13.38, PHP 8.5 — tanpa paket admin/SEO/purifier pihak ketiga.
- SQLite (dev & default produksi). MySQL dijelaskan di docs/DEPLOYMENT.md.
- Tailwind 4 + Vite (`npm run build`).
- PHPUnit untuk semua pengujian (`php artisan test`).

## Menjalankan lokal

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite      # DB default SQLite
php artisan migrate --seed          # catalog 272 item + wilayah Jawa Timur + settings
php artisan admin:create            # buat/reset akun admin (prompt password)
npm install
npm run build                       # atau: npm run dev saat development
php artisan serve                   # http://127.0.0.1:8811 (sesuaikan APP_URL)
```

> `db:seed` membuat admin hanya bila `ADMIN_PASSWORD` (.env) berisi minimal 8 karakter.
> Jika .env.lama tidak berisi, jalankan `php artisan admin:create` setelahnya.

## Perintah penting

| Perintah | Fungsi |
| --- | --- |
| `php artisan catalog:import` | Import/verifikasi katalog dari `docs/source/catalog-manifest.json` (idempoten, non-destruktif, laporan konflik) |
| `php artisan admin:create` | Buat/reset admin dengan password ber-hash |
| `php artisan migrate --seed` | Migrasi + seed (catalog, wilayah, settings) |
| `php artisan test` | Seluruh test suite (SQLite in-memory) |

## Generator artikel AI

- Aktif hanya bila `AI_API_KEY` diisi; tanpa itu tombol "Buat draf" nonaktif.
- Band kata server-enforced: minimum 8000, maksimum 10000, default 8000 (`config/varilevke.php`).
- Alur: tombol **Buat draf** → `POST admin/articles/generate-preview` → JSON draf
  mengisi form → admin mengedit → klik **Simpan** (`store`/`update`) → konten
  disinari `HtmlSanitizerService` sebelum ditulis DB. **Endpoint preview tidak pernah menulis DB.**
- Chunked: 1 panggilan outline JSON + 8 panggilan bagian, dengan retry terbatas,
  timeout, strict JSON (fence markdown dibersihkan), dan pesan error aman
  (tanpa kredensial / payload provider).

## Keamanan konten

- `App\Services\HtmlSanitizerService`: allowlist tag/atribut (DOMDocument) pada
  **input** (store/update), buang `script/iframe/style/object/...`, event handler,
  URL berbahaya (`javascript:`, `data:`), dan membungkus `<table>` dalam
  `div.article-table-scroll` agar bisa digeser horizontal di layar sempit.
- Output publik memakai `{!! $article->content !!}` hanya dari HTML yang sudah
  disinari pada penulisan — semua jalur tulis melewati sanitizer.

## Struktur ringkas

```
app/Services/Catalog/     manifest validator, importer idempoten, laporan
app/Services/Ai/          AiClient + generator chunked (draft-only)
app/Services/             HtmlSanitizerService
app/Http/Controllers/     Public (home, catalog, artikel, wilayah, SEO) + Admin/*
resources/views/          layout komponen, halaman publik, admin CRUD
database/seeders/         CatalogItemSeeder (import), RegionSeeder (BPS), SettingSeeder
database/data/            master-wilayah-bps.json (data publik BPS)
docs/source/              katalog sumber; config/catalog.php memvalidasi jumlahnya
```

## WhatsApp

Empat nomor tetap disimpan sebagai **string** dengan leading zero di
`config/contacts.php` (08118500177, 081399810272, 087894580806, 087894580649).
Komponen `<x-whatsapp-contacts :callout="$callout" />` merender grup tautan
`wa.me/62…`; nomor tidak pernah dikonversi ke integer dan tidak bisa ditambah
lewat panel pengaturan (hanya menyaring/menyembunyikan dari daftar yang ada).

## Dokumen lanjutan

- `docs/DEPLOYMENT.md` — panduan deploy CLI & shared hosting/cPanel.
- `docs/IMPLEMENTATION_STATUS.md` — status pekerjaan, hasil test aktual, blocker.
- `docs/REFERENCE_AUDIT.md` — asal pola yang dipakai dari proyek referensi.
