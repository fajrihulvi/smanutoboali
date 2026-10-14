# Deploy ke Hostinger / Niagahoster (GitHub + SSH + symlink `public_html`)

Panduan memasang aplikasi SMA NU Toboali di **shared hosting Hostinger atau Niagahoster**. Pembaruan dikirim lewat **GitHub** (`git push` di laptop → `git pull` di hosting) dan semua akses memakai **SSH**. `public_html` dijadikan **symlink** ke folder `public/` aplikasi, sehingga kode, `.env`, dan `storage/` berada di luar folder web.

```text
 Laptop                                   GitHub (repo privat)        Hosting (~/domains/<domain>/)
 ──────                                   ────────────────────        ─────────────────────────────
 commit → git push ─────────────────────▶ main ◀──── git pull ─────── smanutoboali/      (kode, .env, storage)
 scripts/deploy-hosting.sh                                            public_html → smanutoboali/public
   npm run build ── rsync public/build ─────────────────────────────▶ smanutoboali/public/build
```

**Kenapa aset di-build di laptop?** Shared hosting tidak punya Node.js. Kode PHP diambil hosting dari GitHub, sedangkan `public/build` (hasil `npm run build`) dikirim dari laptop dengan `rsync`. Semua langkah itu dijalankan oleh satu perintah: `scripts/deploy-hosting.sh`.

> **Niagahoster** sudah bergabung dengan Hostinger, dan akun baru/termigrasi memakai panel yang sama (**hPanel**), jadi langkahnya sama.
>
> Bila akun Niagahoster Anda masih memakai **cPanel lama**, bedanya hanya:
> - SSH di menu *SSH Access*, port biasanya `64000`;
> - folder web adalah `~/public_html`, jadi aplikasi di `~/smanutoboali` dan symlink-nya di home.

---

## 1. Persiapan di hPanel (sekali saja)

| Langkah | Menu hPanel |
|---|---|
| Pastikan paket mendukung **SSH** (Premium/Business/Cloud) | *Advanced → SSH Access*. Bila tidak ada, upgrade paket atau tanya CS |
| Aktifkan SSH, lalu catat **IP, Port, Username** (contoh `u123456789`, port **65002**) | *Advanced → SSH Access → Enable* |
| PHP **8.3** (atau 8.2) | *Advanced → PHP Configuration → PHP versions* |
| Ekstensi PHP aktif: `fileinfo intl gd zip bcmath pdo_mysql mbstring openssl curl` | *PHP Configuration → PHP extensions* |
| Database MySQL + user, lalu catat **nama DB, user, password**; host = `localhost` | *Databases → Management* |
| SSL gratis untuk domain | *Security → SSL* |

---

## 2. SSH dari laptop ke hosting (sekali saja)

1. Buat kunci SSH di laptop. Lewati bila `~/.ssh/id_ed25519` sudah ada.
   ```bash
   ssh-keygen -t ed25519 -C "alfajri-laptop"
   cat ~/.ssh/id_ed25519.pub        # salin
   ```
2. hPanel → *Advanced → SSH Access* → **SSH keys** → *Add SSH key*. Tempel kunci tadi.
3. Tambahkan alias di `~/.ssh/config` laptop. Ganti IP, port, dan username sesuai hPanel.
   ```text
   Host hostinger
       HostName 123.45.67.89
       Port 65002
       User u123456789
       IdentityFile ~/.ssh/id_ed25519
   ```
4. Uji, lalu cek alat yang tersedia di hosting:
   ```bash
   ssh hostinger
   php -v            # harus 8.2/8.3 — bila tidak, lihat §8
   composer -V       # Composer 2
   git --version
   exit
   ```

Mulai sekarang, **File Manager** bisa diganti SSH. Beberapa perintah yang sering dipakai:

| Perintah | Kegunaan |
|---|---|
| `ls -la` | lihat isi folder |
| `cd <folder>` | pindah folder |
| `nano <file>` | edit file (Ctrl+O, Enter simpan; Ctrl+X keluar) |
| `tail -n 50 storage/logs/laravel-$(date +%F).log` | lihat log hari ini |

---

## 3. GitHub (sekali saja)

**Di laptop**, hubungkan proyek ke repo **privat** dan push pertama. Rinciannya ada di `docs/DEPLOY_VPS_GITHUB.md` §2.

```bash
ssh -T git@github.com                                       # kunci laptop sudah terdaftar di GitHub?
git remote add origin git@github.com:<akun>/smanutoboali.git
git push -u origin main --tags
```

**Di hosting**, buat **deploy key** (read-only) agar hosting bisa `git pull`:

```bash
ssh hostinger
ssh-keygen -t ed25519 -f ~/.ssh/github_deploy -N "" -C "hostinger@smanutoboali"
printf 'Host github.com\n    IdentityFile ~/.ssh/github_deploy\n    IdentitiesOnly yes\n' >> ~/.ssh/config
chmod 600 ~/.ssh/config
cat ~/.ssh/github_deploy.pub        # salin
```

