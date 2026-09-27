# Deploy Skoolia ke cPanel (Shared Hosting)

Panduan langkah demi langkah untuk memasang Skoolia di hosting cPanel **tanpa Node.js, supervisor, atau SSH wajib** (cukup Terminal cPanel). Untuk staging maupun produksi.

---

## 0. Prasyarat hosting (sekali saja)

| Kebutuhan | Cara cek di cPanel |
|---|---|
| PHP **8.2 atau 8.3** | *MultiPHP Manager* → pilih versi untuk domain/subdomain |
| Ekstensi: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `gd`, `zip`, `curl`, `intl` | *Select PHP Version* → *Extensions* |
| Terminal / SSH | Menu *Terminal* (atau akses SSH) |
| Cron per menit | Menu *Cron Jobs* |
| Database MySQL/MariaDB + user | *MySQL® Databases* (beri user **ALL PRIVILEGES** ke database) |
| Subdomain dengan document root kustom | *Domains* / *Subdomains* |

Catat path PHP CLI hosting, contoh `/opt/cpanel/ea-php83/root/usr/bin/php` (lihat di *MultiPHP Manager* atau jalankan `which php` di Terminal). Di panduan ini ditulis `php`.

---

## 1. Buat arsip rilis (di komputer lokal)

```bash
git status                     # pastikan bersih & sudah di-commit
scripts/build-release.sh       # → build/skoolia-<versi>.zip
```

Arsip berisi `vendor/` (tanpa paket dev) dan `public/build/` (aset sudah di-build). Arsip **tidak** berisi `.env`, `node_modules`, `tests`, dan `docs`.

---

## 2. Struktur folder di server

```text
/home/<user>/
├── skoolia/                 ← seluruh aplikasi (DI LUAR public_html)
│   ├── app/ bootstrap/ config/ vendor/ ...
│   ├── storage/             ← harus bisa ditulis
│   └── public/              ← document root subdomain diarahkan ke sini
└── public_html/             ← website lain / tidak dipakai
```

1. *File Manager* → unggah zip ke `/home/<user>/`, lalu *Extract*. Hasilnya folder `skoolia/`.
2. *Domains* → buat/ubah subdomain (mis. `staging.sekolah.sch.id`) dengan **Document Root** = `skoolia/public`.

**Jika document root tidak bisa diubah** (hanya `public_html`):

1. Salin *isi* `skoolia/public/` ke `public_html/`.
2. Edit `public_html/index.php`, ganti dua path:
   ```php
   require __DIR__.'/../skoolia/vendor/autoload.php';
   $app = require_once __DIR__.'/../skoolia/bootstrap/app.php';
   ```
3. Tiap rilis, salin ulang `skoolia/public/build/` ke `public_html/build/`.

---

## 3. File `.env` (pertama kali)

Di Terminal cPanel:

```bash
cd ~/skoolia
cp .env.example .env
```

Edit `.env` (File Manager → Edit). Nilai minimum untuk **staging/produksi**:

```dotenv
APP_NAME=Skoolia
APP_ENV=production
APP_DEBUG=false                  # WAJIB false: detail error tidak boleh tampil
APP_URL=https://staging.sekolah.sch.id
APP_FORCE_HTTPS=true             # bila SSL aktif (AutoSSL/Let's Encrypt)
TRUSTED_PROXIES=*                # bila memakai Cloudflare/proxy hosting
HEALTH_TOKEN=<token-acak>        # php -r "echo bin2hex(random_bytes(24));"

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=<user>_skoolia
DB_USERNAME=<user>_skoolia
DB_PASSWORD=<password-db>

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true       # bila HTTPS
CACHE_STORE=database
QUEUE_CONNECTION=database

MAIL_MAILER=smtp
MAIL_HOST=mail.sekolah.sch.id
MAIL_PORT=465
MAIL_SCHEME=smtps
MAIL_USERNAME=no-reply@sekolah.sch.id
MAIL_PASSWORD=<password-email>
MAIL_FROM_ADDRESS=no-reply@sekolah.sch.id
```

> `APP_VERSION` tidak perlu diisi; versi dibaca dari file `VERSION` di arsip rilis.

---

## 4. Perintah artisan pertama kali

```bash
cd ~/skoolia
php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --class="App\Modules\Access\Database\Seeders\RolesAndPermissionsSeeder" --force
php artisan skoolia:make-operator operator@sekolah.sch.id --name="Nama Operator"
php artisan storage:link          # boleh gagal di hosting yang memblokir symlink (lihat §8)
php artisan optimize
```

- Akun demo (password `password`) **tidak pernah** dibuat bila `APP_ENV=production`; seeder demo akan melewatinya.
- Untuk **staging** yang memang ingin akun demo: set `APP_ENV=staging`, jalankan `php artisan db:seed --force`, dan matikan akun demo sebelum situs dibagikan.

---

## 5. Permission folder

```bash
cd ~/skoolia
chmod -R 775 storage bootstrap/cache
find storage -type f -exec chmod 664 {} \;
```

Folder lain cukup `755` (folder) dan `644` (file). Jangan pernah `777`.

---

## 6. Cron job (satu baris)

*Cron Jobs* → *Common Settings: Once Per Minute* → Command:

