#!/usr/bin/env bash
#
# Deploy DARI LAPTOP ke shared hosting (Hostinger/Niagahoster hPanel, cPanel) lewat SSH + GitHub.
# Hosting tidak punya Node.js, jadi aset frontend di-build di laptop lalu dikirim dengan rsync;
# kode PHP diambil server sendiri dengan `git pull` dari GitHub.
#
#   scripts/deploy-hosting.sh            # deploy branch di .deploy.env (default main)
#   FORCE=1 scripts/deploy-hosting.sh    # jalankan ulang walau server sudah versi terbaru
#
# Konfigurasi: salin .deploy.env.example → .deploy.env (tidak di-commit), atau set variabel:
#   DEPLOY_SSH       alias Host di ~/.ssh/config (mis. hostinger); "local" = geladi di komputer ini
#   DEPLOY_PATH      folder aplikasi di server, relatif ke home (mis. domains/smanutoboali.sch.id/smanutoboali)
#   DEPLOY_BRANCH    branch GitHub (default main)
#   DEPLOY_PHP       perintah PHP 8.2+ di server (default php)
#   DEPLOY_COMPOSER  perintah Composer 2 di server (default composer)
#
# Urutan: cek kode bersih & sudah di-push → build aset lokal → [server] mode perbaikan →
# git pull → composer install → [rsync] public/build → [server] migrate, db:seed, optimize → online.
# Bila langkah di server gagal, aplikasi tetap dalam mode perbaikan. Panduan: docs/DEPLOY_HOSTINGER.md.
#
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

# shellcheck disable=SC1091
[[ -f .deploy.env ]] && source .deploy.env

: "${DEPLOY_SSH:?Isi DEPLOY_SSH di .deploy.env (lihat .deploy.env.example)}"
: "${DEPLOY_PATH:?Isi DEPLOY_PATH di .deploy.env (lihat .deploy.env.example)}"
BRANCH="${DEPLOY_BRANCH:-main}"
PHP="${DEPLOY_PHP:-php}"
COMPOSER="${DEPLOY_COMPOSER:-composer}"

# Jalankan skrip bash di folder aplikasi server (atau lokal untuk geladi).
remote() {
    if [[ "$DEPLOY_SSH" == "local" ]]; then
        (cd "$DEPLOY_PATH" && bash -s)
    else
        ssh "$DEPLOY_SSH" "cd \"\$HOME/$DEPLOY_PATH\" && bash -s"
    fi
}

echo "→ Memeriksa kode lokal"
if [[ -n "$(git status --porcelain)" ]]; then
    echo "✗ Ada perubahan yang belum di-commit. Commit & push dulu agar aset yang di-build sama dengan kode di server." >&2
    git status --short >&2
    exit 1
fi

git fetch --quiet origin "$BRANCH"
LOCAL="$(git rev-parse HEAD)"
if [[ "$LOCAL" != "$(git rev-parse "origin/$BRANCH")" ]]; then
    echo "✗ Commit lokal ($(git rev-parse --short HEAD)) belum sama dengan origin/$BRANCH. Jalankan: git push origin $BRANCH" >&2
    exit 1
fi

# Commit yang terpasang di server; kosong bila aset belum pernah dikirim (pemasangan pertama).
DEPLOYED="$(remote <<<'test -f public/build/manifest.json && git rev-parse HEAD' 2>/dev/null || true)"
if [[ "$DEPLOYED" == "$LOCAL" && "${FORCE:-0}" != "1" ]]; then
    echo "✓ Server sudah versi terbaru ($(git describe --tags --always)). Tidak ada yang dideploy."
    exit 0
fi

echo "→ Build aset frontend (lokal)"
npm run build
[[ -f public/build/manifest.json ]] || { echo "✗ Build gagal: public/build/manifest.json tidak ada." >&2; exit 1; }

echo "→ [server] Mode perbaikan, git pull, composer install"
remote <<EOF
set -euo pipefail
trap 'echo "✗ Gagal di server. Aplikasi MASIH dalam mode perbaikan; perbaiki lalu jalankan ulang (FORCE=1), atau: $PHP artisan up" >&2' ERR
if [[ -n "\$(git status --porcelain --untracked-files=no)" ]]; then
    echo "✗ Ada file yang diubah langsung di server:" >&2
    git status --short --untracked-files=no >&2
    exit 1
fi
$PHP artisan down --render="errors::503" --retry=60
git fetch --prune --tags origin $BRANCH
git merge --ff-only origin/$BRANCH
git describe --tags --always > VERSION
$COMPOSER install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader
EOF

echo "→ Mengirim public/build ke server"
if [[ "$DEPLOY_SSH" == "local" ]]; then
    rsync -a --delete public/build/ "$DEPLOY_PATH/public/build/"
else
    rsync -az --delete -e ssh public/build/ "$DEPLOY_SSH:$DEPLOY_PATH/public/build/"
fi

echo "→ [server] Migrasi, cache, online"
remote <<EOF
set -euo pipefail
trap 'echo "✗ Gagal di server. Aplikasi MASIH dalam mode perbaikan; perbaiki lalu jalankan ulang (FORCE=1), atau: $PHP artisan up" >&2' ERR
rm -f public/hot
$PHP artisan migrate --force
$PHP artisan db:seed --force
$PHP artisan optimize:clear
$PHP artisan optimize
$PHP artisan queue:restart
$PHP artisan up
echo "✓ Deploy selesai: \$(cat VERSION)"
EOF
