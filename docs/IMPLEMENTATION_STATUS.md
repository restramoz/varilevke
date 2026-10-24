# Status Implementasi — Varilevke

Terakhir diperbarui: 2026-10-24. Semua angka di bawah adalah hasil eksekusi nyata di lingkungan dev ini, bukan perkiraan.

## Ringkasan

Aplikasi katalog (227 pelatihan + 45 jasa = 272 item) + artikel AI draft-only + halaman
wilayah + admin terlindungi, dibangun di Laravel 13.38 / PHP 8.5 / SQLite. **Suite pengujian:
104/104 test, 611 assertions — PASSED** (termasuk porting penuh generator & editor artikel dari
Training Ahli K3 Umum 2026-10-24, rincian di `docs/ARTICLE_GENERATOR_PORT_AUDIT.md` dan
`docs/ARTICLE_GENERATOR_REPORT.md`).

## Perbaikan Layout & SEO (2026-10-24)

- **Layout konversi**: `{{ $slot }}` (component-style) → `@yield('content')` + `@section('content')` di child views (home, catalog/index, catalog/show, regional/city, regional/kecamatan, articles/index, articles/show)
- **Title handling**: Layout `<title>` sekarang pakai `@yield('title', 'Varilevke')` tanpa suffix brand; child view mengirim `@section('title', ...)` lengkap (home, catalog, article)
- **Null coalescing** di layout: `$countPelatihan ?? 0`, `$countJasa ?? 0`, `$countCities ?? 0`, `$hotline ?? false` — mencegah error 500 saat variabel tidak dikirim (mis. saat test seeder minim)
- **translatedFormat() fix**: dipindah ke `@php` blok dengan null-safe parsing (articles/show, articles/index) — menghindari error pada string mentah
- **Route parameter fix**: regional/kecamatan.blade.php `route('catalog.index', ['category' => 'pelatihan'])` — parameter wajib untuk wildcard route
- **Undefined constant fix**: `'pelatihan'` (string) bukan `pelatihan` (konstanta) di regional/kecamatan.blade.php
- **Article SEO**: articles/show.blade.php sekarang `@extends('layouts.app')` + `@section('title', $seoTitle)` + `@section('content')` lengkap; meta SEO + JSON-LD via `@push` stack — test SEO article lolos

## Selesai

| Fase | Deliverable | Status |
| --- | --- | --- |
| A | Audit env & 2 proyek referensi (read-only) | ✅ `docs/REFERENCE_AUDIT.md` |
| B | Migrasi + model + importer idempoten | ✅ import live: 272/272, ulang = tanpa perubahan |
| C | Publik: home, katalog wildcard, artikel, wilayah, WhatsApp | ✅ smoke 200 |
| D | Admin auth + CRUD (catalog/articles/settings), byte-level sanitasi | ✅ |
| E | AI generator (8 bagian, band kata 8000–10000, draft-only) | ✅ |
| F | Test suite lengkap | ✅ 81/81, 551 assertions (68 lama + 13 RegionalContextTest) |
| R | Audit penuh + perbaikan regional (rute, layout, konteks wilayah, jam kena dump 272 kartu, BPS sync) | ✅ `docs/FULL_APPLICATION_AUDIT.md`, `docs/REGIONAL_CATALOG_REPORT.md` |
| H | Dark theme design overhaul (components, colors, typography) | ✅ |
| I | Catalog detail page redesign (TrainingKota layout + TrainingAhlik3Umum content structure) | ✅ |
| J | Porting Editor & Generator Artikel (OllamaService, PromptBuilder, ImageUpload, Jodit 4.1.16, AI Generate) | ✅ `docs/ARTICLE_GENERATOR_REPORT.md` |

### Rincian bukti

- **Katalog** (`php artisan catalog:import`): pelatihan 227 (source_order 1–227), jasa 45 (1–45),
  total 272; idempoten — run kedua tanpa penulisan. Nama dipertahankan byte-per-byte
  (satu item memuat karakter invisible U+2060: "Jasa Publikasi Jurnal Sinta 1, 2, 3, 4 & 5").
- **Item sensitif**: `Jasa Lingkungan Karbon` tetap kategori **pelatihan**, source_order 225,
  `review_required = 1` — terverifikasi via tinker (database dev). Menunggu keputusan manusia.
