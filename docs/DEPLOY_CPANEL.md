# Deploy Skoolia ke cPanel (Shared Hosting)

Panduan langkah demi langkah untuk memasang Skoolia di hosting cPanel **tanpa Node.js, supervisor, atau SSH wajib** (cukup Terminal cPanel). Berlaku untuk staging maupun produksi.

> Diperbarui untuk **v1.0.0** berdasarkan geladi staging (S35). Arsip rilis dipasang di folder & database baru dengan `APP_ENV=production`, lalu semua halaman keempat portal dibuka. Temuannya dicatat di §12.

---

## 0. Prasyarat hosting (sekali saja)

| Kebutuhan | Cara cek di cPanel |
|---|---|
| PHP **8.2 atau 8.3** | *MultiPHP Manager* → pilih versi untuk domain/subdomain |
| Ekstensi: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `gd`, `zip`, `curl`, `intl` | *Select PHP Version* → *Extensions* |
| Fungsi PHP `proc_open` **tidak** dinonaktifkan | *MultiPHP INI Editor* → `disable_functions`. Dipakai backup (menjalankan `mysqldump`); lihat §9 bila tidak tersedia |
| `mysqldump` tersedia | Terminal: `which mysqldump` (biasanya `/usr/bin/mysqldump`) |
| Terminal / SSH | Menu *Terminal* (atau akses SSH) |
| Cron per menit | Menu *Cron Jobs* |
| Database MySQL/MariaDB + user | *MySQL® Databases* (beri user **ALL PRIVILEGES** ke database) |
| SSL | *SSL/TLS Status* → AutoSSL / Let's Encrypt untuk domain |
| Subdomain dengan document root kustom | *Domains* / *Subdomains* |

Catat path PHP CLI hosting, contoh `/opt/cpanel/ea-php83/root/usr/bin/php` (lihat di *MultiPHP Manager* atau jalankan `which php` di Terminal). Di panduan ini ditulis `php`.

---

## 1. Buat arsip rilis (di komputer lokal)

```bash
git status                          # pastikan bersih & sudah di-commit
scripts/build-release.sh 1.0.0      # → build/skoolia-1.0.0.zip + .sha256
```

Arsip berisi `vendor/` (tanpa paket dev), `public/build/` (aset sudah di-build), dan file `VERSION`. Arsip **tidak** berisi `.env`, `node_modules`, `tests`, dan `docs`. Skrip gagal bila build frontend tidak menghasilkan `public/build/manifest.json`.

Setelah diunggah, cocokkan checksum di Terminal server (opsional):

```bash
sha256sum skoolia-1.0.0.zip      # harus sama dengan isi skoolia-1.0.0.zip.sha256
```

> Untuk geladi dengan perubahan yang belum di-commit: `git add -A && ALLOW_DIRTY=1 scripts/build-release.sh 1.0.0-rc1`.

---

## 2. Struktur folder di server

```text
/home/<user>/
├── skoolia/                 ← seluruh aplikasi (DI LUAR public_html)
│   ├── app/ bootstrap/ config/ vendor/ ...
│   ├── storage/             ← harus bisa ditulis (upload, log, backup)
│   └── public/              ← document root subdomain diarahkan ke sini
└── public_html/             ← website lain / tidak dipakai
```

1. *File Manager* → unggah zip ke `/home/<user>/`, lalu *Extract*. Hasilnya folder `skoolia/`.
2. *Domains* → buat/ubah domain (mis. `sekolah.sch.id` atau `app.sekolah.sch.id`) dengan **Document Root** = `skoolia/public`.

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

```bash
cd ~/skoolia
cp .env.example .env
```

Edit `.env` (File Manager → Edit). **Nilai yang mengandung spasi wajib diberi tanda kutip**, misalnya `SCHOOL_NAME="SMP Negeri 1 Contoh"`. Tanpa kutip, semua perintah `artisan` gagal dengan *Failed to parse dotenv file* (temuan geladi).

