# Deploy ke VPS lewat GitHub (git push → git pull)

Alur ini untuk **VPS dengan akses SSH**. Kode disimpan di GitHub; server mengambil versi terbaru dengan `git pull`, lalu membangun sendiri `vendor/` dan aset frontend. Untuk shared hosting tanpa Node.js, pakai arsip rilis di `docs/DEPLOY_CPANEL.md`.

```text
 Laptop                      GitHub (repo privat)               VPS
 ──────                      ────────────────────               ───
 edit → test → commit
 git push  ───────────────▶  main  ◀─────────────── git fetch/merge (scripts/deploy.sh)
                                                    composer install · npm run build
                                                    migrate · db:seed · optimize
```

- `.env`, `vendor/`, `node_modules/`, dan `public/build/` **tidak** ada di GitHub (`.gitignore`). `.env` hanya ada di server.
- Server memakai **deploy key** read-only: server bisa `pull`, tapi tidak bisa `push` ke GitHub.

---

## 1. Prasyarat VPS (sekali saja)

| Kebutuhan | Keterangan |
|---|---|
| PHP **8.2 / 8.3** CLI + FPM | Ekstensi: `pdo_mysql mbstring openssl tokenizer xml ctype bcmath fileinfo gd zip curl intl` |
| Composer 2 | `composer --version` |
| **Node.js 22 LTS** (min. 20.19) | Dibutuhkan Vite 7 untuk `npm run build` |
| MySQL 8 / MariaDB 10.4+ | Database + user khusus aplikasi |
| Nginx atau Apache | Document root = `<folder-aplikasi>/public` |
| SSL | Let's Encrypt (`certbot`) atau AutoSSL bila VPS memakai cPanel/WHM |
| Cron | `crontab -e` (atau menu *Cron Jobs* di cPanel) |
| RAM ≥ 2 GB, atau swap 2 GB | `npm run build` butuh ±1–1,5 GB |

Contoh pemasangan di **Ubuntu 22.04/24.04** (sebagai user dengan `sudo`):

```bash
sudo apt update
sudo apt install -y git unzip nginx mariadb-server \
  php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml php8.3-bcmath \
  php8.3-gd php8.3-zip php8.3-curl php8.3-intl
# Composer
curl -sS https://getcomposer.org/installer | php && sudo mv composer.phar /usr/local/bin/composer
# Node.js 22 LTS
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash - && sudo apt install -y nodejs
```

> Bila Ubuntu belum punya paket `php8.3`, tambahkan repositori `ppa:ondrej/php` lebih dulu. **VPS dengan cPanel/WHM:** PHP & database diatur lewat WHM/cPanel. Jalankan semua perintah di bawah sebagai **user cPanel** (bukan root). Bila `php` di SSH bukan 8.2+, pakai path lengkap EA-PHP (lihat `DEPLOY_CPANEL.md` §2a).

**Swap 2 GB** (bila RAM < 2 GB; build bisa berhenti dengan pesan `Killed`):

```bash
sudo fallocate -l 2G /swapfile && sudo chmod 600 /swapfile
sudo mkswap /swapfile && sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
```

---

## 2. Sekali saja di laptop: hubungkan proyek ke GitHub

### 2.1 Kunci SSH laptop → GitHub

```bash
ssh-keygen -t ed25519 -C "alfajri-laptop"    # lewati bila ~/.ssh/id_ed25519 sudah ada
cat ~/.ssh/id_ed25519.pub                    # salin
```

GitHub → foto profil → **Settings** → **SSH and GPG keys** → **New SSH key** → tempel → *Add*. Uji:

```bash
ssh -T git@github.com     # "Hi <akun>! You've successfully authenticated…"
```

### 2.2 Buat repo & push pertama

1. GitHub → **New repository**:
   - nama mis. `smanutoboali`;
   - visibilitas **Private**;
   - **jangan** centang README/.gitignore/license (repo harus kosong).
2. Di folder proyek:
   ```bash
   git ls-files | grep -E '^\.env$' || echo "aman: .env tidak ikut"   # .env TIDAK boleh ikut
   git remote add origin git@github.com:<akun>/smanutoboali.git
   git push -u origin main --tags
   ```

---

## 3. Sekali saja di server: deploy key (server → GitHub, read-only)

Masuk ke server: `ssh <user>@<ip-vps>`. Simpan alias di `~/.ssh/config` laptop agar cukup `ssh vps`:

```text
Host vps
    HostName <ip-atau-domain-vps>
    User <user>
    Port 22
    IdentityFile ~/.ssh/id_ed25519
```

Di **server**:

