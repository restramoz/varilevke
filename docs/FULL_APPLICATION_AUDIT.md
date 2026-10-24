# FULL_APPLICATION_AUDIT — Varilevke

Tanggal audit: 2026-10-24 · Model audit penuh memakai verifikasi HTTP nyata (`php artisan serve` + curl), bukan hanya asumsi terhadap kode.

## 0. Metode & status proyek

- Git: `main`, 2 commit (upload awal). Seluruh kode aplikasi (app/, routes/, resources/, database/, tests/) berstatus **untracked/uncommitted**; hanya README.md yang dimodifikasi-tracked.
- Baseline lama: 68 test / 503 assertions PASS (dokumen lama). Diulang selama audit ini — lihat §8.
- Referensi (read-only, tidak dimodifikasi): `/root/trainingkota` (desain/URL regional), `/root/trainingahlik3umum` (struktur konten layanan).
- Dev DB: SQLite `database/database.sqlite`; `.env` menyebut `REGION_SEED_PROVINCES="Jawa Timur"` namun **DB dev saat ini memuat 38 provinsi** (seeding penuh dilakukan sebelumnya).

## 1. Data regional — Sumber vs DB (terverifikasi)

| Metrik | master_wilayah_bps.json (7.288 baris) | DB cities | DB kecamatans |
| --- | --- | --- | --- |
| Provinsi | 38 | 38 | — |
| Kabupaten/Kota unik (kode BPS) | 514 | 514 | — |
| Kode kecamatan unik | 7.288 (0 duplikat) | — | 7.288 |
| Kode klasifikasi ganda per kode kab/kota | 0 | — | — |
| Pasangan (kab, kecamatan) ganda | 0 | — | — |
| Slug kota ganda | — | 0 | — |
| kecamatan tanpa city_id | — | — | 0 |

**Kesimpulan integritas: DB = sumber, tanpa duplikat/paritas hilang.** Alasan historis "CI menampilkan lebih sedikit wilayah": `config/regional.php` membatasi seeder via `REGION_SEED_PROVINCES` (default `Jawa Timur`) — jika produksi menyetel DB dengan batas itu, jumlah publik lebih kecil daripada sumber. DB dev tidak terkena batas tersebut (seeding penuh). Sitemap hanya memuat kecamatan berartikel → bukan indikator kehilangan data.

## 2. Temuan per subsistem (root cause terkonfirmasi = ✅ via uji)

### 2a. Renderer "plain HTML" — ✅ ROOT CAUSE
- `/wilayah` dan `/artikel` di-render sebagai **fragmen tanpa dokumen**: tidak ada `<!DOCTYPE>`, `<head>`, tag Vite. curl menunjukkan output dimulai dengan `<nav aria-label="Breadcrumb">`.
- Akar: `regional/cities.blade.php` dan `articles/index.blade.php` memakai `<x-breadcrumb>` **sebagai layout** (component bukan layout — hanya breadcrumb bar + sebuah div). View tepat ini diberikan langsung ke `view()` tanpa shell.
- Semua view lainnya (`home`, `catalog/*`, `regional/city`, `regional/kecamatan`, `articles/show`) `@extends('layouts.app')`.

### 2b. Konflik rute `wilayah/` — ✅ ROOT CAUSE
- HTTP nyata: `/wilayah/kota-surabaya/pelatihan` → 404.
- Akar: `Route::prefix('wilayah')->group` mendeklarasikan `wilayah/{city}/{kecamatan:slug}` SEBELUM grup `wilayah/{city}/{category}` & `{category}/{item}`. Laravel mencocokkan berurutan → segmen `pelatihan` ditelan `{kecamatan:slug}` → `firstOrFail()` → 404. Akibatnya, katalog yang dibuka dari halaman kota (`regional.city.catalog.show`) sering 404 dan pengguna kehilangan konteks.
- Rute ditulis ulang tanpa duplikasi: `regional.kecamatan`, `regional.city.catalog.*`, `regional.kecamatan.catalog.*`.