```dotenv
APP_NAME=Skoolia
APP_ENV=production
APP_DEBUG=false                  # WAJIB false: detail error tidak boleh tampil
APP_URL=https://sekolah.sch.id   # https wajib untuk produksi
APP_FORCE_HTTPS=true             # bila SSL aktif (AutoSSL/Let's Encrypt)
TRUSTED_PROXIES=*                # bila memakai Cloudflare/proxy hosting
HEALTH_TOKEN=<token-acak>        # php -r "echo bin2hex(random_bytes(24));"

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=<user>_skoolia
DB_USERNAME=<user>_skoolia
DB_PASSWORD=<password-db>

SESSION_DRIVER=database          # SESSION_SECURE_COOKIE otomatis true di produksi
CACHE_STORE=database
QUEUE_CONNECTION=database

LOG_STACK=daily                  # log umum 14 hari; payment & auth 30 hari

MAIL_MAILER=smtp
MAIL_HOST=mail.sekolah.sch.id
MAIL_PORT=465
MAIL_SCHEME=smtps
MAIL_USERNAME=no-reply@sekolah.sch.id
MAIL_PASSWORD=<password-email>
MAIL_FROM_ADDRESS=no-reply@sekolah.sch.id
MAIL_FROM_NAME="SMP Negeri 1 Contoh"

# Realtime (chat & notifikasi langsung) — dashboard.pusher.com, cluster ap1
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=<id>
PUSHER_APP_KEY=<key>
PUSHER_APP_SECRET=<secret>
PUSHER_APP_CLUSTER=ap1

# Pembayaran online — kunci PRODUKSI dari dashboard.midtrans.com (bukan SB-…)
MIDTRANS_SERVER_KEY=<server-key>
MIDTRANS_CLIENT_KEY=<client-key>
MIDTRANS_IS_PRODUCTION=true

# Backup
BACKUP_MAIL_TO=admin@sekolah.sch.id     # tujuan email bila backup gagal
BACKUP_ARCHIVE_PASSWORD=<password-arsip> # opsional: enkripsi ZIP backup (simpan di tempat aman!)
DB_DUMP_BINARY_PATH=/usr/bin             # folder mysqldump bila tidak ada di PATH

# Pemasangan awal (dibaca sekali oleh db:seed — lihat §4)
SCHOOL_NAME="SMP Negeri 1 Contoh"
SCHOOL_LEVEL=SMP                         # SD / SMP / SMA / SMK
OPERATOR_NAME="Nama Operator"
OPERATOR_EMAIL=operator@sekolah.sch.id
OPERATOR_PASSWORD="<min 12 karakter, huruf besar-kecil, angka, simbol>"
```

- `APP_VERSION` tidak perlu diisi; versi dibaca dari file `VERSION` di arsip rilis.
- Kunci Pusher boleh kosong (aplikasi tetap jalan; notifikasi muncul setelah halaman dimuat ulang). Kunci Midtrans boleh kosong (pembayaran online nonaktif; transfer manual + verifikasi tetap jalan).

---

## 4. Perintah artisan pertama kali

```bash
cd ~/skoolia
php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --force        # produksi: role & permission + profil sekolah + 1 operator (tanpa data demo)
```

Lalu **hapus baris `OPERATOR_PASSWORD`** dari `.env`, dan lanjutkan:

```bash
php artisan storage:link           # boleh gagal di hosting yang memblokir symlink (lihat §12)
php artisan optimize
```

- Di `APP_ENV=production`, `db:seed` **selalu** memakai `ProductionSeeder`. Akun demo (password `password`) tidak pernah dibuat, dan perintah aman dijalankan ulang (operator yang sudah ada tidak diubah).
- Password operator yang lemah ditolak (min. 12 karakter, huruf besar & kecil, angka, simbol, dan tidak ada di daftar kebocoran publik).
- Tanpa `OPERATOR_EMAIL`, buat operator secara interaktif: `php artisan skoolia:make-operator operator@sekolah.sch.id --name="Nama Operator"`.
- **Staging yang butuh data demo**: `APP_ENV=staging`, lalu `php artisan migrate:fresh --seed --force`. Jangan pernah di server produksi.

---

## 5. Permission folder

```bash
cd ~/skoolia
chmod -R 775 storage bootstrap/cache
find storage -type f -exec chmod 664 {} \;
```

Folder lain cukup `755` (folder) dan `644` (file). Jangan pernah `777`.

`storage/.htaccess`, `storage/app/private/.htaccess`, dan `storage/app/public/.htaccess` ikut di arsip. File tersebut menolak akses langsung dan mematikan eksekusi PHP di folder upload; jangan dihapus.

---

## 6. Cron job (satu baris)

*Cron Jobs* → *Common Settings: Once Per Minute* → Command:

