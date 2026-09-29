#!/usr/bin/env bash
#
# Membuat arsip rilis Skoolia untuk cPanel shared hosting (tanpa Node di server).
#
#   scripts/build-release.sh            # versi dari git describe
#   scripts/build-release.sh 1.0.0      # versi manual
#   ALLOW_DIRTY=1 scripts/build-release.sh   # geladi: ikut perubahan yang sudah di-`git add`
#
# Hasil: build/skoolia-<versi>.zip (+ .sha256) berisi folder "skoolia/" siap diunggah.
# Arsip TIDAK berisi .env, node_modules, tests, docs, maupun log.
#
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

for tool in git composer npm php zip; do
    command -v "$tool" >/dev/null 2>&1 || { echo "✗ '$tool' tidak ditemukan di PATH." >&2; exit 1; }
done

TREE="HEAD"
if [[ -n "$(git status --porcelain)" ]]; then
    if [[ "${ALLOW_DIRTY:-0}" != "1" ]]; then
        echo "✗ Ada perubahan yang belum di-commit. Commit dulu (atau ALLOW_DIRTY=1 untuk geladi)." >&2
        exit 1
    fi
    # Snapshot working tree + index (file baru harus sudah `git add`) tanpa mengubah stash.
    TREE="$(git stash create || true)"
    TREE="${TREE:-HEAD}"
    echo "! ALLOW_DIRTY=1: arsip memakai perubahan yang belum di-commit (hanya untuk geladi)."
fi

VERSION="${1:-$(git describe --tags --always)}"
OUT="$ROOT/build"
STAGE="$OUT/skoolia"
ARCHIVE="$OUT/skoolia-$VERSION.zip"

echo "→ Menyiapkan salinan bersih ($VERSION)"
rm -rf "$STAGE" "$ARCHIVE"
mkdir -p "$STAGE"
git archive "$TREE" | tar -x -C "$STAGE"
cd "$STAGE"

echo "→ composer install --no-dev"
composer install --no-dev --optimize-autoloader --classmap-authoritative \
    --no-interaction --prefer-dist --no-progress

echo "→ npm ci && npm run build"
npm ci --no-audit --no-fund --loglevel=error
npm run build

if [[ ! -f public/build/manifest.json ]]; then
    echo "✗ public/build/manifest.json tidak ada — build frontend gagal." >&2
    exit 1
fi

echo "→ Membersihkan file yang tidak dibutuhkan server"
echo "$VERSION" > VERSION
rm -rf node_modules tests docs scripts .github public/hot
rm -f .env .env.backup .env.production CLAUDE.md \
    phpunit.xml phpstan.neon pint.json vitest.config.ts eslint.config.js \
    .prettierrc .prettierignore .editorconfig components.json tsconfig.json \
    package.json package-lock.json vite.config.ts .gitattributes
find storage/logs -type f ! -name '.gitignore' -delete
find storage/framework/{cache,sessions,views} -type f ! -name '.gitignore' -delete 2>/dev/null || true
rm -f bootstrap/cache/*.php

echo "→ Membuat arsip"
cd "$OUT"
zip -qr "$ARCHIVE" skoolia
rm -rf "$STAGE"

if command -v shasum >/dev/null 2>&1; then
    (cd "$OUT" && shasum -a 256 "$(basename "$ARCHIVE")" > "$(basename "$ARCHIVE").sha256")
elif command -v sha256sum >/dev/null 2>&1; then
    (cd "$OUT" && sha256sum "$(basename "$ARCHIVE")" > "$(basename "$ARCHIVE").sha256")
fi

echo "✓ Selesai: ${ARCHIVE#"$ROOT/"} ($(du -h "$ARCHIVE" | cut -f1))"
[[ -f "$ARCHIVE.sha256" ]] && echo "  SHA-256: $(cut -d' ' -f1 "$ARCHIVE.sha256")"
echo "  Lanjutkan dengan docs/DEPLOY_CPANEL.md"
