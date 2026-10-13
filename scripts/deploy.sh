#!/usr/bin/env bash
#
# Deploy di server VPS dari GitHub (dijalankan DI SERVER, di folder aplikasi):
#
#   scripts/deploy.sh                 # tarik branch main bila ada commit baru
#   BRANCH=staging scripts/deploy.sh  # branch lain
#   FORCE=1 scripts/deploy.sh         # jalankan ulang walau tidak ada commit baru
#   PHP_BIN=/opt/cpanel/ea-php83/root/usr/bin/php scripts/deploy.sh
#
# Urutan: mode perbaikan → git pull (fast-forward) → composer → build aset → migrate →
# seeder produksi (permission baru; akun lama tidak diubah) → cache → restart antrean → online.
# Bila satu langkah gagal, aplikasi tetap dalam mode perbaikan agar pengguna tidak
# melihat versi setengah jadi. Panduan: docs/DEPLOY_VPS_GITHUB.md.
#
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

PHP="${PHP_BIN:-php}"
BRANCH="${BRANCH:-main}"

for tool in git composer npm "$PHP"; do
    command -v "$tool" >/dev/null 2>&1 || { echo "✗ '$tool' tidak ditemukan di PATH." >&2; exit 1; }
done

[[ -f .env ]] || { echo "✗ File .env belum ada. Ikuti pemasangan pertama di docs/DEPLOY_VPS_GITHUB.md." >&2; exit 1; }

# Perubahan langsung di server akan bentrok dengan git pull (dan hilang saat deploy berikutnya).
if [[ -n "$(git status --porcelain --untracked-files=no)" ]]; then
    echo "✗ Ada file terlacak yang diubah langsung di server:" >&2
    git status --short --untracked-files=no >&2
    echo "  Pindahkan perubahan ke komputer lokal → commit → push, lalu di server: git checkout -- <file>." >&2
    exit 1
fi

echo "→ Mengambil perubahan dari GitHub (origin/$BRANCH)"
git fetch --prune --tags origin "$BRANCH"

if [[ "$(git rev-parse HEAD)" == "$(git rev-parse "origin/$BRANCH")" && "${FORCE:-0}" != "1" ]]; then
    echo "✓ Sudah versi terbaru ($(git describe --tags --always)). Tidak ada yang dideploy."
    exit 0
fi

echo "→ Mode perbaikan"
"$PHP" artisan down --render="errors::503" --retry=60

trap 'echo "✗ Deploy gagal (baris $LINENO). Aplikasi MASIH dalam mode perbaikan. Perbaiki penyebabnya lalu jalankan ulang: FORCE=1 scripts/deploy.sh (atau php artisan up untuk kembali online)." >&2' ERR

git merge --ff-only "origin/$BRANCH"
git describe --tags --always > VERSION

echo "→ Dependensi PHP"
composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader

echo "→ Build aset frontend"
npm ci --no-audit --no-fund
npm run build
rm -f public/hot

echo "→ Database"
"$PHP" artisan migrate --force
"$PHP" artisan db:seed --force

echo "→ Cache"
"$PHP" artisan optimize:clear
"$PHP" artisan optimize
"$PHP" artisan queue:restart

"$PHP" artisan up
trap - ERR
echo "✓ Deploy selesai: $(cat VERSION)"