### 2c. Konteks kota hilang dalam katalog — klaim tertulis, gagal nyata
- `CatalogController::cityIndex/cityShow` ada dan view berkait boleh rendering `{{ $city }}` namun: (i) 404 dari bagian (2b); (ii) `cityShow` tidak meneruskan `$cities` untuk sidebar "(Pilih Wilayah)" → dropdown kosong; (iii) item terkait memakai `catalog.show` generik → konteks hilang; (iv) tidak ada jalur konteks **kecamatan** ke detail (status kecamatan hilang).
- Catatan: sidebar WA pada halaman generik memakai dropdown pilihan wilayah; saat konteks tersedia, tombol WA harusnya memuat prefill regional — bukan lagi "pilih wilayah".

### 2d. Judul halaman rusak — ✅ terkonfirmasi
- `catalog/index.blade.php:31` dan `catalog/show.blade.php:45` memakai `@section('title', 'Katalog {{ ucfirst($category) }}…')` maka Blade mengkompilasi interpolasi DI DALAM string → tag judul tercetak sebagai `<?php echo e($cityInfix); ?>` (curl-terverifikasi); view lain dengan pola identik ikut diperiksa.

### 2e. DOM dump 272–272 kartu di halaman kota/kecamatan — ✅
- `regional/city.blade.php` men-render **seluruh 227 pelatihan + 45 jasa** (lebih dari 6/3 hanya disembunyikan via class `hidden`); kecamatan juga identik. Ukuran HTTP: 511 KB untuk halaman kota (dan kueri berulang pada tiap render). Katalog indeks 480 KB (pola serupa).
- Kondisi yang diminta tugas: "Jangan duplikat 272 entri per kota/kecamatan". Solusinya: porsi "featured + link katalog kota" yang lulus ke `cityIndex`.

### 2f. Ketidakvalidan kelas/komponen
- `badge-warning` dipakai di `regional/kecamatan.blade.php` — tidak exist di `app.css` → badge polos.
- `x-collapse` dipakai beberapa FAQ/akordeon (catalog index, kecamatan) — **package @alpinejs/collapse tidak dimuat** → panel FAQ tidak bisa expand.
- `x-service-visual :item="$kecamatan"` di halaman kecamatan — komponen dimaksud untuk item katalog; untuk kecamatan menampilkan "Skema Teknis" entitas `default` tanpa makna.
- Atribut JSON-LD rusak: `<script type="application/ld+json>` (tanpa quote penutup) di `regional/city.blade.php` & `catalog/show.blade.php`.
- Layout memiliki script `mobile-menu-toggle` yang merujuk ID DOM yang **tidak pernah dibuat** (dead code).

### 2g. Katalog
- Jumlah item: **272 = 227 pelatihan + 45 jasa** (importer idempoten, DatabaseSeeder idem — diuji lihat CatalogImportTest).
- Keutuhan 227/45 terjaga: nama persis dipertahankan (termasuk U+2060 pada 1 item), `source_order` 1–227/1–45 unik per kategori.
- Pencarian: `?q=` di-level controller berfungsi (Server-side, diuji). Filter kategori: via judul `request()->route('category')`. Paginasi: tidak ada (DOM-filter client-side, pola TrainingKota). Detail: `$content` fallback ke teks generik, tidak membuat data.
- `Jasa Lingkungan Karbon` masih `review_required=1` menunggu keputusan manusia (tidak diubah dalam audit).

### 2h. Artikel & SEO
- Artikel 0 baris di DB dev (ok; ditest via factory).
- `/artikel` fragmen (2a). `/artikel/{slug}` baik (layout benar, sanitasi input admin; tabel dibungkus oleh sanitizer).
- Sitemap/robots dinamis baik; kecamatan tidak berartikel sengaja tidak masuk.
- Kanonikal & OG ada; canonical pakai `url()->current()` konsisten dengan host.

