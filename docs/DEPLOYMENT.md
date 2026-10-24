# Panduan Deployment Varilevke

## 1. Kebutuhan server

| Butuh | Keterangan |
| --- | --- |
| PHP ≥ 8.2 (dev: 8.5.4) | Ekstensi wajib: `pdo_sqlite` (default), `mbstring`, `dom`/`libxml`, `openssl`, `fileinfo`, `curl`. Untuk MySQL: `pdo_mysql`. |
| Composer | Untuk CLI deployment; untuk cPanel tanpa shell lihat §4. |
| Node.js 20+ (opsional) | Hanya untuk membangun aset (`npm run build`). **Aset bisa dibangun di mesin lokal lalu diunggah.** |

`composer.json` membutuhkan `laravel/framework ^13.17`. Hard requirement platform PHP
mengikuti lockfile; jalankan `composer platform-check` jika ragu.

## 2. Artefak deploy (disarankan dari mesin lokal)

Jangan upload `node_modules`; unggah hasil build:

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build          # menghasilkan public/build
zip -r deploy.zip app bootstrap config database public resources routes storage vendor artisan composer.* .env.example docs README.md
```

Jangan ikutkan `.env` asli, file log, atau `database/database.sqlite` berisi data —
salinan produksi dibuat di server.

## 3. Deployment dengan akses SSH/CLI

```bash
# 1. Unzip ke ~/varilevke (di luar docroot; docroot diberi titik ke public/)
# 2. Konfigurasi
cp .env.example .env
php artisan key:generate
nano .env       # APP_NAME, APP_URL=https://domainanda, ADMIN_PASSWORD, AI_API_KEY (opsional)
touch database/database.sqlite && chmod ug+w database database/database.sqlite

# 3. Migrasi + data awal (idempoten: aman diulang)
php artisan migrate --force
php artisan db:seed --force     # catalog 272 + wilayah + settings + admin

# 4. Aset, tautan storage, dan cache
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

- **Document root** harus menunjuk ke `public/` (bukan folder proyek).
- Jika `storage:link` gagal (symlink dimatikan), gunakan route `public/storage/...`
  hanya jika memakai storage; saat ini aplikasi tidak mewajibkannya.
- Queue dikonfigurasi `sync`-style (`QUEUE_CONNECTION=database`) tanpa worker berjalan,
  aman untuk shared hosting.
- Setelah selesai, verifikasi §5.

## 4. Deployment shared hosting / cPanel tanpa shell

> **Pernyataan jujur kompatibilitas:** panduan ini memetakan langkah yang **sudah diverifikasi
> di CLI lokal**, belum diuji di cPanel. Assumption yang dicek: cPanel menyediakan File Manager,
> Editor `.env`?, PHP version selector, dan dapat membuat cron job. Jika semua itu tersedia,
> langkah berikut jalan; jika tidak, minta support hosting menjalankan perintah §3.

1. **Bangun di lokal** (§2), termasuk `composer install --no-dev` sehingga `vendor/` terikut.
2. Unggah arsip ke folder di **luar** `public_html`, mis. `/home/user/varilevke-app/`.
3. `public_html` → isi alias/copy **isi folder `public/`** (index.php, .htaccess, build/, robots).
   Edit `public/index.php` kalau path `../vendor/autoload.php` dan `../bootstrap/app.php`
   sudah benar relatif (struktur default memang satu level ke atas).
   Jika `public/` tidak bisa dijadikan docroot, copy isi `public/` ke `public_html`
   dan pastikan `.htaccess` milik Laravel ikut.
4. Buat `database/database.sqlite` (kosong, 0 byte) lewat File Manager; izin 664.
5. `HOME/.env` di folder app: isi semua variabel termasuk `APP_URL=https://domain`,
   `ADMIN_PASSWORD=…`, `APP_KEY` (generate `vendor/bin/... php -r ...` di lokal lalu copy),
   `AI_API_KEY` bila dipakai.
6. Migrasi tanpa shell — dua pilihan:
   a. **Cron job sekali jalan**: buat cron `cd /home/user/varilevke-app && php artisan migrate --force && php artisan db:seed --force` — eksekusi satu menit kemudian, lalu hapus cron.
   b. **Script PHP sementara** di `public_html/_migrate.php` yang menjalankan
      `Artisan::call('migrate', ['--force' => true])`, dibuka sekali di browser, **lalu dihapus segera**.
7. Kosongkan/refresh cache: buat cron/endpoint untuk `php artisan optimize:clear`, kemudian
   `config:cache` jika ingin caching aktif.

## 5. Checklist smoke test pasca-deploy

- [ ] `GET /` → HTTP 200, nama situs benar.
- [ ] `GET /pelatihan` dan `GET /jasa` → 200, jumlah item 227 dan 45.
- [ ] `GET /sitemap.xml` → 200, valid XML, host `APP_URL` (≈313 URL).
- [ ] `GET /robots.txt` → 200, berisi baris `Sitemap: https://…/sitemap.xml`.
- [ ] HTTPS redirect aktif; canonical di halaman detail cocok dengan URL asli.
- [ ] Login `/login` dengan password admin tidak memakai default (log-in lalu keluar).
- [ ] `GET /admin` dengan user non-admin → 403.
- [ ] Buat artikel draf berisi `<script>` via admin → disimpan tanpa `<script>` (sanitasi jalan).

## 6. Backup & rollback

- **SQLite:** backup `database/database.sqlite` (`cp file file.bak` atau
  `sqlite3 database.sqlite ".backup 'backup.sqlite'"`). Katalog dapat di-rebuild dari manifest
  (`php artisan catalog:import` idempoten) tapi **artikel admin tidak** — backup DB sebelum deploy besar.
- **Files:** simpan arsip versi sebelumnya (`app`, `config`, `resources`, `routes`, `public/build`).
- **Rollback file:** tarik kembali arsip versi lama, jalankan `php artisan migrate:rollback`
  hanya bila migrasi baru diketahui destruktif (di proyek ini migrasi hanya tambah kolom/index).
- Jangan pernah membalikan DB produk dengan DB dev (`:memory:` kalau database hilang →
  aplikasi error, bukan kehilangan data silang).

## 7. Catatan keamanan

- Secrets (AI API key, password) hanya di `.env`; tidak pernah di commit/log.
- `.env` harus berada **di luar** docroot; `public/` tidak memasangnya.
- Kalau memakai MySQL: set `DB_*` + ekstensi `pdo_mysql` aktif.
- HTTPS wajib sebelum mem-share, supaya cookie sesi tidak bisa disadap.
