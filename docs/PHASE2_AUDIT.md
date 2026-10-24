# Audit Fase 2 — Varilevke ( basis: 2026-10-23 )

Semua angka di bawah dibaca langsung dari kode/database hari ini, bukan dari dugaan. `/root/trainingkota` dibaca read-only.

## 1. State repo & lingkungan

- Git: branch `main`, hanya `README.md` modified; sembilan belas path lain untracked (hasil unggahan fase 1). Semua pekerjaan ada; tidak ada file yang dihapus.
- Stack: Laravel 13.17 / PHP 8.5 / SQLite; Tailwind **v4** (`@import 'tailwindcss'` + `@theme`, plugin `@tailwindcss/vite`), font Instrument Sans via `bunny()` di `vite.config.js`. Tidak ada framework JS.
- Baseline test: `php artisan test --compact` → **68/68 passed, 503 assertions**.

## 2. Struktur aplikasi saat ini (apa yang sudah berjalan)

| Bagian | Fakta | Sumber |
| --- | --- | --- |
| Katalog | 272 item di DB: pelatihan 227, jasa 45; improrter idempoten, manifest di `docs/source/catalog-manifest.json`; `Jasa Lingkungan Karbon` tetap `pelatihan` + `review_required=1` | tinker + `CatalogImporter` |
| Wilayah | DB dev: 38 kota & 666 kecamatan (hanya Jawa Timur, dibatasi `REGION_SEED_PROVINCES`) | tinker |
| Region seed | `RegionSeeder` idempoten per `bps_code`, membaca `database/data/master-wilayah-bps.json` (md5 identik dengan `master_wilayah_bps.json` di root: `35e73dbf…`) | config/regional.php |
| Skema data master | list flat 7.288 baris: `Provinsi, Kode Provinsi, Kabupaten/Kota, Kode Kab/Kota, Jenis Wilayah, Kecamatan, Kode Kecamatan` | inspeksi JSON |
| WhatsApp | 4 nomor string ber-awalan-0 di `config/contacts.php`; helper `App\Support\WhatsApp` (08→62, wa.me) | config + tests |
| Artikel | 0 artikel di DB dev; lamngsung pakai workflow AI draft-only | tinker, `ArticleGeneratorService` |
| AI | `AiClient` + generator terbagi-bagian (8 chunk), strict JSON, 6.000–10.000 kata, **tidak pernah menulis DB** dari endpoint generate-preview | kode + test |
| SEO | `SeoController` sitemap (semua 272 slug + artikel + kota; kecamatan hanya bila ada artikel) + robots + canonical/OG di `partials/seo` | kode |
| Admin | auth `users.is_admin`, CRUD katalog/artikel/settings; layout admin masih tema terang slate | views |
| Views | ringkas: home 32 baris, catalog index 28, catalog show 42, layout 40, CSS 9 baris — tema terang teal | views |

## 3. Sistem desain TrainingKota (hasil inspeksi, bukan tebakan)

Pola diambil dari `tailwind.config.js`, `app.css`, `layouts/app.blade.php`, `home.blade.php`, `service-detail.blade.php`, dan `DESIGN.md`.

- **Palet gelap industrial**: background `#070D18`, panel `#0F2038`, card `#0B1526`, border `#1E324E`/`#142338`, aksen hijau `#0D7A5F`/`#10B981`, amber `#D97706`/`#F59E0B`, sky `#38BDF8`, teks `#F1F5F9/#94A3B8/#64748B`, WhatsApp `#25D366`.
- **Tipografi**: Space Grotesk (judul/huruf kapital) + IBM Plex Sans (badan), `label-caps` 11px spacing 0.08em.
- **Geometri**: strict 2px radius semua elemen, tanpa shadow.
- **Komponen**: top bar operasional, header sticky + logo SVG + nav uppercase + hamburger (Alpine), footer 4 kolom, `btn-primary/btn-secondary/btn-whatsapp`, badge chips, `input-k3`, `panel-k3/card-k3`, tabel gelap zebra-stripe, FAQ accordion, TOC artikel.
- **Halaman layanan**: bar breadcrumb → header (badge + nama + 2 CTA kontekstual per kategori) → *spec box* → konten spesifik kategori (pelatihan: silabus + sasaran; kajian: metodologi + scope; jasa: alur proses + deliverables) → FAQ → artikel/side panel → sidebar (form kontak + jasa terkait + artikel terkait).