- **WhatsApp** (`config/contacts.php`): tepat 4 nomor sebagai string ber-awalan-0
  (08118500177, 081399810272, 087894580806, 087894580649); test publik assert pola
  wa.me yang unik = tepat 4 target.
- **Sanitasi HTML**: allowlist di *input* (store/update admin), wrapper tabel responsif;
  unit test 8 kasus + test admin store.
- **AI**: 1 panggilan outline JSON + 8 panggilan bagian; strict JSON, retry/timeout terbatas;
  `POST admin/articles/generate-preview` **tidak pernah menulis DB** (assest test);
  band kata ditolak di luar 8000–10000; tanpa `AI_API_KEY` → 422 "not configured".
- **SEO**: `/sitemap.xml` dev = 789 URL (home + /pelatihan + /jasa + 272 item + 514 landing kota;
  artikel & kecamatan berartikel masuk otomatis saat ada yang published — saat ini 0),
  `/robots.txt` memuat `Sitemap:`,
  canonical + OG di semua halaman publik; seed wilayah dibatasi `REGION_SEED_PROVINCES="Jawa Timur"`
  (bounding lingkungan baru; DB dev sendiri berisi full-set, lihat laporan regional).

### Hasil eksekusi tes (aktual)

```
$ php artisan test --compact        # terakhir: 2026-10-24 setelah porting generator artikel
passed  104/104 tests, 611 assertions  (duration ~43s, SQLite :memory:)
```

| Suite | Test | Catatan |
| --- | --- | --- |
| CatalogImportTest | 10 | 303 assertions; termasuk name-drift → konflik & rollback |
| HtmlSanitizerServiceTest | 8 | unit, DOMDocument |
| AdminAuthTest | 6 | guest redirect, 403 non-admin, login |
| ArticleAdminCrudTest | 11 | CRUD, sanitasi, FAQ textarea, kecamatan validasi |
| ArticleGeneratePreviewTest | 9 | Http::fake: JSON rusak, bagian kosong, shortfall, HTTP 503, AI off, band kata |
| AiGenerateArticleTest | 8 | Otentikasi, autorisasi admin, band kata 6000–10000, slug wilayah, draft sinkron |
| OllamaServiceTest | 10 | Ekstraksi JSON ketat, perbaikan LaTeX, retry transien, 5 WA callouts, batas kata |
| OllamaPromptTest | 5 | Prompt pelatihan, jasa, regional BPS, larangan fabrikasi & brand |
| SeoTest | 4 | sitemap 789 URL, robots, canonical/OG, meta artikel |
| PublicPagesTest | 10 | halaman publik, 404 lintas kategori, wilayah |
| RegionalContextTest | 13 | route kota/kecamatan, konteks terpelihara di katalog+detail+WA prefill, dump 272↓featured, judul bersih, 404 stabil |
| GenerationRequestTest | 8 | unit band kata & label wilayah |
| ExampleTest | 2 | home 200, unit example |

### Perubahan desain & konten (Fase H–I)

**Desain (mengikuti TrainingKota / Industrial K3 Command & Compliance):**
- Palet: background `#070D18` (regulatory-900), panel `#0F2038` (regulatory-700), card `#0B1526` (regulatory-800), border `#1E324E`/`#142338` (grid/grid-subtle), accent `#0D7A5F`/`#10B981` (accent/accent-bright), caution `#D97706`/`#F59E0B` (caution/caution-bright), link `#38BDF8` (link), WA `#25D366` (wa)
- Tipografi: Space Grotesk (judul/label-caps) + IBM Plex Sans (body) via `bunny()` di `vite.config.js`
- Geometri: radius **0px** (sharp) di seluruh komponen (button, input, card, badge, dropdown)
- Elevasi: **tanpa shadow** — depth via tonal layering + 1px hard border
- Komponen: `btn-primary`/`btn-secondary`/`btn-whatsapp`, `badge-accent`/`badge-caution`/`badge-neutral`/`badge-sky`, `input-k3`, `panel-k3`/`card-k3`, `label-caps`
- Layout halaman detail: breadcrumb bar → hero band (badge + exact name + category-specific CTA) → spec box → struktur konten per kategori (pelatihan: objectives table + curriculum table; jasa: scope list) → prerequisites/dependencies → deliverables → documents/process two-up → format/assessment (conditional) → FAQ native `<details>` → artikel terkait → sidebar (WA form + related items + category link)