```bash
cd /home/<user>/skoolia && /opt/cpanel/ea-php83/root/usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

Satu cron ini menjalankan semua tugas terjadwal:

| Tugas | Jadwal |
|---|---|
| Detak scheduler (dibaca `/health`, halaman *Sistem*, `go-live-check`) | tiap menit |
| `queue:work --stop-when-empty --max-time=50` (email, notifikasi, ekspor laporan, tagihan massal, cetak raport) | tiap menit |
| Terbitkan pengumuman terjadwal | tiap menit |
| Bersihkan ekspor laporan kedaluwarsa (24 jam) | tiap jam |
| Hapus permanen file di tempat sampah > 30 hari & ZIP raport > 7 hari | 01:30 |
| Bersihkan audit log > 365 hari | 02:00 |
| Bersihkan token reset password kedaluwarsa | 02:15 |
| **Backup database** | 02:30 |
| **Backup file unggahan** | Minggu 03:00 |
| Bersihkan backup lama (retensi 7 hari / 4 minggu / 3 bulan) | 04:00 |
| Pantau kesehatan backup (email bila tidak sehat) | 05:00 |
| Pengingat tagihan H-3 & H+1 jatuh tempo | 07:00 |

---

## 7. Verifikasi

1. Buka `https://<domain>/` → beranda website; `https://<domain>/login` → halaman pilihan portal.
2. Login di `/login/operator` dengan akun dari §4.
3. Tunggu ±1 menit setelah `php artisan optimize`. Perintah `optimize:clear` mengosongkan cache, termasuk detak scheduler, yang akan muncul lagi pada menit berikutnya.
4. Jalankan checklist otomatis:
   ```bash
   php artisan skoolia:go-live-check
   ```
   Semua baris **GAGAL** wajib diperbaiki. Baris **PERINGATAN** diperiksa manual (mis. Pusher/Midtrans yang sengaja belum dipakai).
5. Cek status dari luar (untuk layanan pemantau uptime):
   ```bash
   curl -H "X-Health-Token: <HEALTH_TOKEN>" https://<domain>/health
   ```
   - `status: "ok"` → sehat.
   - `status: "degraded"` → cron belum berjalan (scheduler tidak berdetak 5 menit).
   - HTTP `503` + `status: "down"` → koneksi database gagal.
   - HTTP `403` → token salah; `404` → `HEALTH_TOKEN` kosong di `.env`.
6. Buka URL yang tidak ada (mis. `/xyz`) → halaman 404 bergaya Skoolia, tanpa detail teknis.
7. Menu **Sistem** (operator) menampilkan versi, antrean, backup terakhir, dan ukuran storage.
8. Jalankan backup pertama: `php artisan skoolia:backup database` (atau tombol **Backup database sekarang** di menu Sistem).

---

## 8. Isi awal sebelum dibuka ke pengguna

Urutan ringkas; rinciannya ada di `docs/PANDUAN_PENGGUNA.md` (bagian Operator).

1. **Pengaturan Sekolah**: nama, logo, alamat, kepala sekolah, tanda tangan, batas upload, mode presensi.
2. **Master Data**: tahun ajaran & semester aktif, mata pelajaran, guru (+ akun), kelas/rombel, siswa (impor Excel) & orang tua (+ akun).
3. **Akademik**: penugasan guru & jadwal.
4. **Role & Permission**: tinjau hak akses bawaan bila perlu.
5. **Keuangan**: jenis biaya & potongan.
6. **CMS Website**: isi lalu **terbitkan** halaman sistem (Profil, Visi Misi, Sambutan, Struktur Organisasi, Fasilitas, Kontak), karena migrasi membuatnya sebagai draf. Sebelum terbit, `/kontak` dan halaman lain menampilkan 404 dan tidak muncul di menu website (temuan geladi). Isi juga Pengaturan Website, banner, dan berita pertama.
7. **PPDB** (bila dipakai): periode, jalur, kuota, dan dokumen persyaratan.

---

## 9. Backup & restore

**Otomatis** (dari cron §6), tersimpan di `~/skoolia/storage/app/backups/`:

| Folder | Isi | Jadwal | Disimpan |
|---|---|---|---|
| `skoolia-database/` | ZIP berisi `db-dumps/mysql-<database>.sql` | harian 02:30 | 7 hari terakhir, lalu 1/minggu selama 4 minggu, lalu 1/bulan selama 3 bulan |
| `skoolia-files/` | ZIP berisi `private/` & `public/` (semua file unggahan) | Minggu 03:00 | sama |

- Email ke `BACKUP_MAIL_TO` hanya dikirim bila backup gagal, tidak sehat (DB > 1 hari, file > 8 hari), atau melebihi 5 GB.
- **Simpan salinan di luar server** minimal seminggu sekali: unduh ZIP terbaru lewat File Manager ke Google Drive/komputer sekolah. Tujuan otomatis tambahan (S3/Google Drive) bisa ditambahkan lewat `BACKUP_DISKS=backups,s3`; paket adapter (mis. `league/flysystem-aws-s3-v3`) harus ikut di-install saat build rilis.
- **Bila `proc_open` dinonaktifkan hosting**, backup database gagal (email terkirim). Aktifkan `proc_open` di *MultiPHP INI Editor*; bila tidak diizinkan, pakai fitur *Backup* cPanel (unduh database harian) dan tetap jalankan backup file.

