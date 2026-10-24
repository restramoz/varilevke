# Laporan Porting Generator & Editor Artikel — Varilevke

Tanggal: 2026-10-24  
Proyek Target: `/root/varilevke`  
Proyek Sumber: `/root/trainingahlik3umum` (Read-Only)  
Status: **SELESAI & TERVERIFIKASI (104 Tests Passed, 611 Assertions)**

---

## 1. Ringkasan Eksekutif

Sub-sistem generator dan editor artikel dari proyek referensi `trainingahlik3umum` telah diporting secara menyeluruh dan disesuaikan (*adapted*) ke dalam arsitektur Varilevke. Implementasi mencakup:
1. Layanan backend cerdas `OllamaService` dengan arsitektur modular (`ArticlePromptBuilder`, `ImageUploadService`).
2. Editor visual modern berbasis Jodit 4.1.16 dengan antarmuka gelap (*dark regulatory UI* `bg-regulatory-900`/`card-k3`), live preview, filter wilayah berjenjang (Provinsi, Kota/Kabupaten, Kecamatan), dan generator prompt Google Flow.
3. Sistem prompting multi-kategori yang membedakan artikel Pelatihan (227 item), Jasa/Konsultasi (45 item), dan Artikel Regional berbasis BPS.
4. Penegakan batas kata yang ketat (minimum 6.000 kata, maksimum 10.000 kata, default 8.000 kata) dengan loop kelanjutan (*bounded continuation*) dan deteksi konten berulang.
5. Injeksi otomatis 5 callout WhatsApp kontekstual menggunakan 4 nomor resmi terpusat dari `config/contacts.php`.
6. Alur preview draf yang aman: hasil AI tidak pernah disimpan langsung ke database sebelum diperiksa dan disimpan secara sadar oleh admin yang terotentikasi.

---

## 2. Pemetaan & Arsitektur Berkas yang Diporting

| Komponen Sumber (`trainingahlik3umum`) | Komponen Target (`varilevke`) | Penyesuaian Arsitektural di Varilevke |
|---|---|---|
| `app/Services/OllamaService.php` | `app/Services/OllamaService.php` | Mendukung Ollama native (`/api/chat`) dan OpenAI-compatible endpoint. Integrasi dengan `HtmlSanitizerService` dan injeksi WhatsApp terpusat. |
| (Logika prompt di OllamaService) | `app/Services/Ollama/ArticlePromptBuilder.php` | Pemisahan tanggung jawab (SRP). Membedakan prompt Pelatihan, Jasa, dan Regional dengan batasan negatif yang ketat. |
| `app/Services/ImageUploadService.php` | `app/Services/ImageUploadService.php` | Porting penuh validasi gambar: penolakan SVG/vektor, verifikasi MIME (JPG, PNG, WebP), ukuran maks 5MB, `getimagesize()` check, dan proteksi `.htaccess`. |
| `app/Http/Controllers/Admin/ArticleController.php` | `app/Http/Controllers/Admin/ArticleAdminController.php` | Menambahkan endpoint `aiGenerate()` dan `uploadImage()` tanpa merusak endpoint lama `generatePreview()`. |
| `database/migrations/2026_03_08_000000_add_image_brief_to_articles.php` | `database/migrations/2026_10_24_065800_add_image_brief_to_articles_table.php` | Menambahkan kolom `image_brief` (JSON nullable) pada tabel `articles`. |
| `app/Models/Article.php` | `app/Models/Article.php` | Penambahan casting `'image_brief' => 'array'` dan penambahan ke `$fillable`. |
| `resources/views/admin/articles/create.blade.php` | `resources/views/admin/articles/create.blade.php` | Jodit 4.1.16, dark regulatory theme, filter provinsi, pencarian kota dinamis, AJAX kecamatan, Google Flow prompt box, dan split-screen live preview. |
| `resources/views/admin/articles/edit.blade.php` | `resources/views/admin/articles/edit.blade.php` | Jodit 4.1.16, dark theme, preview gambar unggulan, prompt box, dan real-time live preview. |
| `resources/views/admin/articles/index.blade.php` | `resources/views/admin/articles/index.blade.php` | Filter status, pencarian judul/topik, badge kata, indikator wilayah & katalog, serta aksi view/edit/delete. |
| `resources/views/admin/layout.blade.php` | `resources/views/admin/layout.blade.php` | Menambahkan tag `<meta name="csrf-token">`, `@vite(['resources/css/app.css', 'resources/js/app.js'])`, `@stack('styles')`, dan `@stack('scripts')`. |
| `routes/web.php` | `routes/web.php` | Rute bernama `admin.articles.ai-generate` dan `admin.upload.image` di bawah middleware `['auth', 'admin']`. |
| `.env.example` & `config/services.php` | `.env.example` & `config/services.php` | Konfigurasi `services.ollama` dengan fallback ke `services.ai` tanpa mengekspos kunci rahasia. |

---

## 3. Fitur Editor & Alur Kerja yang Dipertahankan