### 2i. Admin
- Auth: `auth` + `admin` (EnsureUserIsAdmin); guest redirect; non-admin 403 (diuji AdminAuthTest).
- AI generate-preview test menjamin tidak pernah menulis DB.
- Cadangan validasi ada; tidak ada tindakan destruktif tanpa otentikasi.

### 2j. Frontend
- Vite build produk akhir lama — perlu verifikasi build selesai (`npm run build`) setelah perbaikan.
- Font ganda: layout memuat Google Fonts **dan** bunny() Vite — tidak fatal namun relokasi akan dikonsolidasi. (biarkan = low priority)
- Focus styles ada (`:focus-visible`), label input-k3 ada; ukuran input 44px.

## 3. Alur pengguna rusak / tidak lengkap (daftar)

1. `/wilayah` – muncul sebagai plain HTML (2a) tanpa style/navigasi.
2. `/artikel` – muncul plain HTML (2a).
3. Halaman kota → kartu katalog → detail: detail 3-segmen berfungsi (200), tapi rute tidak konsisten & sidebar wilayah kosong; selain itu konteks kecamatan hilang di detail.
4. Halaman kota → "Lihat katalog kota ini" (`/wilayah/{city}/{category}`) → **404** (2b).
5. Halaman kecamatan → kartu katalog → detail: menuju `regional.city.catalog.show` (konteks kota saja; kecamatan hilang di detail; tidak ada badge/label kecamatan pada halaman detail).
6. Judul identik & berisi artifact `<?php echo` pada halaman katalog kategori pelatihan/jasa (2d).
7. Halaman kota memuat seluruh 272 kartu ke dalam dokumen — memperlambat & urutan tidak informatif.
8. Tautan item terkait pada halaman detail kota menuju konteks lintas katalog (2c-iii).
9. FAQ/akordeon pada beberapa halaman tidak expandable (2f).
10. Badge `badge-warning` tanpa style (2f).

## 4. File yang terlibat

| Area | Berkas |
| --- | --- |
| Rute | `routes/web.php` |
| Controllers publik | `app/Http/Controllers/RegionalController.php`, `CatalogController.php`, `PublicArticleController.php`, `HomeController.php`, `SeoController.php` |
| Views | `resources/views/regional/{cities,city,kecamatan}.blade.php`, `articles/index.blade.php`, `catalog/{index,show}.blade.php`, `layouts/app.blade.php`, `components/{breadcrumb,whatsapp-contacts}.blade.php` |
| CSS | `resources/css/app.css` (tokens, komponen; 18px token sudah definisi) |
| Seeder | `config/regional.php`, `database/seeders/RegionSeeder.php` (idempoten, non-destruktif) |
| Tests baru | 1 file: `tests/Feature/RegionalContextTest.php` (13 test) |

## 5. Rencana perbaikan (urutan eksekusi)