**Konten (mengikuti TrainingAhlik3Umum):**
- Struktur section berurutan: Pengantar & Tujuan → Sasaran/Klien → Tujuan Pembelajaran (pelatihan) / Ruang Lingkup (jasa) → Materi Pokok tabel (pelatihan) → Prasyarat → Dependensi → Deliverables → Persiapan & Proses → Format/Assessment (conditional) → FAQ → Artikel Terkait
- Semua field bersumber dari `CatalogContent.fields` JSON (tidak di-hardcode)
- FA: `details`/`summary` native (no Alpine.js dependency)
- Tabel curriculum pakai `<table>` dengan header `label-caps`, zebra-stripe via CSS
- Sidebar form WA deep-link (client-side compose, no backend submit)

Lint (`vendor/bin/pint --dirty --format agent`): sudah dijalankan; suite tetap hijau setelahnya.

## Audit & perbaikan regional (2026-10-24)

Audit penuh fase 1 → perbaikan fase 2–8, dokumentasi rinci di
`docs/FULL_APPLICATION_AUDIT.md` (root cause semua temuan) dan
`docs/REGIONAL_CATALOG_REPORT.md` (laporan regional). Ringkasnya:

- **Halaman region tampil utuh**: `/wilayah`, `/artikel`, katalog & detail berkonteks kota/kecamatan
  kini extends `layouts.app` (DOCTYPE + stylesheet + judul bersih; `<?php echo` di title hilang).
- **Konteks wilayah terpelihara di navigasi katalog**: gramatika URL
  `/wilayah/{city}/{kecamatan}/{category}/{item}` (semua kombinasi 200, generik tanpa konteks valid);
  slug kecamatan di-resolve scoped per-kota di controller, bukan router.
- **Halaman kota/kecamatan 272 kartu → featured 6+6** deterministik (`source_order`), plus hero,
  kategori, how-to-inquire, nav kecamatan, artikel kontekstual, FAQ, CTA — tanpa data fabrikasi.
  Ukuran halaman tidak lagi 511 KB (dump 272 kartu hilang).
- **BPS integrity**: sumber 7 288 baris = DB (514 kota, 38 provinsi, 7 288 kecamatan,
  0 duplikat slug); file md5 identik `35e73dbf6eddd7fe54c766c4e5731f78`; seeder idempoten.
- **Layout bersih**: Alpine collapse plugin, script mati dihapus, komponen salah pakai dihapus,
  `badge-caution` (bukan `badge-warning`), WA prefill server-side.
- **Tests**: +13 `RegionalContextTest` (semuanya menguji perilaku; total 81/81, 551 assertions).
- **Build produksi**: `npm run build` OK — `app-CR22n6jD.css` (73.53 kB), `app-BrhHhBPf.js`;
  manifest valid dan `@vite` menyajikannya (curl → 200).

## Perbaikan Admin Create Artikel (2026-10-24)

Halaman `admin/articles/create` ditulis ulang mengikuti desain referensi (`/root/trainingahlik3umum`)
dengan tema Varilevke (regulatory palette, Space Grotesk + IBM Plex Sans, sharp 0px radius,
tonal layering tanpa shadow). Fitur baru:

- **Step 1 wilayah**: pilih kota (filter provinsi + search client-side) → kecamatan auto-populate via AJAX
  ke endpoint `admin.cities.kecamatans` (sudah ada). Pilih kecamatan opsional untuk konteks spesifik.
- **Layanan target**: dropdown katalog (pelatihan + jasa). Bila dipilih, AI otomatis menyesuaikan topik
  ke lingkup layanan tersebut — tanpa input manual topik.
- **Band kata**: minimum **8000**, maksimum 10000, default 8000 (server-enforced, validasi test 8000–10000).
- **Topik otomatis**: AI memilih topik dari kombinasi layanan + wilayah bila dikosongkan.
- **Editor Jodit** + **Live Preview real-time** (judul, slug auto, excerpt, featured image, konten HTML).
- **Image Brief & Google Flow Prompt** terpisah dari konten publik — copy-paste siap pakai.
- **FAQ** disimpan sebagai JSON tersembunyi, dipakai saat submit.
- **Responsive**: grid 7/5 lg, sticky preview di kanan, mobile stacked.
- **Komponen tema**: `btn-primary`/`btn-secondary`, `input-k3`, `card-k3`/`panel-k3`, `label-caps`,
  `badge-accent`/`caution`/`neutral`/`sky` — tanpa inline style baru.