1. **Jodit Editor 4.1.16:**
   - Toolbar lengkap: bold, italic, underline, strikethrough, font, fontsize, brush, paragraph, align, list, indent, link, image, table, source, fullsize, undo, redo, hr, eraser.
   - Penanganan unggah gambar terintegrasi melalui endpoint `admin.articles.upload-image` (atau fallback `admin.upload.image`) dengan token CSRF.
   - Styling responsif tabel artikel K3 (`overflow-x-auto`, tabel bergaris dengan kelas `prose-table`).
2. **UI Dark Regulatory System:**
   - Konsisten dengan palet warna Varilevke: `bg-regulatory-900`, `border-regulatory-700`, teks `text-regulatory-100`, aksen teal `text-accent`.
3. **Filter Wilayah Berjenjang:**
   - Dropdown Provinsi memfilter daftar Kota/Kabupaten secara instan.
   - Pencarian real-time untuk Kota/Kabupaten.
   - Pemanggilan AJAX asinkron untuk daftar Kecamatan berdasarkan Kota yang dipilih (`GET /admin/cities/{city:slug}/kecamatans`).
4. **Google Flow Prompt Generator:**
   - Panel akordeon interaktif yang menyusun prompt pencarian mendalam yang dapat disalin dengan satu klik (Copy Button) untuk riset pendukung di luar editor.
5. **Real-time Live Preview:**
   - Kolom pratinjau langsung yang merefleksikan judul, ringkasan, gambar brief, dan konten HTML terformat saat admin mengetik atau setelah AI menghasilkan draf.

---

## 4. Perilaku & Perbaikan OllamaService

Selama proses audit dan adaptasi, beberapa kelemahan pada implementasi dasar telah diperbaiki:
1. **Konfigurasi Lingkungan:**
   - Parameter disetel melalui `config/services.php` (`services.ollama.base_url`, `services.ollama.model`, `services.ollama.api_key`, `services.ollama.timeout`, `services.ollama.max_attempts`).
   - Tidak ada kredensial atau URL yang di-hardcode.
2. **Koneksi dan Penanganan Error:**
   - Retry terbatas (*bounded retry*) hingga 3 kali hanya untuk kegagalan transien (ConnectionException, timeout, error 5xx).
   - Penolakan langsung tanpa retry untuk error klien HTTP 4xx (401, 403, 404, 422).
   - Penanganan timeout eksplisit (default 180 detik).
3. **Parsing JSON Ketat (*Strict Extraction & Repair*):**
   - Membersihkan pembungkus markdown code fences (````json ... ````).
   - Otomatis memperbaiki karakter escape tidak valid dari LaTeX atau backslash.
   - Ekstraksi objek JSON seimbang (*balanced JSON object extraction*) jika respons model menyertakan teks pengantar.
   - **Prinsip Keamanan Output:** Jika format model rusak dan tidak dapat dipulihkan secara sah, permintaan gagal secara eksplisit dengan pesan informatif — tidak pernah mengembalikan draf sukses dari teks mentah yang korup.
4. **Injeksi WhatsApp Terpusat:**
   - Mengambil 4 nomor resmi dari `config/contacts.php` (`08118500177`, `081399810272`, `087894580806`, `087894580649`).
   - Memasukkan tepat 5 callout WhatsApp yang didistribusikan secara proporsional di antara tag `</p>` pada konten artikel.

---

## 5. Sistem Prompt Multi-Kategori & Batasan Faktual

Sistem prompt diimplementasikan dalam `App\Services\Ollama\ArticlePromptBuilder` dengan pemisahan tegas:

### A. Artikel Pelatihan (`pelatihan`)
- Berfokus pada kompetensi kerja, silabus/materi inti, persyaratan peserta, metode evaluasi, dan relevansi industri.
- Katalog item **"Jasa Lingkungan Karbon"** tetap diklasifikasikan sebagai `pelatihan` dengan status `review_required = true` sesuai arahan katalog master.

### B. Artikel Jasa & Konsultasi (`jasa`)
- Berfokus pada ruang lingkup teknis, tahapan pelaksanaan pekerjaan, metodologi pengukuran/audit, deliverable/laporan teknis, dan kepatuhan standar industri.

### C. Artikel Kontekstual Wilayah (*Regional Context*)
- Mengintegrasikan hierarki resmi BPS: Provinsi, Kota/Kabupaten, dan Kecamatan.
- Menjelaskan profil sektor industri lokal, potensi bahaya lingkungan kerja di daerah tersebut, dan rujukan pemenuhan norma K3.

### Batasan Faktual Negatif (*Strict Negative Constraints*)
Prompt secara eksplisit melarang pembuatan fakta palsu:
- DILARANG mengklaim akreditasi atau penunjukan Kemnaker RI / BNSP kecuali diverifikasi di data internal.
- DILARANG mengarang nomor SKP, nomor regulasi palsu, atau jaminan kelulusan 100%.
- DILARANG mengarang harga, jadwal pelatihan, alamat kantor cabang regional fiktif, atau nomor telepon palsu selain kontak resmi.
- DILARANG mengarang testimoni, statistik klien fiktif, atau klaim verifikasi sepihak.

---

## 6. Penegakan Batas Kata (*Word-Count Enforcement*)