**Restore database** (sudah diuji di geladi: jumlah baris tabel utama identik):

1. `php artisan down --render="errors::503"`.
2. Unduh & ekstrak ZIP dari `storage/app/backups/skoolia-database/`. Bila arsip terenkripsi, password = `BACKUP_ARCHIVE_PASSWORD`.
3. *phpMyAdmin* → pilih database → **Import** file `db-dumps/mysql-<database>.sql`. Atau di Terminal:
   ```bash
   mysql -u <user> -p <database> < db-dumps/mysql-<database>.sql
   ```
4. `php artisan optimize:clear && php artisan optimize && php artisan up`.

**Restore file unggahan**: ekstrak ZIP dari `skoolia-files/`, lalu salin folder `private/` dan `public/` ke `~/skoolia/storage/app/` (menimpa).

> Uji restore ke database kosong **minimal sekali per semester** (mis. di subdomain staging). Backup yang tidak pernah diuji belum tentu bisa dipulihkan.

---

## 10. Rilis berikutnya (update)

```bash
# lokal
scripts/build-release.sh 1.0.1
```

Di server:

1. Masuk mode perbaikan. Pengguna melihat halaman "Sedang dalam perbaikan", sementara Anda tetap bisa mengecek lewat tautan rahasia:
   ```bash
   cd ~/skoolia
   php artisan down --render="errors::503" --retry=60 --secret="<kata-rahasia>"
   # buka https://<domain>/<kata-rahasia> sekali → browser Anda melewati mode perbaikan
   ```
2. Unggah & extract zip ke folder sementara, lalu salin **menimpa** isi `~/skoolia/` **kecuali** `.env` dan `storage/`.
3. Jalankan:
   ```bash
   php artisan migrate --force
   php artisan db:seed --force          # tambah permission baru; operator tidak diubah
   php artisan optimize:clear && php artisan optimize
   php artisan up
   ```
4. Cek `/health` dan menu **Sistem**.

**Sekali saja saat naik ke v1.0.0** (dari versi sebelumnya): buat thumbnail untuk gambar lama:

```bash
php artisan skoolia:thumbnails
```

Cache halaman website publik otomatis tidak dipakai lagi setelah rilis baru, karena hash aset Vite ikut kunci cache.

---

## 11. Log & pemantauan

| Tempat | Isi |
|---|---|
| Menu **Sistem** (operator) | versi, detak cron & antrean, job gagal (+ tombol ulang/hapus), backup terakhir, ukuran storage, daftar file log |
| `storage/logs/laravel-YYYY-MM-DD.log` | error aplikasi (14 hari) |
| `storage/logs/payment-YYYY-MM-DD.log` | pembayaran online, callback Midtrans (ditolak = WARNING), verifikasi (30 hari) |
| `storage/logs/auth-YYYY-MM-DD.log` | login berhasil/gagal, lockout, logout, reset password (30 hari) |
| Menu **Audit Log** | siapa mengubah apa (nilai, raport, keuangan, akses, dsb.) |
| `GET /health` + `X-Health-Token` | untuk layanan pemantau uptime (mis. UptimeRobot, cek tiap 5 menit) |

Log tidak pernah berisi password, token, atau kunci API.

---

## 12. Troubleshooting