**Yang sengaja TIDAK disalin dari TrainingKota**: klaim kredensial ("Kemnaker RI Certified", "BNSP Accredited", pricing/durasi resmi, "5 menit respon", nama produk pihak ketiga, badge brand lain) — untuk Varilevke ini belum terverifikasi dari sumber mana pun, sehingga dipakai hanya sebagai komponen visual dengan label status faktual (jumlah katalog, 4 kontak WA).

## 4. Yang akan diadaptasi → rencana perubahan

| Area Varilevke | Adaptasi |
| --- | --- |
| `app.css` + `vite.config.js` | token gelap TK → `@theme` Tailwind v4; bunny swap Instrument Sans → Space Grotesk + IBM Plex Sans; kelas komponen (btn/badge/input/panel/label-caps/article-prose dark) |
| `app-layout` | top bar + header sticky + hamburger (vanilla JS, tanpa Alpine CDN) + footer 4 kolom; route & nama tetap |
| home | hero industrial + quick inquiry card (deep-link WhatsApp, bukan form palsu) + 2 pilar (karena Varilevke = 2 kategori) + blok wilayah/artikel |
| catalog index/show | panel + panel detail TK; show mendapat struktur konten spesifik kategori (lihat §5) |
| articles, regional, breadcrumb, wa-contacts | restyle ke sistem gelap, chips kota, tabel artikel bergulir |
| admin | konsisten gelap (kelas swap), dapat editor konten item (§5) |

## 5. Rencana sistem konten (Fase 3–4) — keputusan desain

- Tabel baru **`catalog_contents`** (migrasi aditif): `catalog_item_id` (unique FK), `status` (`draft|review|published`, default draft), `fields` JSON terstruktur per tipe konten, `edited_at`, timestamps. Identitas katalog tidak tersentuh.
- Builder konten berbasis kata kunci nama (`TrainingContentBuilder` untuk pelatihan, `ServiceContentBuilder` untuk jasa) menghasilkan field berbeda per item dengan rumusan hati-hati; **tanpa** klaim akreditasi/harga/durasi/jaminan.
- Field "hanya bila terkonfirmasi" (format pelaksanaan, sertifikat, assessment) dibiarkan `null` → tidak dirender; dilaporkan sebagai *missing/pending verification* di laporan cakupan.
- Command `catalog:content:generate` (idempoten, tidak pernah menghapus) + `catalog:content:coverage {--export}` → `docs/SERVICE_CONTENT_COVERAGE.md`.
- Admin: editor field per item + status + tombol "draf AI" (endpoint preview draft-only, 422 bila AI tidak dikonfigurasi) — melengkapi sistem yang ada, tidak menggantinya.
- Regional: default seed diperluas ke seluruh provinsi dari file (tetap bisa dibatasi via `REGION_SEED_PROVINCES`); sitemap tetap menutup kecamatan tanpa artikel (anti doorway farm).

## 6. Risiko & batasan

- Tidak ada aset TrainingKota (gambar/favicon) yang disalin; ikon hanya SVG inline generik.
- DB dev saat ini menahan 1.000+ baris wilayah Jawa Timur; migrasi konten bersifat aditif, seeder wilayah tidak pernah truncate → pembuatan provinsi lain aman.
- Screenshot lintas-proyek: belum ada harness browser automation; QA via kode + HTTP + pengujian fitur, dan keterbatasan ini dinyatakan di laporan akhir.