```bash
cd /home/<user>/skoolia && /opt/cpanel/ea-php83/root/usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

Satu cron ini menjalankan:

| Tugas | Jadwal |
|---|---|
| Detak scheduler (dibaca `/health`) | tiap menit |
| `queue:work --stop-when-empty --max-time=50` (email, job berat) | tiap menit |
| Hapus permanen file di tempat sampah > 30 hari | 01:30 |
| Bersihkan audit log > 365 hari | 02:00 |
| Bersihkan token reset password kedaluwarsa | 02:15 |

---

## 7. Verifikasi

1. Buka `https://<domain>/login` → halaman pilihan portal tampil.
2. Login di `/login/operator` dengan akun dari `skoolia:make-operator`.
3. Cek status (setelah cron berjalan ≥ 1 menit):
   ```bash
   curl -H "X-Health-Token: <HEALTH_TOKEN>" https://<domain>/health
   ```
   Contoh jawaban sehat:
   ```json
   {"status":"ok","version":"v0.1.0","checks":{"database":{"ok":true},
    "queue":{"ok":true,"pending":0,"failed":0,"last_processed_at":"..."},
    "scheduler":{"ok":true,"last_run_at":"..."}}}
   ```
   - `status: "degraded"` → cron belum berjalan (scheduler tidak berdetak 5 menit).
   - HTTP `503` + `status: "down"` → koneksi database gagal.
   - HTTP `403` → token salah; `404` → `HEALTH_TOKEN` kosong di `.env`.
4. Buka URL yang tidak ada (mis. `/xyz`) → halaman 404 bergaya Skoolia, tanpa detail teknis.

---

## 8. Rilis berikutnya (update)

```bash
# lokal
scripts/build-release.sh
```

Di server:

1. `php artisan down` (menampilkan halaman "Sedang dalam perbaikan").
2. Unggah & extract zip ke folder sementara, lalu salin **menimpa** isi `~/skoolia/` **kecuali** `.env` dan `storage/`.
3. Jalankan:
   ```bash
   cd ~/skoolia
   php artisan migrate --force
   php artisan db:seed --class="App\Modules\Access\Database\Seeders\RolesAndPermissionsSeeder" --force
   php artisan optimize:clear && php artisan optimize
   php artisan up
   ```
4. Cek `/health`.

Seeder role & permission aman dijalankan ulang: permission baru ditambahkan, perubahan operator di menu *Role & Permission* tidak dicabut.

---

## 9. Troubleshooting

| Gejala | Penyebab umum | Solusi |
|---|---|---|
| **HTTP 500** tanpa pesan | `.env` belum ada / `APP_KEY` kosong / versi PHP < 8.2 / folder storage tidak bisa ditulis | Cek `storage/logs/laravel.log`. Jalankan `php artisan key:generate --force`, pastikan PHP 8.2+ di MultiPHP Manager, jalankan §5 |
| 500 setelah update | Cache config/route lama | `php artisan optimize:clear && php artisan optimize` |
| Halaman polos tanpa gaya (CSS/JS 404) | `public/build` tidak ikut terunggah atau document root salah | Pastikan document root = `skoolia/public` dan folder `public/build/manifest.json` ada |
| **Mixed content** (aset `http://` di halaman `https://`), atau redirect loop | Aplikasi di balik proxy SSL tidak tahu request aslinya HTTPS | `.env`: `APP_URL=https://…`, `APP_FORCE_HTTPS=true`, `TRUSTED_PROXIES=*`, lalu `php artisan optimize` |
| **419 Page Expired** saat login | Cookie sesi tidak tersimpan: `SESSION_SECURE_COOKIE=true` tapi situs masih `http`, atau `SESSION_DOMAIN` salah | Aktifkan SSL atau set `SESSION_SECURE_COOKIE=false`; kosongkan `SESSION_DOMAIN` |
| `storage:link` gagal (**symlink() has been disabled**) | Hosting memblokir fungsi symlink | Abaikan. File publik (logo, gambar) otomatis disajikan lewat route `/media/{id}`; file privat memang selalu lewat `/files/{id}` |
| Email tidak terkirim | Queue tidak berjalan atau SMTP salah | Cek `/health` → `queue.pending`; pastikan cron aktif; cek kredensial `MAIL_*` |
| `/health` → `degraded` | Cron belum aktif / path PHP salah di cron | Cek *Cron Jobs*; jalankan perintah cron manual di Terminal untuk melihat error |
| Upload gagal untuk file besar | Batas PHP hosting lebih kecil dari pengaturan Skoolia | Naikkan `upload_max_filesize` & `post_max_size` di *MultiPHP INI Editor*, atau turunkan batas di *Pengaturan Sekolah* |
| Login ditolak "Terlalu banyak percobaan" | Rate limit 5x/menit per email+IP | Tunggu 1 menit |

---

## 10. Checklist sebelum dibuka ke pengguna

- [ ] `APP_ENV=production`, `APP_DEBUG=false`
- [ ] HTTPS aktif, `APP_URL` memakai `https://`
- [ ] Cron aktif dan `/health` → `status: "ok"`
- [ ] Akun demo tidak ada / sudah dinonaktifkan; password operator kuat
- [ ] Pengaturan Sekolah (nama, logo, alamat) sudah diisi
- [ ] Email lupa password sudah dicoba dan diterima