| Gejala | Penyebab umum | Solusi |
|---|---|---|
| Semua perintah artisan: **Failed to parse dotenv file … unexpected whitespace** | Nilai `.env` berisi spasi tanpa tanda kutip | Beri kutip: `SCHOOL_NAME="SMP Negeri 1 Contoh"` |
| **HTTP 500** tanpa pesan | `.env` belum ada / `APP_KEY` kosong / versi PHP < 8.2 / folder storage tidak bisa ditulis | Cek `storage/logs/laravel-*.log`. Jalankan `php artisan key:generate --force`, pastikan PHP 8.2+ di MultiPHP Manager, jalankan §5 |
| 500 setelah update | Cache config/route lama | `php artisan optimize:clear && php artisan optimize` |
| Halaman polos tanpa gaya (CSS/JS 404) | `public/build` tidak ikut terunggah atau document root salah | Pastikan document root = `skoolia/public` dan `public/build/manifest.json` ada; hapus `public/hot` bila ada |
| **Mixed content** (aset `http://` di halaman `https://`), atau redirect loop | Aplikasi di balik proxy SSL tidak tahu request aslinya HTTPS | `.env`: `APP_URL=https://…`, `APP_FORCE_HTTPS=true`, `TRUSTED_PROXIES=*`, lalu `php artisan optimize` |
| **419 Page Expired** saat login | Cookie sesi tidak tersimpan: situs masih `http` padahal produksi mewajibkan cookie HTTPS, atau `SESSION_DOMAIN` salah | Aktifkan SSL. Khusus staging tanpa SSL: `SESSION_SECURE_COOKIE=false`. Kosongkan `SESSION_DOMAIN` |
| Setelah login, halaman berikutnya gagal dimuat (staging **http**) | Versi < 1.0.0 mengirim CSP `upgrade-insecure-requests` di situs http (temuan geladi, sudah diperbaiki) | Perbarui ke v1.0.0 atau aktifkan SSL |
| Tombol/skrip tidak jalan, konsol browser "Refused to … Content Security Policy" | Sumber eksternal baru belum diizinkan CSP | Sementara `CSP_REPORT_ONLY=true` + `php artisan optimize`, laporkan ke pengembang, lalu kembalikan ke `false` |
| `storage:link` gagal (**symlink() has been disabled**) | Hosting memblokir fungsi symlink | Abaikan. File publik disajikan lewat route `/media/{id}`; file privat memang selalu lewat `/files/{id}` |
| Backup database gagal (email "backup failed") | `proc_open` dinonaktifkan, atau `mysqldump` tidak ditemukan | Lihat §9; isi `DB_DUMP_BINARY_PATH` dengan folder `mysqldump` (`which mysqldump`) |
| Email tidak terkirim | Queue tidak berjalan atau SMTP salah | Menu Sistem → antrean menumpuk/gagal; pastikan cron aktif; cek kredensial `MAIL_*` |
| `/health` → `degraded` / Sistem: "Cron belum pernah berjalan" | Cron belum aktif / path PHP salah di cron / cache baru dikosongkan | Cek *Cron Jobs*; tunggu 1 menit setelah `optimize:clear`; jalankan perintah cron manual di Terminal untuk melihat error |
| Job gagal menumpuk | Error saat mengirim email/membuat ekspor | Menu Sistem → lihat pesan error, perbaiki penyebab, lalu **Ulang** |
| Upload gagal untuk file besar | Batas PHP hosting lebih kecil dari pengaturan Skoolia | Naikkan `upload_max_filesize` & `post_max_size` di *MultiPHP INI Editor*, atau turunkan batas di *Pengaturan Sekolah* |
| Login ditolak "Terlalu banyak percobaan" | Rate limit 5x/menit per email+IP | Tunggu 1 menit (tercatat di `auth-*.log`) |
| `/kontak`, `/profil` → 404 | Halaman sistem belum diterbitkan | CMS Website → Halaman → isi & ubah status ke Terbit |

---

## 13. Checklist go-live

Jalankan `php artisan skoolia:go-live-check`. Perintah ini memeriksa sebagian besar butir di bawah secara otomatis (exit code ≠ 0 bila ada yang GAGAL). Butir **(manual)** dicek sendiri.

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` terisi
- [ ] HTTPS aktif + redirect, `APP_URL` memakai `https://` (HSTS & cookie aman otomatis)
- [ ] Kunci **Pusher** & **Midtrans produksi** (bukan `SB-…`); URL notifikasi Midtrans `https://<domain>/webhooks/midtrans` **(manual, di dashboard Midtrans)**
- [ ] Email produksi (SMTP) — coba "Lupa password" dan pastikan email diterima **(manual)**
- [ ] Cron aktif, `/health` → `status: "ok"`
- [ ] Backup database sudah berjalan, **restore sudah diuji** (§9) **(manual)**, salinan luar server dijadwalkan **(manual)**
- [ ] Tidak ada akun demo (`@skoolia.test`); tidak ada operator/kepala sekolah berpassword lemah; `OPERATOR_PASSWORD` sudah dihapus dari `.env`
- [ ] Pengaturan Sekolah terisi; halaman website sudah diterbitkan
- [ ] Aset build ada, tidak ada `public/hot`, folder storage bisa ditulis, `.htaccess` folder upload ada
- [ ] Temuan & rekomendasi di `docs/SECURITY_REVIEW.md` §4 sudah dipenuhi **(manual)**
- [ ] Pelatihan singkat per role memakai `docs/PANDUAN_PENGGUNA.md` **(manual)**