Pasang kunci itu: GitHub → repo → **Settings → Deploy keys → Add deploy key**. Tempel, dan **jangan** centang *Allow write access*. Uji dari hosting:

```bash
ssh -T git@github.com    # "Hi <akun>/smanutoboali! You've successfully authenticated…"
```

---

## 4. Pemasangan pertama

### 4.1 Clone & `.env` (di hosting)

```bash
ssh hostinger
cd ~/domains/smanutoboali.sch.id
git clone git@github.com:<akun>/smanutoboali.git smanutoboali
cd smanutoboali
cp .env.example .env
nano .env
```

Isi minimal `.env`. Daftar lengkapnya ada di `docs/DEPLOY_CPANEL.md` §3.

```dotenv
APP_NAME="SMA NU Toboali"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://smanutoboali.sch.id
APP_FORCE_HTTPS=true

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u123456789_smanu
DB_USERNAME=u123456789_smanu
DB_PASSWORD="<password-db>"

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_SCHEME=smtps
MAIL_USERNAME=no-reply@smanutoboali.sch.id
MAIL_PASSWORD="<password-email>"
MAIL_FROM_ADDRESS=no-reply@smanutoboali.sch.id
MAIL_FROM_NAME="SMA NU Toboali"

SCHOOL_NAME="SMA NU Toboali"
SCHOOL_LEVEL=SMA
ACCOUNT_DOMAIN=smanutoboali.sch.id
DEFAULT_ACCOUNT_PASSWORD="<min 12 karakter: huruf besar-kecil, angka, simbol>"
```

Lalu:

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate --force
chmod -R 775 storage bootstrap/cache
```

### 4.2 Symlink `public_html` → `smanutoboali/public` (di hosting)

```bash
cd ~/domains/smanutoboali.sch.id
mv public_html public_html_lama          # simpan isi bawaan hosting (default.php, dsb.)
ln -s smanutoboali/public public_html
ls -l                                    # harus: public_html -> smanutoboali/public
```

- Folder web (`public_html`) sekarang menunjuk ke `public/` aplikasi. Hanya `index.php`, `.htaccess`, `build/`, `images/`, dan favicon yang bisa diakses dari internet. `.env`, `vendor/`, dan `storage/` tetap tersembunyi.
- **Symlink dibuat sekali saja.** `git pull` dan deploy berikutnya tidak menyentuhnya.
- `php artisan storage:link` **tidak perlu**: file unggahan disajikan lewat route aplikasi.
- `public_html_lama` boleh dihapus setelah situs berjalan.

### 4.3 Deploy pertama (di laptop)

```bash
cp .deploy.env.example .deploy.env       # sekali saja; isinya tidak di-commit
nano .deploy.env                         # DEPLOY_SSH=hostinger, DEPLOY_PATH=domains/smanutoboali.sch.id/smanutoboali
scripts/deploy-hosting.sh
```

Deploy pertama mem-build aset, mengirim `public/build`, menjalankan migrasi, dan membuat 4 akun default (`operator@`, `kepsek@`, `guru@`, `ortu@smanutoboali.sch.id`).

Lalu **di hosting**, hapus password awal dari `.env`:

```bash
ssh hostinger
cd ~/domains/smanutoboali.sch.id/smanutoboali
nano .env                    # HAPUS baris DEFAULT_ACCOUNT_PASSWORD
php artisan optimize
```

### 4.4 Cron (hPanel)

*Advanced → Cron Jobs* → **Custom** → jadwal setiap menit (`* * * * *`) → command (ganti `u123456789`):

```bash
cd /home/u123456789/domains/smanutoboali.sch.id/smanutoboali && php artisan schedule:run >> /dev/null 2>&1
```

Cron ini menjalankan antrean (email, notifikasi, ekspor), pengumuman terjadwal, backup harian, dan pengingat tagihan. Daftar lengkapnya ada di `docs/DEPLOY_CPANEL.md` §6.

### 4.5 Verifikasi

1. Buka `https://smanutoboali.sch.id`, lalu `/login`.
2. Login `operator@smanutoboali.sch.id`, lalu ganti password saat diminta.
3. Tunggu 1–2 menit, lalu di hosting jalankan `php artisan skoolia:go-live-check`. Semua baris **GAGAL** harus diperbaiki.

---

## 5. Alur kerja sehari-hari

Semua dari laptop, di folder proyek:

```bash
git add -A
git commit -m "feat: …"
git push
scripts/deploy-hosting.sh
```

`scripts/deploy-hosting.sh` mengerjakan:

1. **Cek kode lokal.** Berhenti bila ada perubahan yang belum di-commit, atau commit belum di-push. Dengan begitu aset yang di-build selalu sama dengan kode yang di-pull hosting.
2. **Lewati bila tidak ada perubahan.** Bila hosting sudah versi terbaru, tidak ada yang dilakukan (`FORCE=1` untuk memaksa).
3. **`npm run build`** di laptop.
4. **Di hosting:**
   - masuk mode perbaikan (berhenti dulu bila ada file yang diedit langsung di hosting);
   - `git pull` (fast-forward);
   - `composer install --no-dev`.
