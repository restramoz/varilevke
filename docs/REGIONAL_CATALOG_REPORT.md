# Laporan Katalog Wilayah — Varilevke

**Tanggal**: 2026-10-24
**Status**: Audit + perbaikan regional selesai — 81/81 test lolos (551 assertions), build produksi OK.

---

## 1. Root cause yang diperbaiki

| Temuan | Root cause | Perbaikan |
|---|---|---|
| `/wilayah`, `/artikel` render "plain HTML" (tanpa DOCTYPE/CSS) | View tidak `@extends('layouts.app')` | `regional/cities.blade.php` & `articles/index.blade.php` kini extends layout + `<x-breadcrumb>` |
| `/wilayah/{city}/pelatihan` 404 | Route kecamatan wildcard menelan segmen `pelatihan` | Urutan route dirombak; kecamatan **tidak** diikat router |
| Judul berisi `<?php echo e($cityInfix); ?>` | `@section('title')` mengevaluasi ekspresi PHP mentah | Title dikomposisi di blok `@php`, dikirim sebagai string |
| Kota/kecamatan me-render semua 272 kartu (511 KB) | Halaman wilayah men-skip filter katalog | Featured 6+6 card deterministik (`source_order`); sisa lewat index katalog wilayah |
| `x-collapse` mati | Alpine core tanpa plugin collapse | Plugin `@alpinejs/collapse` dimuat sebelum Alpine core |
| `badge-warning` tanpa style | Alias kelas tidak ada di stylesheet | Pakai `badge-caution` yang sudah ada |
| `<x-service-visual>` di halaman kecamatan | Komponen butuh data katalog, di halaman non-katalog | Dihapus |
| Script `mobile-menu-toggle` mati | Duplikasi handler; menu buatan Alpine | Dihapus dari layout |
| WA CTA compose di JS client | Konsistensi/fail-open di no-JS | Link dibangun server-side via `WhatsApp::link()` |

## 2. Gramatika URL & konteks wilayah

| Route | URI | Konteks |
|---|---|---|
| Indeks kota | `/wilayah` | — (search client-side) |
| Landing kota | `/wilayah/{city}` | kota |
| Landing kecamatan | `/wilayah/{city}/{kecamatan}` | kota + kecamatan |
| Katalog generik | `/pelatihan`, `/jasa` | — |
| Katalog kota | `/wilayah/{city}/{category}` | kota |
| Detail kota | `/wilayah/{city}/{category}/{item}` | kota |
| Katalog kecamatan | `/wilayah/{city}/{kecamatan}/{category}` | kota + kecamatan |
| Detail kecamatan | `/wilayah/{city}/{kecamatan}/{category}/{item}` | kota + kecamatan (badge, intro, WA prefill memuat kecamatan) |

Kunci kecil:
- Slug kecamatan hanya unik **per kota** → resolusi dilakukan di controller (scoped ke city), bukan router.
- Akses langsung tanpa konteks (mis. `/pelatihan/ahli-k3-umum`) tetap menampilkan halaman generik valid.
- Tidak ada duplikasi konten master per kota/kecamatan: item master disimpan sekali; konteks wilayah hanya mengubah wrapper halaman.

## 3. Verifikasi data (fresh, database dev saat audit)

| Metrik | Sumber | DB dev | Status |
|---|---|---|---|
| Provinsi | 38 | 38 | ✅ |
| Kota/Kabupaten | 514 | 514 | ✅ |
| Kecamatan | 7 288 | 7 288 | ✅ |
| Duplikat slug kota | — | 0 | ✅ |
| Duplikat slug kecamatan dalam 1 kota | — | 0 | ✅ |
| Cakupan kecamatan | — | 514/514 kota ter-cover | ✅ |
| Pelatihan (import) | 227 | 227 | ✅ |
| Jasa (import) | 45 | 45 | ✅ |

- File sumber `database/data/master-wilayah-bps.json` ≡ `master_wilayah_bps.json` (md5 identik `35e73dbf6eddd7fe54c766c4e5731f78`); 7 288 baris.
- Re-sync `RegionSeeder` idempoten (`firstOrNew`/skip by name) — aman dijalankan ulang.
- `REGION_SEED_PROVINCES="Jawa Timur"` di `.env` **tidak diubah**; ia membatasi bounding lingkungan baru. DB saat ini datang dari seed re-sync yang memuat semua provinsi (keputusan fase 5: expose semua dataset valid).
- **Jawa Timur** (subset): 38 kota/kabupaten, 666 kecamatan.

## 4. Keputusan desain minor

- Featured card di landing kota/kecamatan: 6 pelatihan + 6 jasa, `source_order` pertama (deterministik) — bukan render semua item.
- `View::composer('layouts.app')` di AppServiceProvider: `countPelatihan`, `countJasa`, `countCities`, `hotline` selalu tersedia.
- `x-breadcrumb` adalah komponen bar + container — bukan layout; jangan jadikan root halaman.
- Loop variable di sidebar form dinamai `$regionOption` agar tidak menimpa `$city`.
- Data kota tidak punya koordinat (kolom `lat/lng` tidak ada) → embed peta memakai query nama BPS, bukan koordinat.

## 5. Verifikasi halaman live (curl, dev server 127.0.0.1:8811)

Semua publik mengandung DOCTYPE + stylesheet `app-CtEwMD22.css` dan judul bersih:

```
/wilayah                                              200
/wilayah/kota-surabaya                                200
/wilayah/kota-surabaya/pelatihan                      200
/wilayah/kota-surabaya/pelatihan/ahli-k3-umum         200  ✅ "…- Pelatihan di Kota Surabaya"
/wilayah/kota-surabaya/asemrowo/pelatihan             200
/wilayah/kota-surabaya/asemrowo/pelatihan/ahli-k3-umum 200  ✅ "…- Pelatihan di Kecamatan Asemrowo, Kota Surabaya"
/artikel                                              200
/pelatihan/ahli-k3-umum (generik tanpa konteks)       200
```

## 6. Test & build

- **Suite**: `php artisan test --compact` → **81/81 passed, 551 assertions** (68 test lama tetap hijau + 13 baru `tests/Feature/RegionalContextTest.php`).
- **RegionalContextTest** (13 test): ketersediaan semua route baru, konteks terpelihara di halaman
  kota/kecamatan/katalog/detail, judul termuat, 404 pada slug invalid, konteks kecamatan ter-scoped per-kota.
- **Build**: `npm run build` OK → `assets/app-CtEwMD22.css` (84.75 kB, gzip 16.14 kB), `assets/app-BrhHhBPf.js` (0.30 kB); `public/build/manifest.json` valid; `@vite` pada layout men-show kedua entry; asset 200.

## 7. Sisa risiko / out-of-scope

1. **Database memuat semua 38 provinsi** — berbeda dengan disiplin "doorway farm" di komentar
   `config/regional.php`. Keputusan fase 5: expose semua dataset valid. Sitemap memuat 789 URL
   (home + 2 indeks kategori + 272 item + 514 landing kota; artikel & kecamatan berartikel menambah
   baris sendiri saat ada yang published).
2. **`review_required = 1`**: item "Jasa Lingkungan Karbon" menunggu keputusan manusia (pelatihan vs jasa).
3. **RegionSeeder + seed bounding**: jalankan `php artisan db:seed --class=RegionSeeder` di environment baru hanya mengisi provinsi di `.env` (default Jawa Timur), sedangkan DB dev sekarang memuat semua. Konsisten; hanya perlu diingat saat staging/prod.
4. Satu item katalog memuat karakter invisible U+2060 di nama (sumber asli, dipertahankan byte-per-byte).