1. **Rute**: deklarasikan `city.catalog.*` sebelum kecamatan; tambahkan jalur konteks kecamatan `/wilayah/{city}/{kecamatan}/{category}/{item}` (+ indeks `/wilayah/{city}/{kecamatan}/{category}`). Validasi kota/pasangan kecamatan via model binding eksplisit (slug di scope city).
2. **Layout**: `regional/cities` & `articles/index` → `@extends('layouts.app')`, breadcrumb via `<x-breadcrumb>` sebagai isi konten.
3. **Judul**: perbaiki interpolasi string `@section('title')` (concatenasi PHP, bukan `{{ }}`).
4. **Regional index**: tetap dikelompokkan per provinsi + pencarian klien + paging/anchor `?prov=` (daftar 514 kota tetap tersedia, ambil via query & layout konsisten).
5. **Halaman kota/kecamatan**: ganti dump 272 kartu dengan (a) hero terstruktur TrainingKota, (b) deskripsi layanan singkat, (c) kartu "featured" 6+6 deterministik (pakai `source_order` teratas), (d) link "Lihat semua (N) → katalog kota / kecamatan", (e) bagian daftar kecamatan (kota) / kecamatan bertetangga (kecamatan), (f) artikel, FAQ, CTA kontekstual.
6. **Detail kontekstual**: controller kota/kecamatan menampilkan variabel wilayah; view menampilkan badge wilayah, breadcrumbs, intro region, WA prefill berwilayah, related item memakai konteks, back-link eksplisit, tetap menampilkan konten master; tanpa konteks — halaman generik valid.
7. **Perbaikan kecil**: alias `badge-warning`/pakai `badge-caution`; muat plugin CDN `x-collapse`; hilangkan `<x-service-visual>` dari kecamatan; hilangkan script layout mati; sidebar "$cities" di cityShow; kandungan kategori memeriksa jalur baca umum (catatan: tidak ada data office/rekomendasi buatan).
8. **Tests**: tambah RegionalContextTest (route baru, konteks terpelihara, 227/45 utuh, stabilitas 404, konteks kota kembali).

## 6. Kriteria verifikasi

- Setiap halaman publik (home, /pelatihan, /jasa, /artikel, /wilayah, luaran index kota/kecamatan, detail generik/detail kota/detail kecamatan) mengandung `<link rel="stylesheet" href="…/build/assets/app-…css">` → layouts terkait konsisten.
- `/wilayah/{city}/{category}` dan kontekstual kecamatan kembali 200 + benar nama kota/kecamatan.
- Judul halaman tidak mengandung `<?php echo`; tidak ada "badge-warning" tanpa style; JSON-LD valid.
- CMS: 227/45 utuh post-audit (query ulang).
- `npm run build` menghasilkan asset baru dan manifest valid.
- Suite full test semua lulus.
- Tidak ada referensi proyek diubah; `.env` tidak diubah; tidak ada lap delete DB.

## 7. Out-of-scope & sisa risiko

- Banyak provinsi → halaman kota besar; dilindungi: kota tetap direktor biner, tak garasi 272 kartu.
- Kecamatan tidak berartikel → tetap `noindex` kecuali konten benar-benar publik; sitemap sudah demikian.

## 8. Status eksekusi — terkini

Perbaikan seluruh temuan di atas **selesai dan terverifikasi** (2026-10-24):

- Kriteria verifikasi §6 — semua lolos dengan bukti eksekusi nyata (bukan simulasi):
  - Layout empat halaman: DOCTYPE + stylesheet `app-CtEwMD22.css` hadir di home, /pelatihan,
    /jasa, /artikel, /wilayah, index & detail kota/kecamatan/generik (curl).
  - Keseluruhan gramatika wilayah 200 dengan konteks benar: city, kecamatan, katalog kota,
    katalog kecamatan, detail kota ("…di Kota Surabaya"), detail kecamatan
    ("…di Kecamatan Asemrowo, Kota Surabaya"); generik `/pelatihan/ahli-k3-umum` 200.
  - Judul bersih (tanpa artifact PHP), JSON-LD valid, `badge-warning` tinggal sejarah.
  - Data utuh post-audit: 227 pelatihan + 45 jasa; 514 kota / 7 288 kecamatan / 38 provinsi
    = sumber BPS (md5 identik), 0 duplikat slug.
  - `npm run build` OK + manifest valid + asset 200.
  - Suite penuh **81/81, 551 assertions PASS** (68 lama tetap hijau + 13 RegionalContextTest).
  - Proyek referensi read-only tetap tak tersentuh; `.env` tidak berubah.
- Rincian perbaikan regional: `docs/REGIONAL_CATALOG_REPORT.md`.
- Sisa risiko terbuka hanya yang dikenal di §7 dan §7-Regional — tidak ada temuan baru yang lolos.