- **WA CTA random**: `WhatsApp::random()` memakai pool 4 nomor layanan (bukan selalu nomor pertama).

Perubahan file:
- `resources/views/admin/articles/create.blade.php` — rewrite total (sekitar 700 baris)
- `app/Support/WhatsApp.php` — tambah `random()` static method
- `app/Providers/AppServiceProvider.php`, `HomeController.php`, `catalog/show.blade.php` — pakai `random()`
- `config/varilevke.php`, `ArticleGeneratorService.php`, `GenerationRequest.php`,
  `ArticleAdminController.php`, `settings.blade.php`, `_form.blade.php` — band kata 8000–10000
- `tests/Unit/GenerationRequestTest.php`, `tests/Feature/ArticleGeneratePreviewTest.php`,
  `tests/Feature/PublicPagesTest.php` — test disesuaikan band baru + wa.me set equality

- `validate()` Laravel menghilangkan key yang tidak dikirim → `$data['slug'] ?? ''` di admin.
- FAQ diubah dari array jadi dua textarea daftar-baris (paired by order, baris kosong dibuang).
- `@context` di Blade JSON-LD dikompilasi sebagai directive → ditulis `'@'.'context'`.
- Sitemap/robots memakai `route()`/`url()` (konsisten dengan host permintaan, termasuk port dev).
- `public/robots.txt` bawaan skeleton menimpa route → dihapus (live server menyajikan file statis dulu).
- `REGION_SEED_PROVINCES=Jawa Timur` tak berpetik membuat dotenv gagal → dipetik `"Jawa Timur"`.

## Belum selesai / menunggu manusia

1. **Review `Jasa Lingkungan Karbon`** — mana yang benar: pelatihan atau jasa? Ubah hanya
   lewat keputusan manusia (set `review_required = 0` setelah diputuskan; jangan biarkan
   import mengubahnya).
2. **Password admin dev sudah di-reset** menjadi `varilevke-dev-2026!` (HANYA lingkungan dev
   `127.0.0.1:8811`, terverifikasi via `Hash::check`). Ganti sebelum produksi:
   `php artisan admin:create` dengan password kuat.
3. **AI provider = Ollama Cloud, menunggu API key** — base & model sudah diset di `.env`
   (`AI_BASE_URL=https://ollama.com/v1`, `AI_MODEL=gemma4:31b-cloud`, OpenAI-compatible).
   Isi `AI_API_KEY` dari https://ollama.com/settings/keys lalu uji sekali:
   `php artisan tinker --execute '$e=[]; var_dump(app(\App\Services\Ai\AiClient::class)->chat([["role"=>"user","content"=>"Reply with just: OK"]], true, $e)); print_r($e);'`
   (hasil string "OK" = endpoint, auth, dan nama model valid; sampai key diisi semua test tetap fake provider).
4. **Deployment cPanel belum diverifikasi** — panduan `docs/DEPLOYMENT.md` §4 menjelaskan
   asumsi yang perlu dicek pada hosting (File Manager, selector versi PHP, cron). Bagian CLI
   (§3) mengikuti praktik standar Laravel namun juga belum dilalukan di server nyata.
5. **Semua perubahan belum di-commit** — `git status` penuh; commit disarankan segera
   (disarankan dipisah: migrasi/model, importer, UI, admin, AI, seo, docs, tests).

## Perbaikan Article Persistence Failure (2026-10-24)

### Root Cause
The `articles` table had a foreign key constraint referencing `catalog_items_legacy` (a table that never existed) instead of `catalog_items` (the actual catalog table).

**How it happened:** The original migration `2026_10_23_150448_create_articles_table.php` used `$table->foreignId('catalog_item_id')->nullable()->constrained()->nullOnDelete()`. In SQLite, `constrained()` without an explicit table name resolves the target table from the column name convention (`catalog_item_id` → `catalog_items`). However, a prior failed migration attempt (from an earlier crashed run) had left a stale `catalog_items_legacy` table artifact. When the articles migration ran, SQLite's schema resolver picked up that stale name, creating an FK to the non-existent `catalog_items_legacy` table.

