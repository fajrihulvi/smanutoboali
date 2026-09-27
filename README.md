# Skoolia

Integrated School Management System — Laravel 12 + Inertia (React/TypeScript) + Tailwind + shadcn/ui.
Target deploy: cPanel shared hosting (PHP 8.2/8.3, MySQL, tanpa Node di server).

Dokumen acuan: [docs/SKOOLIA_SYSTEM_PLANNING.md](docs/SKOOLIA_SYSTEM_PLANNING.md), [docs/VIBE_PLANNING_SKOOLIA.md](docs/VIBE_PLANNING_SKOOLIA.md), [CLAUDE.md](CLAUDE.md).

## Setup lokal

Prasyarat: PHP ≥ 8.2 (ekstensi `pdo_mysql`, `mbstring`, `gd`, `intl`, `zip`, `bcmath`, `fileinfo`), Composer 2, Node.js ≥ 20, MySQL/MariaDB.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate

# buat database "skoolia" (utf8mb4) lalu:
php artisan migrate:fresh --seed

composer dev          # server + queue + log + vite
```

Buka http://localhost:8000/login. Akun demo (password `password`):
`kepsek@skoolia.test`, `guru@skoolia.test`, `operator@skoolia.test`, `ortu@skoolia.test`.

## Pemeriksaan kualitas

```bash
php artisan test
./vendor/bin/pint
./vendor/bin/phpstan analyse
npm run types
npm run build
```

## Catatan

- Versi platform PHP di `composer.json` dikunci ke 8.2.0 agar dependensi cocok dengan hosting.
- Queue, cache, dan session memakai driver `database`; queue diproses oleh scheduler (`php artisan schedule:run` via cron).
- Aset frontend dibangun lokal (`npm run build`), server hanya menerima `public/build`.
