# CLAUDE.md — Aturan Tetap Proyek SKOOLIA

File ini dibaca otomatis oleh Claude Code di setiap sesi. Patuhi seluruh aturan di bawah kecuali prompt sesi secara eksplisit mengatakan lain.

## Dokumen acuan

- `docs/SKOOLIA_SYSTEM_PLANNING.md` — spesifikasi fungsional (fitur, workflow, status, role).
- `docs/VIBE_PLANNING_SKOOLIA.md` — keputusan teknis, arsitektur, design system, dan urutan sesi.
- Jika keduanya bertentangan, **VIBE_PLANNING menang** (ia berisi koreksi atas spesifikasi awal).

## Stack

- Laravel (versi stabil terbaru yang didukung PHP di hosting), PHP 8.2+
- React + TypeScript melalui **Inertia.js** (bukan SPA + REST API terpisah)
- Tailwind CSS + shadcn/ui, ikon `lucide-react`
- MySQL/MariaDB
- Realtime: **Pusher Channels** (cluster `ap1`) + Laravel Echo
- Target deploy: **cPanel shared hosting** — tidak ada Node.js, supervisor, atau daemon di server

## Batasan shared hosting (WAJIB diingat)

1. Jangan menambah dependensi yang butuh proses berjalan terus (Reverb, Horiz
on, Octane, Swoole, Redis wajib, Node SSR).
2. Queue memakai driver `database` dan diproses lewat scheduler (`queue:work --stop-when-empty`), bukan worker permanen.
3. Aset frontend di-build lokal (`npm run build`); server hanya menerima hasil `public/build`.
4. Proses berat (import/export besar, generate tagihan massal, PDF massal) harus lewat job + chunk.
5. Jangan bergantung pada `storage:link`; file privat disajikan lewat controller dengan otorisasi.

## Struktur modular

Backend: `app/Modules/<Module>/` — lihat §3 VIBE_PLANNING.

```text
app/Modules/<Module>/
├── <Module>ServiceProvider.php
├── routes/web.php
├── Models/  Enums/  Actions/  Services/  Policies/
├── Http/Controllers/{Principal,Teacher,Operator,Guardian}/
├── Http/Requests/  Http/Resources/
├── Events/  Listeners/  Notifications/  Jobs/
└── Database/{Migrations,Factories,Seeders}/
```

Frontend: `resources/js/Modules/<Module>/{Pages,Components}/`. Inertia page name memakai format `'<Module>::<Path>'`, contoh `Inertia::render('Attendance::Teacher/Index')` → `resources/js/Modules/Attendance/Pages/Teacher/Index.tsx`.

Buat modul baru dengan `php artisan make:module <Name>` (dibuat di Sesi 02).

## Aturan kode backend

- Controller tipis: validasi di FormRequest, logika tulis di **Action** (satu kelas satu tindakan, method `handle()`), query kompleks di **Service**.
- Semua status memakai **PHP backed enum** di folder `Enums/`, termasuk label Bahasa Indonesia dan aturan transisi (`canTransitionTo()`).
- Setiap model yang tampil di URL memakai kolom `public_id` (ULID) sebagai route key. Jangan pernah expose `id` auto-increment di URL.
- Tabel transaksional memakai `SoftDeletes` dan menyimpan `academic_year_id` bila relevan.
- **Otorisasi dua lapis:** permission (spatie) + Policy/ownership. Menyembunyikan menu BUKAN otorisasi.
- Query data siswa untuk role non-operator WAJIB melalui scope `Student::visibleTo($user)` (dibuat di Sesi 10).
- Aksi penting dicatat ke audit log (spatie/laravel-activitylog).
- Props Inertia dibentuk lewat API Resource; jangan kirim model mentah (hindari bocor kolom sensitif).
- Tabel besar: paginasi server-side, eager loading, tanpa N+1.
- Pesan UI, validasi, dan email dalam **Bahasa Indonesia**. Nama kelas, tabel, dan kolom dalam **Bahasa Inggris**.
- Timezone `Asia/Jakarta`, locale `id`.

## Aturan kode frontend

- TypeScript strict. Tipe props halaman didefinisikan, tanpa `any`.
- Pakai komponen di `resources/js/Components/ui` (shadcn) dan `resources/js/Components/shared` sebelum membuat komponen baru.
- Warna HANYA lewat token design system (`brand-*`, `slate-*`, dan warna status). Jangan hardcode hex di komponen.
- Setiap halaman list punya: loading state, empty state, paginasi, pencarian/filter bila relevan.
- Form memakai `useForm` Inertia; tampilkan error per field.
- Tanggal diformat dengan `date-fns` locale `id`; mata uang dengan `Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' })`.
- Responsif mulai 360px. Portal Orang Tua dirancang **mobile-first**.

## Konvensi role

| Kode role (spatie) | Label | URL prefix | Route name prefix |
|---|---|---|---|
| `principal` | Kepala Sekolah | `/kepala-sekolah` | `principal.` |
| `teacher` | Guru | `/guru` | `teacher.` |
| `operator` | Admin/Operator | `/operator` | `operator.` |
| `guardian` | Orang Tua | `/orang-tua` | `guardian.` |
| `applicant` | Calon Orang Tua (PPDB) | `/ppdb/akun` | `applicant.` |

Permission memakai format `<modul>.<aksi>`, contoh `attendance.manage` atau `report_card.approve`.

## Alur kerja setiap sesi

1. Baca bagian sesi terkait di `docs/VIBE_PLANNING_SKOOLIA.md` sebelum menulis kode.
2. Jika ada keputusan yang ambigu, tanyakan dulu. Jangan menebak skema data.
3. Tulis test Pest untuk fitur baru, minimal: akses per role dan ownership (role lain/anak lain ditolak).
4. Sebelum selesai, jalankan: `php artisan test`, `./vendor/bin/pint`, `./vendor/bin/phpstan analyse`, `npm run types` (atau `tsc --noEmit`), `npm run build`.
5. Akhiri dengan ringkasan: file yang diubah, perintah migrasi/seed yang perlu dijalankan, cara uji manual, dan saran pesan commit.

## Perintah umum

```bash
composer dev                       # server + vite + queue (lokal)
php artisan migrate:fresh --seed   # reset DB dengan data demo
php artisan test
./vendor/bin/pint
./vendor/bin/phpstan analyse
npm run build
```

## Akun demo (dari seeder)

Password semua akun demo: `password`.
`kepsek@skoolia.test`, `guru@skoolia.test`, `operator@skoolia.test`, `ortu@skoolia.test`