```bash
ssh-keygen -t ed25519 -f ~/.ssh/github_deploy -N "" -C "deploy@smanutoboali"
cat ~/.ssh/github_deploy.pub     # salin
cat >> ~/.ssh/config <<'EOF'
Host github.com
    IdentityFile ~/.ssh/github_deploy
    IdentitiesOnly yes
EOF
chmod 600 ~/.ssh/config
```

GitHub → repo → **Settings** → **Deploy keys** → **Add deploy key** → tempel, **biarkan "Allow write access" tidak dicentang** → *Add key*. Uji dari server:

```bash
ssh -T git@github.com     # "Hi <akun>/smanutoboali! You've successfully authenticated…"
```

---

## 4. Pemasangan pertama di server

Contoh folder `/var/www/smanutoboali` (di cPanel: `~/smanutoboali`, lalu document root domain diarahkan ke `smanutoboali/public`).

```bash
sudo mkdir -p /var/www && sudo chown $USER: /var/www      # tidak perlu di cPanel
cd /var/www
git clone git@github.com:<akun>/smanutoboali.git smanutoboali
cd smanutoboali

cp .env.example .env
nano .env
```

Isi `.env` sama seperti `DEPLOY_CPANEL.md` §3. Yang wajib:

- `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://…`;
- `DB_*` (database & user yang sudah dibuat);
- `MAIL_*`, Pusher & Midtrans bila dipakai;
- `DEFAULT_ACCOUNT_PASSWORD="…"`: password awal 4 akun default (min. 12 karakter, huruf besar & kecil, angka, simbol).

Setelah itu:

```bash
composer install --no-dev --optimize-autoloader     # deploy.sh butuh vendor/ untuk 'artisan down'
php artisan key:generate --force
FORCE=1 scripts/deploy.sh                           # build aset, migrate, seeder (4 akun default), cache
nano .env                                           # HAPUS baris DEFAULT_ACCOUNT_PASSWORD
php artisan optimize
php artisan storage:link
```

**Permission folder.** PHP (web server) harus bisa menulis `storage/` dan `bootstrap/cache/`.

- **cPanel:** cukup `chmod -R 775 storage bootstrap/cache`.
- **Ubuntu + Nginx/PHP-FPM** (FPM berjalan sebagai `www-data`):
  ```bash
  sudo chown -R $USER:www-data storage bootstrap/cache
  sudo chmod -R ug+rwX storage bootstrap/cache
  sudo find storage bootstrap/cache -type d -exec chmod g+s {} +
  ```

**Cron** (`crontab -e`, satu baris; ganti `php` dengan path lengkap bila perlu):

```cron
* * * * * cd /var/www/smanutoboali && php artisan schedule:run >> /dev/null 2>&1
```

**Nginx** (Ubuntu): document root ke `public/`, lalu aktifkan SSL:

```nginx
server {
    server_name smanutoboali.sch.id;
    root /var/www/smanutoboali/public;
    index index.php;
    client_max_body_size 20M;

    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
    location ~ /\.(?!well-known) { deny all; }
}
```

```bash
sudo nano /etc/nginx/sites-available/smanutoboali   # tempel blok di atas
sudo ln -s /etc/nginx/sites-available/smanutoboali /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
sudo apt install -y certbot python3-certbot-nginx && sudo certbot --nginx -d smanutoboali.sch.id
```

Verifikasi:

1. Login `operator@smanutoboali.sch.id`, lalu ganti password saat diminta.
2. Jalankan `php artisan skoolia:go-live-check`.

---

## 5. Alur kerja sehari-hari

**Di laptop** setelah perubahan selesai & teruji:

```bash
git add -A
git commit -m "feat: …"
git push
```

**Deploy ke server**, langsung dari laptop dengan satu perintah:

```bash
ssh vps 'cd /var/www/smanutoboali && scripts/deploy.sh'
```

`scripts/deploy.sh` menjalankan langkah berikut:

1. Berhenti bila ada file yang diubah langsung di server.
2. Bila tidak ada commit baru, tidak melakukan apa-apa.
3. Masuk mode perbaikan (pengguna melihat halaman "Sedang dalam perbaikan").
4. `git merge --ff-only origin/main`.
5. `composer install --no-dev`.
6. `npm ci && npm run build`.
7. `migrate --force`, lalu `db:seed --force` (hanya menambah permission baru; akun yang ada tidak diubah).
8. `optimize` + `queue:restart`.
9. Online kembali.

Bila satu langkah gagal, aplikasi **tetap dalam mode perbaikan** dan pesan error tampil. Perbaiki lalu jalankan `FORCE=1 scripts/deploy.sh`, atau `php artisan up` untuk kembali online dengan kode terakhir.

Opsi:

| Perintah | Kegunaan |
|---|---|
| `FORCE=1 scripts/deploy.sh` | Jalankan ulang semua langkah walau tidak ada commit baru |
| `BRANCH=staging scripts/deploy.sh` | Deploy branch lain (mis. server uji coba) |
| `PHP_BIN=/opt/cpanel/ea-php83/root/usr/bin/php scripts/deploy.sh` | `php` di PATH bukan 8.2+ (cPanel). Build aset juga memanggil `php`, jadi lebih aman tambahkan PHP 8.3 ke PATH |

**Penanda versi:** beri tag saat rilis. Versi tampil di menu **Sistem** (dari file `VERSION` yang ditulis `deploy.sh`).

```bash
git tag v1.1.0 && git push --tags
```

> **Jangan mengedit kode langsung di server.** Perubahan di server akan menghentikan deploy berikutnya, dan memang harus dibatalkan. Ubah di laptop → commit → push → deploy.

---

## 6. Membatalkan rilis (rollback)

1. **Backup dulu:** `php artisan skoolia:backup database`.
2. Di **laptop**, batalkan commit yang bermasalah lalu deploy ulang:
   ```bash
   git revert <hash-commit>     # membuat commit pembatal; riwayat tetap utuh
   git push
   ssh vps 'cd /var/www/smanutoboali && scripts/deploy.sh'
   ```
3. Bila commit tersebut menambah migrasi, `git revert` tidak menghapus tabel/kolomnya.
   - Kembalikan database dengan `php artisan migrate:rollback --step=1 --force` **sebelum** deploy pembatal;
   - atau restore backup (`DEPLOY_CPANEL.md` §9).

---

## 7. (Opsional) Deploy otomatis saat push

GitHub Actions bisa menjalankan `deploy.sh` setiap kali `main` di-push.

1. Buat kunci khusus di laptop: `ssh-keygen -t ed25519 -f gh-actions-deploy -N ""`.
2. Pasang kunci publik di **server**: tambahkan isi `gh-actions-deploy.pub` ke `~/.ssh/authorized_keys`.
3. Simpan di GitHub → repo → **Settings** → **Secrets and variables** → **Actions**:
   - `VPS_HOST`;
   - `VPS_USER`;
   - `VPS_SSH_KEY` (isi file `gh-actions-deploy`, kunci privat).
4. Buat file `.github/workflows/deploy.yml`:

```yaml
name: Deploy
on:
  push:
    branches: [main]
jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - name: Jalankan deploy.sh di VPS
        run: |
          install -m 600 /dev/null key && echo "${{ secrets.VPS_SSH_KEY }}" > key
          ssh -i key -o StrictHostKeyChecking=accept-new ${{ secrets.VPS_USER }}@${{ secrets.VPS_HOST }} \
            'cd /var/www/smanutoboali && scripts/deploy.sh'
```

Disarankan tetap deploy manual (§5) sampai alurnya terbiasa, agar rilis tidak terjadi di jam sekolah tanpa sengaja.

---

## 8. Troubleshooting

| Gejala | Penyebab | Solusi |
|---|---|---|
| `git push`: **Permission denied (publickey)** | Kunci laptop belum ada di GitHub | §2.1; uji `ssh -T git@github.com` |
| `git clone`/`fetch` di server: **Permission denied (publickey)** atau **Repository not found** | Deploy key belum dipasang / `~/.ssh/config` server salah | §3; pastikan `IdentityFile ~/.ssh/github_deploy` |
| `deploy.sh`: **Ada file terlacak yang diubah langsung di server** | Ada file yang diedit di server | Lihat `git diff`. Pindahkan perubahan ke laptop bila perlu, lalu `git checkout -- <file>` |
| `deploy.sh`: **Not possible to fast-forward** | Ada commit dibuat di server, atau riwayat GitHub ditulis ulang (`push --force`) | Backup, lalu `git reset --hard origin/main` dan `FORCE=1 scripts/deploy.sh` |
| `npm run build` berhenti dengan **Killed** | RAM habis | Tambah swap 2 GB (§1) |
| `npm ci`: **Unsupported engine** / Vite minta Node lebih baru | Node < 20.19 | Pasang Node 22 LTS |
| Build: **php: command not found** / versi PHP salah | Plugin Wayfinder memanggil `php` dari PATH | Tambahkan folder PHP 8.2+ ke PATH di `~/.bashrc` |
| Website **500** setelah deploy | Permission `storage/`, atau `.env` belum lengkap | Cek `storage/logs/laravel-*.log`; ulangi perintah permission §4 |
| Masih tampil "Sedang dalam perbaikan" | Deploy gagal di tengah | Baca pesan error deploy, perbaiki, `FORCE=1 scripts/deploy.sh` (atau `php artisan up`) |

Troubleshooting umum aplikasi (cron, email, backup, CSP, dll.) ada di `DEPLOY_CPANEL.md` §12.