5. **`rsync public/build`** ke hosting.
6. **Di hosting:**
   - `migrate --force`;
   - `db:seed --force` (permission baru; akun yang ada tidak diubah);
   - `optimize`;
   - `queue:restart`;
   - online kembali.

Bila langkah di hosting gagal, situs **tetap dalam mode perbaikan** dan pesan error tampil. Perbaiki lalu jalankan `FORCE=1 scripts/deploy-hosting.sh`, atau `ssh hostinger` → `php artisan up`.

Beri tag saat rilis agar versinya tampil di menu **Sistem**:

```bash
git tag v1.1.0 && git push --tags && scripts/deploy-hosting.sh
```

> **Jangan mengedit kode langsung di hosting** (File Manager atau `nano` di file selain `.env`). Deploy berikutnya akan berhenti sampai perubahan itu dibatalkan (`git checkout -- <file>`). Selalu ubah di laptop → commit → push → deploy.

---

## 6. Membatalkan rilis

1. Backup database di hosting dulu: `php artisan skoolia:backup database`.
2. Di laptop, batalkan commit lalu deploy ulang:
   ```bash
   git revert <hash-commit>
   git push
   scripts/deploy-hosting.sh
   ```
3. Bila rilis itu membawa migrasi, kembalikan databasenya **sebelum** deploy pembatal:
   - `php artisan migrate:rollback --step=1 --force`;
   - atau restore backup (`DEPLOY_CPANEL.md` §9).

---

## 7. Backup di Hostinger

Backup aplikasi berjalan otomatis lewat cron (database harian, file mingguan) ke `storage/app/backups/`. Butuh `proc_open` dan `mysqldump`. Cek di hosting:

```bash
php -r 'var_dump(function_exists("proc_open"));'    # harus bool(true)
which mysqldump
```

- Bila `proc_open` tidak tersedia, backup database aplikasi gagal (email dikirim ke `BACKUP_MAIL_TO`). Andalkan backup otomatis Hostinger: *Files → Backups* (database & file), dan unduh rutin.
- Salin backup ke laptop dari laptop:
  ```bash
  rsync -az hostinger:domains/smanutoboali.sch.id/smanutoboali/storage/app/backups/ ~/Backup/smanutoboali/
  ```

---

## 8. Troubleshooting

| Gejala | Penyebab | Solusi |
|---|---|---|
| `ssh hostinger`: **Connection refused / timed out** | SSH belum aktif / port salah | hPanel → SSH Access → Enable; `Port` di `~/.ssh/config` = port dari hPanel (biasanya 65002) |
| `ssh hostinger`: **Permission denied (publickey)** | Kunci belum ditambahkan di hPanel | hPanel → SSH Access → SSH keys → Add |
| `git clone`/`pull` di hosting: **Permission denied (publickey)** | Deploy key belum di GitHub / `~/.ssh/config` hosting | §3; uji `ssh -T git@github.com` di hosting |
| `php -v` di SSH < 8.2 (composer/artisan gagal) | PHP CLI berbeda dari PHP website | Pakai path lengkap. Cari dengan `ls /opt/alt/php8*/usr/bin/php`. Isi `.deploy.env`: `DEPLOY_PHP=/opt/alt/php83/usr/bin/php` dan `DEPLOY_COMPOSER="/opt/alt/php83/usr/bin/php $(which composer)"`. Cron juga memakai path itu |
| `composer install`: **Allowed memory size exhausted** | Batas memori PHP CLI | `COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev --optimize-autoloader` |
| Situs **403 Forbidden** setelah symlink | Symlink dibuat user lain, atau `public_html` dibuat ulang panel | Hapus & buat ulang lewat SSH sebagai user hosting: `rm public_html && ln -s smanutoboali/public public_html` |
| Situs kembali menampilkan halaman bawaan hosting | Panel membuat ulang folder `public_html` (mis. setelah memasang ulang website/SSL) | `mv public_html public_html_lama2 && ln -s smanutoboali/public public_html` |
| **500** dengan `vendor/autoload.php` tidak ditemukan | `composer install` belum jalan | `cd smanutoboali && composer install --no-dev --optimize-autoloader` |
| Halaman polos tanpa gaya (CSS/JS 404) | `public/build` belum terkirim | Jalankan `FORCE=1 scripts/deploy-hosting.sh` dari laptop |
| `deploy-hosting.sh`: **Ada perubahan yang belum di-commit / belum di-push** | Pengaman: aset harus dari kode yang sama | `git add -A && git commit … && git push`, lalu ulangi |
| `deploy-hosting.sh`: **Ada file yang diubah langsung di server** | File diedit lewat File Manager/SSH | `ssh hostinger` → `cd …/smanutoboali && git checkout -- <file>`, lalu ulangi |
| **419 Page Expired** / redirect loop di https | SSL belum aktif atau `APP_URL` masih `http` | Pasang SSL; `APP_URL=https://…`, `APP_FORCE_HTTPS=true`, lalu `php artisan optimize` |

Masalah umum aplikasi (cron, email, CSP, upload besar, dll.) ada di `docs/DEPLOY_CPANEL.md` §12.