Sesuai spesifikasi Varilevke:
- **Batas Minimum:** 6.000 kata.
- **Batas Maksimum:** 10.000 kata.
- **Target Default:** 8.000 kata.

### Mekanisme Loop Kelanjutan Terbatas (*Bounded Continuation*):
1. Menghitung jumlah kata nyata dari teks bersih (`strip_tags` dan `html_entity_decode`).
2. Jika draf awal < 6.000 kata:
   - Sistem menjalankan loop kelanjutan terarah maksimal 6 iterasi.
   - Setiap iterasi meminta bagian lanjutan yang belum dibahas tanpa mengulang konten sebelumnya.
   - Dilengkapi deteksi duplikasi: jika model mengulang teks yang sudah ada, proses dihentikan dengan aman.
3. Jika setelah upaya kelanjutan artikel masih < 6.000 kata, draf ditolak secara eksplisit dengan pesan status 500 informatif.
4. Jika artikel melebihi 10.000 kata, sistem memangkas teks secara rapi pada batas elemen blok HTML (`<p>`, `<table>`, `<ul>`).

---

## 7. Hasil Pengujian & Pembuatan Aset Produksi

### A. Suite Pengujian Otomatis (PHPUnit)
Eksekusi pengujian penuh membuktikan integritas seluruh aplikasi:
```bash
php artisan test --compact
```
**Hasil:**
- **Tests:** 104 passed
- **Assertions:** 611 assertions
- **Status:** 100% HIJAU (0 gagal, 0 error)

Rincian modul pengujian yang baru diporting dan diverifikasi:
1. `tests/Unit/OllamaServiceTest.php`: 10 tests, 36 assertions (parsing sukses, ekstraksi field, penolakan < 6000 kata, penolakan > 10000 kata, deteksi loop duplikasi, timeout & HTTP error, sanitasi LaTeX, injeksi 5 WhatsApp callouts).
2. `tests/Unit/OllamaPromptTest.php`: 5 tests, 5 assertions (prompt pelatihan, prompt jasa, prompt wilayah BPS, pembatasan negatif brand luar, klasifikasi aman Jasa Lingkungan Karbon).
3. `tests/Feature/AiGenerateArticleTest.php`: 8 tests, 19 assertions (otentikasi 401, autorisasi admin 403, validasi kata minimum/maksimum 422, validasi slug kota 422, respons draf sinkron tanpa queue/polling, penanganan error 500, relasi katalog & kota).
4. `tests/Feature/ArticleAdminCrudTest.php`: 11 tests, 37 assertions (CRUD admin, sanitasi HTML, proteksi CSRF, keunikan slug).
5. `tests/Feature/ArticleGeneratePreviewTest.php`: 9 tests, 35 assertions (kompatibilitas generator preview legacy).

### B. Standardisasi Gaya Kode (Laravel Pint)
```bash
vendor/bin/pint --format agent
```
**Hasil:**
- 73 berkas diperiksa, seluruh kode memenuhi standar gaya Laravel (0 dirty files).

### C. Build Aset Frontend (Vite)
```bash
npm run build
```
**Hasil:**
- Vite 8.3.5 berhasil mengompilasi bundel produksi tanpa error:
  - `public/build/assets/app-9w8H9y65.css`: 47.45 kB (gzip: 8.94 kB)
  - `public/build/assets/app-D1nF2xYc.js`: 59.39 kB (gzip: 21.65 kB)
  - `public/build/manifest.json`: 1.29 kB

---

## 8. Status Verifikasi Live Ollama Server

- **Status Server Lokal:** Pada lingkungan dev lokal/CI saat ini, tidak ada daemon server Ollama eksternal yang aktif secara publik.
- **Strategi Pengujian:** Seluruh suite pengujian otomatis (`OllamaServiceTest`, `AiGenerateArticleTest`, `ArticleGeneratePreviewTest`) menggunakan simulasi HTTP (`Http::fake()`) dan mocking service terisolasi. Hal ini memastikan pengujian dapat dijalankan kapan saja di lingkungan build mana pun tanpa ketergantungan pada server AI aktif.
- **Konfigurasi Produksi:** Tempat penampung variabel lingkungan yang aman telah disiapkan di `.env.example`:
  ```dotenv
  OLLAMA_BASE_URL=http://localhost:11434
  OLLAMA_MODEL=mistral:latest
  OLLAMA_API_KEY=
  OLLAMA_TIMEOUT=180
  OLLAMA_MAX_RETRIES=3
  ```
  Administrator hanya perlu mengisi variabel tersebut pada `.env` server produksi ketika server Ollama atau OpenAI-compatible proxy tersedia.

---

## 9. Kesimpulan

Porting sub-sistem generator dan editor artikel dari `trainingahlik3umum` ke `varilevke` telah selesai secara paripurna. Repositori referensi `/root/trainingahlik3umum` tetap bersih dan sama sekali tidak berubah (*working tree clean*). Seluruh fungsionalitas editor modern, prompting spesifik katalog, penegakan batasan kata, dan sanitasi keamanan telah aktif dan diverifikasi dengan 104 pengujian sukses di Varilevke.