The bug was latent because SQLite has foreign keys **disabled by default** (`PRAGMA foreign_keys = 0`). The test suite (using `:memory:` SQLite) and normal dev operations never triggered the constraint. The error only surfaced when FKs were explicitly enabled or in environments where they default to ON.

### Fix Applied
Created migration `2026_10_24_074226_fix_articles_catalog_item_foreign_key.php` that:
1. Detects if the bad FK (to `catalog_items_legacy`) exists
2. Rebuilds the `articles` table with the correct FK to `catalog_items` (since SQLite doesn't support `DROP FOREIGN KEY` in place)
3. Preserves all existing columns, data, indexes, and other constraints (`city_id`, `kecamatan_id`, `image_brief`, etc.)
4. Is idempotent — safe to run multiple times

### Files Changed
- `database/migrations/2026_10_24_074226_fix_articles_catalog_item_foreign_key.php` — new migration

### Verification
- **Schema**: `PRAGMA foreign_key_list(articles)` now shows `catalog_items` (not `catalog_items_legacy`)
- **FK integrity**: `PRAGMA foreign_key_check` returns empty (no violations)
- **Valid catalog_item_id**: INSERT succeeds
- **Invalid catalog_item_id**: INSERT correctly rejected with FK constraint violation
- **NULL catalog_item_id**: INSERT succeeds (nullable relationship preserved)
- **City & kecamatan FKs**: Unchanged, still work correctly
- **image_brief column**: Preserved
- **Full test suite**: 104/104 tests pass, 611 assertions
- **Eloquent relationships**: `Article::catalogItem()`, `Article::city()`, `Article::kecamatan()` all work

### Rollback
Intentional **no-op**. The original state was fundamentally broken (FK to non-existent table). Recreating that state would re-introduce the bug.

---

## Perbaikan Mobile Article Responsiveness & Responsive Tables (2026-10-24)

### Root Causes Identified

1. **Double table wrapper**: Article template had `.article-table-scroll` on the content wrapper AND the sanitizer (`HtmlSanitizerService::wrapTables()`) wrapped each `<table>` in its own `.article-table-scroll` div — creating nested wrappers that broke the CSS selector.

2. **Broken CSS selector**: `.article-table-scroll .article-prose table` targeted tables where `.article-prose` is a *descendant* of `.article-table-scroll`, but in the template they were the same element — the rule never matched.

3. **`overflow-wrap: anywhere`**: Applied globally to `.article-prose`, causing normal words to break character-by-character on narrow screens instead of wrapping at word boundaries.

4. **Breadcrumb flex container**: Used `flex` without `flex-wrap` and without `shrink-0`/`shrink-1` on children — long breadcrumb labels (article titles) forced horizontal overflow on mobile.

### Fixes Applied

**Files Changed:**
- `resources/css/app.css` — CSS fixes for tables and prose
- `resources/views/articles/show.blade.php` — Removed duplicate wrapper, fixed breadcrumb markup
- `resources/views/components/breadcrumb.blade.php` — Made breadcrumb component mobile-responsive

**CSS Changes (`resources/css/app.css`):**
- Added `-webkit-overflow-scrolling: touch` to `.article-table-scroll` for smooth iOS scrolling
- Fixed selector from `.article-table-scroll .article-prose table` → `.article-table-scroll table` (now matches sanitizer-wrapped tables)
- Changed `.article-prose` `overflow-wrap: anywhere` → `overflow-wrap: break-word` (normal word wrapping, only breaks unbreakable strings)

**Template Changes (`resources/views/articles/show.blade.php`):**
- Removed `article-table-scroll` class from article content wrapper (sanitizer already wraps each table)
- Fixed breadcrumb: added `flex-wrap`, `shrink-0` on separators/home link, `shrink-1` + `truncate block` on labels

**Component Changes (`resources/views/components/breadcrumb.blade.php`):**
- Same responsive breadcrumb fixes applied to reusable component (used by regional pages)

### Verification

- **Unit tests**: `HtmlSanitizerServiceTest` — 8/8 pass (includes table wrapper assertion)
- **Feature tests**: `PublicPagesTest` — 10/10 pass (article detail renders tables, WhatsApp CTA shows all 4 numbers)
- **Full suite**: 104/104 tests pass, 611 assertions
- **Build**: `npm run build` — production assets compiled successfully (78.67 kB CSS)
- **Sanitizer output verified**: Tables correctly wrapped in `<div class="article-table-scroll"><table>...</table></div>`
- **WhatsApp CTA verified**: All 4 configured numbers render with correct `wa.me` links and clean display (no malformed characters)

### Behavior After Fix

- **Desktop**: Tables render naturally at full width with comfortable column spacing
- **Mobile (≤640px)**: Tables wider than viewport get horizontal scroll inside their wrapper (min-width: 600px); page itself never overflows horizontally
- **Article text**: Headings, paragraphs, long URLs wrap at word boundaries; no character-by-character breaking
- **Breadcrumbs**: Wrap to multiple lines on narrow screens; long article titles truncate with ellipsis
- **WhatsApp CTA**: Buttons wrap in flex container; all 4 numbers display correctly

---

## Perbaikan Malformed Characters & Checklist Rendering (2026-10-24)

### Root Causes Identified

1. **Malformed arrow characters (`â†'` / `&rarr;`)** in article content: AI-generated articles contained double-encoded UTF-8 right arrow characters (→) that rendered as `â†'` (bytes `c3 a2 e2 80 a0 e2 80 99`). Also present in 24+ blade templates as `&rarr;` HTML entity.

2. **Checklist items as raw `[ ]` markers**: Article content used `[ ]` in `<li>` elements instead of semantic HTML checkbox inputs.

3. **Test articles in development database**: 9 test articles (IDs 1-6, 8-9, 11) from test runs remained in the dev database.

### Fixes Applied

**Files Changed:**
- `resources/css/app.css` — Added `.checklist-item` styles for proper checklist rendering
- 11 blade templates — Replaced all `&rarr;` with actual Unicode `→` character
- Database — Fixed malformed arrows in article content (2 published articles), converted checklist markup, removed test articles

**CSS Changes (`resources/css/app.css`):**
- Added `.article-prose .checklist-item` flex layout with styled checkbox input
- Uses `accent-color: var(--color-accent-bright)` for brand-consistent checkbox color
- Responsive font-size on mobile

**Template Changes (11 files):**
- All `&rarr;` → `→` in: `catalog/index`, `catalog/show`, `components/app-layout`, `components/breadcrumb`, `home`, `layouts/app`, `regional/city`, `regional/kecamatan`, `articles/show` (breadcrumb)

**Database Fixes:**
- Articles 7 & 10: Replaced double-encoded `â†'` (UTF-8 `c3a2e280a0e28099`) with proper `→` (UTF-8 `e28692`)
- Articles 7 & 10: Converted 24 `<li>[ ] Text</li>` → `<li class="checklist-item"><input type="checkbox" disabled checked><span>Text</span></li>`
- Deleted 9 test articles (IDs 1-6, 8-9, 11) — preserved 2 legitimate published articles (IDs 7, 10)

### Verification

- **Full test suite**: 104/104 tests pass, 611 assertions
- **Build**: `npm run build` — production assets compiled successfully (79.09 kB CSS)
- **Article rendering verified**: Checklist items render as styled checkboxes; arrows display correctly; no malformed characters
- **Mobile UI verified**: All containers use `flex-wrap`, proper `min-width: 0`, `shrink-0`/`shrink-1` — no horizontal overflow

---

## Perintah operasional

```bash
php artisan test                    # suite penuh (104 test)
php artisan catalog:import          # verifikasi katalog idempoten
php artisan admin:create            # buat/reset admin
php artisan serve --port=8811       # server dev (sessions DB, sqlite)
```

## Risiko yang diketahui (dan diterima untuk MVP)

- SQLite single-file: untuk produksi berat traffic, beralih ke MySQL (`ext pdo_mysql`).
- Word count oracle `str_word_count(strip_tags(...))` — perhitungan kata dengan bahasa
  campuran bisa beda ± beberapa kata dengan editor manusia; batas tetap server-enforced.
- Sitemap membaca DB langsung (789 URL: item + indeks + kota + kecamatan berartikel);
  bila item bertambah lewat admin, tidak perlu `catalog:import` ulang.
