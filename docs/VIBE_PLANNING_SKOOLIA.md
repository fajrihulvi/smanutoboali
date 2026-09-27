# SKOOLIA — Vibe Planning untuk Claude Code

> Blueprint implementasi bertahap **Skoolia – Integrated School Management System**.
> Pasangan dokumen:
> - `docs/SKOOLIA_SYSTEM_PLANNING.md` → spesifikasi fungsional (APA yang dibangun)
> - `docs/VIBE_PLANNING_SKOOLIA.md` → dokumen ini (BAGAIMANA dan URUTAN pembangunan)
> - `CLAUDE.md` (root project) → aturan tetap yang dibaca Claude Code setiap sesi

---

## 0. Cara Pakai Dokumen Ini

1. Letakkan `CLAUDE.md` di root project. Simpan dokumen ini dan spesifikasi awal di folder `docs/`.
2. Kerjakan **satu sesi = satu prompt** secara berurutan (S00 → S35).
3. Mulai setiap sesi dengan konteks bersih (`/clear`), lalu tempel **Template Pembuka** + prompt sesi.
4. Untuk sesi besar, minta Claude Code membuat rencana dulu (Plan Mode, `Shift+Tab`) sebelum menulis kode.
5. Setelah sesi selesai: cek bagian **Selesai jika**, uji manual di browser, lalu commit.
6. Jika hasil melenceng, perbaiki di sesi yang sama. Jangan lanjut ke sesi berikutnya dengan fondasi yang retak.

### Template Pembuka (tempel sebelum setiap prompt sesi)

```text
Baca CLAUDE.md, lalu baca docs/VIBE_PLANNING_SKOOLIA.md bagian "S<nomor>"
beserta bagian spesifikasi terkait di docs/SKOOLIA_SYSTEM_PLANNING.md.
Periksa kode yang sudah ada agar konsisten dengan modul sebelumnya.
Buat rencana singkat dulu, tunggu persetujuan saya, baru implementasi.
```

### Konvensi commit

```text
feat(attendance): input presensi harian oleh guru
fix(chat): otorisasi private channel conversation
chore(deploy): skrip build untuk cPanel
```

Buat tag git di akhir setiap fase: `v0.1-foundation`, `v0.2-master-data`, dan seterusnya.

---

## 1. Keputusan Teknis

| Komponen | Pilihan | Alasan |
|---|---|---|
| Backend | Laravel (versi stabil terbaru yang didukung PHP di cPanel) | Laravel 12 butuh PHP ≥ 8.2, jadi cek versi PHP hosting di S00 |
| Frontend | React + TypeScript via **Inertia.js** | Satu aplikasi dan satu auth session tanpa REST API terpisah; paling ramah shared hosting |
| Starter | Laravel **React Starter Kit** resmi | Sudah membawa Inertia, TS, Tailwind, dan shadcn/ui |
| CSS / UI | Tailwind CSS + **shadcn/ui** + `lucide-react` | Komponen elegan dan mudah di-theme biru |
| Realtime | **Pusher Channels** (cluster `ap1`, Singapura) + Laravel Echo | Tidak butuh WebSocket server sendiri, jadi aman untuk shared hosting |
| Database | MySQL / MariaDB | Standar di cPanel |
| RBAC | `spatie/laravel-permission` | Role + permission teruji |
| Audit log | `spatie/laravel-activitylog` | Menyimpan old/new value otomatis |
| PDF | `barryvdh/laravel-dompdf` | Raport, kwitansi, dan bukti pendaftaran; murni PHP |
| Excel | `maatwebsite/excel` | Import siswa dan export laporan |
| Gambar | `intervention/image` | Kompres foto upload (hemat storage hosting) |
| Payment | **Midtrans Snap** (alternatif: Xendit) | Umum di Indonesia, mendukung VA/QRIS/e-wallet |
| Backup | `spatie/laravel-backup` | Backup DB + file terjadwal lewat cron |
| Queue | Driver `database` + cron scheduler | Tidak ada supervisor di shared hosting |
| Tabel | TanStack Table | Tabel data dengan sort/filter |
| Grafik | Recharts | Dashboard KPI |
| Tanggal | `date-fns` locale `id` | Format Indonesia |
| Test & kualitas | Pest, Laravel Pint, Larastan | Standar ekosistem Laravel |
| Website publik | **Blade + Tailwind** (bukan Inertia) | SEO: halaman publik harus ter-render di server tanpa Node SSR |

---

## 2. Batasan cPanel Shared Hosting dan Solusinya

| Batasan | Solusi di Skoolia |
|---|---|
| Tidak ada Node.js di server | `npm run build` di lokal/CI, upload folder `public/build` |
| Tidak ada supervisor/daemon | Queue diproses scheduler: `queue:work --stop-when-empty --max-time=50` setiap menit via cron |
| Tidak bisa menjalankan WebSocket server | Pusher (hosted); Laravel cukup mengirim HTTP request ke API Pusher |
| `symlink()` sering diblokir | File privat disajikan lewat `FileController` dengan otorisasi; file publik CMS memakai route fallback jika `storage:link` gagal |
| Memori dan waktu eksekusi terbatas | Paginasi, `chunk()`, dan job untuk import/export/generate massal |
| Document root adalah `public_html` | Subdomain/addon domain dengan document root diarahkan ke `skoolia/public` (lihat §9) |
| SSH belum tentu tersedia | Minimal butuh **Terminal cPanel** untuk `composer`/`artisan`; cek di S00 |
| Kuota koneksi Pusher gratis terbatas | Hanya chat dan notifikasi yang realtime; cek kuota paket terbaru sebelum go-live |

---

## 3. Arsitektur Modular

### 3.1 Daftar modul

| Modul | Cakupan | Domain |
|---|---|---|
| `Core` | Layout props, settings, file, audit, helper periode aktif | Sistem |
| `Auth` | Login per role, role aktif, reset password | Sistem |
| `Access` | User management, role & permission | Sistem |
| `School` | Profil sekolah, tahun ajaran, semester | Master |
| `People` | Guru, orang tua, siswa, relasi orang tua–siswa | Master |
| `Academic` | Rombel/kelas, mapel, penugasan mengajar, jadwal, materi | Learning |
| `Attendance` | Presensi siswa & guru, rekap | Learning |
| `Assessment` | Komponen nilai, input nilai | Learning |
| `ReportCard` | Raport + workflow approval + PDF | Learning |
| `Portfolio` | Portofolio siswa | Learning |
| `Activity` | Kegiatan dan dokumentasi | Learning |
| `Chat` | Percakapan realtime | Engagement |
| `ChildCondition` | Laporan kondisi anak | Engagement |
| `Announcement` | Pengumuman internal | Engagement |
| `Notification` | Notification center (in-app, email, realtime) | Engagement |
| `Finance` | Jenis tagihan, invoice, pembayaran, gateway | Operation |
| `Ppdb` | Pendaftaran, verifikasi, seleksi, daftar ulang | Operation |
| `Cms` | Konten website + halaman publik Blade | Operation |
| `Dashboard` | Dashboard per role + laporan | Analytics |

### 3.2 Struktur backend

```text
app/
├── Modules/
│   ├── Core/
│   ├── Attendance/
│   │   ├── AttendanceServiceProvider.php     # load routes, migrations, policies, events
│   │   ├── routes/web.php                    # dikelompokkan per prefix role
│   │   ├── Models/
│   │   │   ├── AttendanceSession.php
│   │   │   └── AttendanceRecord.php
│   │   ├── Enums/AttendanceStatus.php        # HADIR, SAKIT, IZIN, ALPA, TERLAMBAT
│   │   ├── Actions/RecordAttendanceAction.php
│   │   ├── Services/AttendanceRecapService.php
│   │   ├── Policies/AttendanceSessionPolicy.php
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   ├── Teacher/AttendanceController.php
│   │   │   │   ├── Guardian/AttendanceController.php
│   │   │   │   ├── Principal/AttendanceController.php
│   │   │   │   └── Operator/AttendanceController.php
│   │   │   ├── Requests/StoreAttendanceRequest.php
│   │   │   └── Resources/AttendanceRecordResource.php
│   │   ├── Events/AttendanceRecorded.php
│   │   ├── Listeners/NotifyGuardiansOfAbsence.php
│   │   ├── Notifications/StudentAbsentNotification.php
│   │   └── Database/{Migrations,Factories,Seeders}/
│   └── ...
├── Http/Middleware/                          # middleware lintas modul
├── Providers/
│   └── ModuleServiceProvider.php             # base class untuk provider modul
└── Support/                                  # helper global (Money, ActivePeriod, dll.)
```

### 3.3 Struktur frontend

```text
resources/
├── css/app.css                    # token design system (@theme)
├── js/
│   ├── app.tsx                    # Inertia resolver format 'Module::Path'
│   ├── Components/
│   │   ├── ui/                    # shadcn/ui (sudah di-theme biru)
│   │   └── shared/                # PageHeader, DataTable, StatCard, StatusBadge,
│   │                              # EmptyState, ConfirmDialog, FileUploader,
│   │                              # StudentSwitcher, FilterBar, Pagination
│   ├── Layouts/
│   │   ├── AuthLayout.tsx
│   │   ├── AppLayout.tsx          # sidebar + topbar + notification bell
│   │   └── ParentLayout.tsx       # mobile-first + bottom navigation
│   ├── Modules/
│   │   └── Attendance/
│   │       ├── Pages/{Teacher,Guardian,Principal,Operator}/Index.tsx
│   │       └── Components/AttendanceGrid.tsx
│   ├── config/navigation.ts       # menu per role, difilter oleh permission
│   ├── hooks/                     # usePermission, useActiveRole, useEchoChannel
│   ├── lib/                       # echo.ts, format.ts, utils.ts
│   └── types/                     # tipe global + tipe shared props
└── views/
    ├── app.blade.php              # root Inertia
    └── public/                    # website sekolah (Blade, SEO)
```

### 3.4 Aturan antar-modul

- Modul boleh **membaca** Model modul lain (misalnya `Attendance` membaca `Student`).
- Modul **tidak boleh menulis** ke tabel modul lain secara langsung. Panggil Action milik modul pemilik atau gunakan Event.
- Efek samping lintas modul (notifikasi, audit, update statistik) dijalankan lewat **Event + Listener**.
- Kode yang dipakai 3+ modul dipindah ke `Core` atau `app/Support`.

---

## 4. Role, Autentikasi, dan Otorisasi

### 4.1 Mekanisme login per role + role aktif

- Semua halaman login (`/login/guru`, `/login/orang-tua`, dan seterusnya) memakai **satu guard** (`web`) dan satu tabel `users`.
- Setelah kredensial valid, sistem mengecek bahwa user memiliki role yang sesuai halaman login. Jika tidak, tampilkan pesan generik "Email atau password salah" agar keberadaan akun tidak bocor.
- Role yang dipakai disimpan di session sebagai `active_role`, lalu user diarahkan ke `/<prefix>/dashboard`.
- Middleware `active.role:teacher` memastikan route `/guru/*` hanya bisa diakses dengan `active_role = teacher`.
- User dengan lebih dari satu role (misalnya guru yang juga orang tua murid) mendapat **Role Switcher** di topbar.

### 4.2 Matriks permission (ringkas)

✓ = penuh, 👁 = lihat saja, ◐ = terbatas pada data miliknya, ✗ = tidak ada akses

| Permission | Kepala Sekolah | Guru | Operator | Orang Tua |
|---|---|---|---|---|
| `master_data.manage` | ✗ | ✗ | ✓ | ✗ |
| `student.view` | 👁 semua | ◐ siswa yang diajar | ✓ | ◐ anak sendiri |
| `academic.manage` | ✗ | ◐ jadwal & materinya | ✓ | ✗ |
| `attendance.manage` | ✗ | ◐ kelasnya | ✓ | ✗ |
| `grade.manage` | ✗ | ◐ mapelnya | 👁 | ✗ |
| `report_card.review` | ✗ | ◐ jika wali kelas | ✗ | ✗ |
| `report_card.validate` | ✗ | ✗ | ✓ | ✗ |
| `report_card.approve` | ✓ | ✗ | ✗ | ✗ |
| `portfolio.manage` | ✗ | ◐ | ✓ | ✗ |
| `activity.manage` | ✗ | ◐ | ✓ | ✗ |
| `finance.manage` | ✗ | ✗ | ✓ | ✗ |
| `finance.view` | 👁 | ✗ | ✓ | ◐ tagihan anak |
| `ppdb.manage` | ✗ | ✗ | ✓ | ✗ |
| `ppdb.decide` | ✓ | ✗ | ✗ | ✗ |
| `cms.manage` | ✗ | ✗ | ✓ | ✗ |
| `chat.use` | ✓ | ✓ | ✓ | ✓ |
| `child_condition.respond` | ✗ | ◐ | ✗ | ✗ |
| `child_condition.submit` | ✗ | ✗ | ✗ | ◐ |
| `user.manage` / `role.manage` | ✗ | ✗ | ✓ | ✗ |
| `report.export` | ✓ | ◐ | ✓ | ✗ |

### 4.3 Aturan cakupan data (ownership)

| Role | Siswa yang boleh diakses |
|---|---|
| Orang Tua | Siswa yang terhubung lewat tabel `guardian_student` |
| Guru | Siswa di rombel **tahun ajaran aktif** tempat ia punya `teaching_assignment` atau menjadi wali kelas |
| Kepala Sekolah | Semua (baca) |
| Operator | Semua |

Aturan ini diimplementasikan sekali di scope `Student::visibleTo(User $user)`. Semua Policy yang menyangkut siswa (presensi, nilai, raport, portofolio, kondisi anak, tagihan, chat) memakai scope ini.

---

## 5. Penyesuaian Model Data (koreksi atas spesifikasi awal)

| Spesifikasi awal | Penyesuaian | Alasan |
|---|---|---|
| Orang tua–siswa 1:N | **N:M** lewat `guardian_student` (kolom: `relationship` ayah/ibu/wali, `is_primary`) | Ayah dan ibu bisa punya akun masing-masing; kakak-adik berbagi orang tua |
| `class_students` | **`class_enrollments`** (student, classroom, academic_year, status) | Siswa berpindah rombel setiap tahun; riwayat tetap tersimpan |
| `teacher_subjects` + `class_subjects` | **`teaching_assignments`** (teacher, classroom, subject, academic_year) | Satu tabel menjawab "siapa mengajar apa di kelas mana tahun ini" |
| Wali kelas tidak jelas | Kolom `homeroom_teacher_id` di `classrooms` (per tahun ajaran) | Wali kelas adalah penugasan, bukan role |
| `attendances` generik | `attendance_sessions` + `attendance_records` (siswa) dan `teacher_attendances` (guru) | Presensi siswa dan guru punya alur berbeda |
| Status raport 5 tahap | `DRAFT → SUBMITTED → HOMEROOM_REVIEWED → VALIDATED → APPROVED → PUBLISHED`, plus `REVISION` | Selaras dengan workflow 4 pihak dan mendukung pengembalian |
| Actor PPDB tanpa akun | Role **`applicant`**; akun dikonversi menjadi `guardian` saat daftar ulang | Calon orang tua perlu login untuk memantau status |
| ID numerik di URL | Kolom **`public_id` (ULID)** sebagai route key | Mencegah enumerasi ID (IDOR) |
| — | `academic_year_id` di tabel transaksional + helper `ActivePeriod` | Semua data terikat periode aktif |
| — | `conversations.student_id` (opsional) | Chat bisa diberi konteks "tentang anak X" |
| — | `conversation_participants.last_read_at` | Status baca dan jumlah belum dibaca |
| — | `payments.gateway_reference` unik + tabel `payment_callbacks` | Callback idempotent dan bisa diaudit |
| Nilai generik | `grade_components` dengan bobot + `grades` + kolom deskripsi capaian | Siap untuk format raport Kurikulum Merdeka |

---

## 6. Design System — "Elegant Blue"

### 6.1 Palet warna (token di `resources/css/app.css`)

| Token | Hex | Pemakaian |
|---|---|---|
| `brand-950` | `#0A1A3F` | Sidebar (navy gelap), header login |
| `brand-900` | `#0F2A5F` | Gradient sidebar, teks judul penting |
| `brand-800` | `#163A80` | Hover sidebar |
| `brand-700` | `#1D4ED8` | Tombol primary saat hover, link aktif |
| `brand-600` | `#2563EB` | **Primary**: tombol, fokus, highlight |
| `brand-500` | `#3B82F6` | Grafik seri utama, ikon aktif |
| `brand-300` | `#93C5FD` | Grafik seri kedua, border fokus |
| `brand-100` | `#DBEAFE` | Latar badge, item terpilih |
| `brand-50` | `#EFF6FF` | Latar section lembut |
| `accent` | `#38BDF8` (sky) | Aksen gradient dan ilustrasi |
| Netral | Tailwind `slate` | Teks (`slate-900/600/400`), border (`slate-200`), latar (`slate-50`) |
| Sukses | `emerald-600` | Hadir, Lunas, Diterima |
| Peringatan | `amber-500` | Izin, Pending, Revisi |
| Bahaya | `rose-600` | Alpa, Gagal, Ditolak |
| Info | `sky-600` | Sakit, Diproses |

Warna non-biru **hanya** dipakai untuk status semantik; lebih dari 80% permukaan UI bernuansa biru, putih, dan slate.

### 6.2 Tipografi

- Font: **Plus Jakarta Sans** (Google Fonts), weight 400/500/600/700.
- Skala: judul halaman `text-2xl font-semibold`, judul kartu `text-base font-semibold`, isi `text-sm`, caption `text-xs text-slate-500`.
- Angka di tabel dan KPI memakai `tabular-nums`.

### 6.3 Pola layout

- **Sidebar**: gradient `brand-950 → brand-900`, teks putih 80%; item aktif memakai latar `white/10` dan garis kiri `brand-500`; logo di atas; dapat diciutkan.
- **Topbar**: putih, `border-b slate-200`, berisi breadcrumb, pencarian global, lonceng notifikasi (badge realtime), role switcher, dan avatar.
- **Konten**: latar `slate-50`, kartu putih `rounded-2xl border border-slate-200 shadow-sm`, padding `p-6`.
- **Portal Orang Tua**: mobile-first dengan bottom navigation (Beranda, Anak, Chat, Tagihan, Profil); pemilih anak berupa chip avatar di atas halaman.
- **Halaman login**: split-screen. Kiri: panel gradient biru dengan ilustrasi/logo sekolah dan judul sesuai role ("Portal Guru", "Portal Orang Tua"). Kanan: form putih yang bersih. Di mobile, panel kiri menjadi header ringkas.
- **Stat card**: ikon dalam lingkaran `brand-50`, angka besar `text-3xl font-semibold`, tren kecil di bawahnya.
- **Status badge**: pill `rounded-full px-2.5 py-0.5 text-xs font-medium`, latar warna-100 dan teks warna-700.

### 6.4 Detail yang membuat tampilan elegan

- Transisi halus `transition-colors duration-150`; tanpa animasi berlebihan.
- Spacing konsisten (kelipatan 4); whitespace lega.
- Ilustrasi empty state sederhana satu warna biru.
- Skeleton loader untuk tabel dan kartu.
- Toast (sonner) di kanan atas untuk sukses/gagal.
- Fokus keyboard terlihat (`ring-2 ring-brand-500/40`), kontras teks minimal AA.

---

## 7. Peta Sesi

| Fase | Sesi | Output utama | Prioritas |
|---|---|---|---|
| **0 — Persiapan** | S00–S03 | Hosting tervalidasi, scaffold, arsitektur modular, design system | P0 |
| **1 — Foundation** | S04–S07 | Login multi-role, RBAC, user management, file/audit/settings | P0 |
| ⛳ Checkpoint | S07b | **Deploy staging pertama ke cPanel** | P0 |
| **2 — Master Data** | S08–S11 | Periode, guru, orang tua, siswa, rombel, penugasan | P0 |
| **3 — Learning** | S12–S18 | Akademik, presensi, nilai, raport, portofolio, kegiatan | P0/P1 |
| **4 — Engagement** | S19–S23 | Pusher, notifikasi, chat, kondisi anak, pengumuman, portal ortu | P1 |
| **5 — Operation** | S24–S29 | Keuangan, payment gateway, PPDB, CMS, website publik | P1/P2 |
| **6 — Dashboard** | S30–S31 | Dashboard 4 role, laporan PDF/Excel | P1 |
| **7 — Hardening** | S32–S35 | Keamanan, performa, backup, deploy produksi | P0 |

---

## 8. Detail Sesi

### FASE 0 — PERSIAPAN

#### S00 — Validasi hosting & akun layanan (manual, tanpa Claude Code)

Checklist yang Anda kerjakan sendiri:

- [ ] Versi PHP di cPanel (MultiPHP Manager) minimal **8.2**; catat versi tertinggi yang tersedia.
- [ ] Ekstensi PHP aktif: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `gd`, `zip`, `curl`, `intl`.
- [ ] **Terminal cPanel** atau SSH tersedia, dan `composer` bisa dijalankan.
- [ ] Cron Jobs tersedia (interval per menit diizinkan).
- [ ] Bisa membuat subdomain/addon domain dengan document root kustom.
- [ ] Database MySQL/MariaDB + user dibuat.
- [ ] Akun **Pusher Channels**: buat app, cluster `ap1`, catat `app_id`, `key`, `secret`.
- [ ] Akun **Midtrans Sandbox** (boleh menyusul di Fase 5).
- [ ] SMTP untuk email (email cPanel atau layanan transaksional).

**Selesai jika:** semua butir tercentang dan versi PHP diketahui (menentukan versi Laravel).

---

#### S01 — Scaffold project

```text
Buat project Laravel baru bernama "skoolia" memakai Laravel React Starter Kit
(Inertia + React + TypeScript + Tailwind + shadcn/ui). Gunakan versi Laravel stabil
terbaru yang kompatibel dengan PHP <ISI VERSI PHP HOSTING>.

Konfigurasi:
- APP_NAME=Skoolia, timezone Asia/Jakarta, locale id, fallback_locale en, faker_locale id_ID
- Database MySQL; QUEUE_CONNECTION=database; SESSION_DRIVER=database; CACHE_STORE=database
- Pasang paket: spatie/laravel-permission, spatie/laravel-activitylog, barryvdh/laravel-dompdf,
  maatwebsite/excel, intervention/image, spatie/laravel-backup
- Pasang dev: pestphp/pest, larastan/larastan (level 6), laravel/pint
- Pasang npm: @tanstack/react-table, recharts, date-fns, sonner (jika belum ada)
- Tambahkan paket terjemahan pesan validasi Bahasa Indonesia (lang/id)
- Script npm "types" untuk tsc --noEmit
- Buat .env.example lengkap dengan komentar per blok (APP, DB, MAIL, PUSHER, MIDTRANS)
- Buat README.md singkat: cara setup lokal
Jangan buat fitur apa pun dulu. Pastikan halaman bawaan starter kit berjalan
dan semua test bawaan lulus.
```

**Selesai jika:** `composer dev` jalan, halaman awal tampil, `php artisan test` hijau, dan `npm run build` sukses.

---

#### S02 — Arsitektur modular

```text
Implementasikan arsitektur modular sesuai §3 VIBE_PLANNING:

1. app/Providers/ModuleServiceProvider.php (abstract): method helper untuk load routes
   (routes/web.php milik modul), migrations (Database/Migrations), dan registrasi
   policy/event. Setiap modul punya <Module>ServiceProvider yang extend kelas ini.
   Daftarkan provider modul di bootstrap/providers.php.
2. Artisan command `make:module {name}` yang men-generate kerangka folder lengkap
   (lihat CLAUDE.md) + ServiceProvider + routes/web.php kosong + folder frontend
   resources/js/Modules/<Name>/{Pages,Components}, lalu otomatis mendaftarkan provider.
3. Inertia resolver di app.tsx untuk format 'Module::Path' →
   ./Modules/<Module>/Pages/<Path>.tsx (import.meta.glob). Page bawaan starter kit
   tetap bisa di-resolve (fallback ke ./pages/**).
4. Buat modul Core sebagai contoh pertama, dengan satu halaman "Core::Welcome"
   untuk membuktikan resolver berjalan.
5. Kelas app/Support/ActivePeriod (stub dulu, diisi di S08).
6. Trait HasPublicId (kolom public_id ULID, auto-generate saat creating,
   getRouteKeyName = public_id).
7. Test: make:module membuat struktur yang benar; halaman Core::Welcome ter-render.
```

**Selesai jika:** `php artisan make:module Demo` menghasilkan modul yang langsung bisa dipakai, lalu modul Demo dihapus.

---

#### S03 — Design system & layout dasar

```text
Terapkan design system "Elegant Blue" sesuai §6 VIBE_PLANNING:

1. Token warna brand-50..brand-950 + accent di resources/css/app.css (@theme),
   dan sesuaikan variabel theme shadcn (primary = brand-600, ring = brand-500,
   radius lebih lembut). Font Plus Jakarta Sans.
2. Layouts: AuthLayout (split-screen biru), AppLayout (sidebar gradient navy,
   topbar dengan breadcrumb, slot lonceng notifikasi, role switcher, menu avatar),
   ParentLayout (mobile-first + bottom navigation).
3. config/navigation.ts: definisi menu untuk 4 role sesuai §5 spesifikasi,
   tiap item punya: label, href (route name), icon lucide, permission yang dibutuhkan,
   children opsional. Sementara pakai data dummy.
4. Components/shared: PageHeader, StatCard, StatusBadge (varian: success, warning,
   danger, info, neutral, brand), DataTable (TanStack, paginasi server-side Laravel),
   EmptyState, ConfirmDialog, FilterBar, FileUploader (drag & drop + preview),
   Skeleton, dan Toast (sonner) yang membaca flash message Inertia.
5. Halaman "Core::StyleGuide" (hanya di env local) yang menampilkan semua komponen
   di atas untuk review visual.
Pastikan tampilan responsif dari 360px dan elegan: whitespace lega, kartu rounded-2xl,
border slate-200, shadow halus.
```

**Selesai jika:** halaman StyleGuide tampil rapi di desktop dan mobile, dan Anda puas dengan nuansa birunya. **Revisi desain di sesi ini**, bukan nanti.

---

### FASE 1 — FOUNDATION

#### S04 — Autentikasi multi-role

```text
Buat modul Auth sesuai §4.1 VIBE_PLANNING:

1. Route login terpisah: /login/kepala-sekolah, /login/guru, /login/operator,
   /login/orang-tua, plus halaman /login yang berisi pilihan 4 portal (kartu elegan).
   Satu LoginController dengan parameter role; tiap halaman login memakai AuthLayout
   dengan judul dan ilustrasi berbeda.
2. Validasi: kredensial benar DAN user punya role sesuai portal. Jika tidak,
   pesan generik "Email atau password salah." Akun nonaktif (users.is_active=false)
   ditolak.
3. Simpan session active_role, regenerasi session, redirect ke /<prefix>/dashboard.
4. Middleware alias 'active.role' (contoh active.role:teacher) + middleware
   yang membagikan ke Inertia: user, active_role, daftar role user, permissions
   (array string), dan flash message.
5. Role switcher: POST /switch-role/{role} (validasi user punya role tersebut).
6. Rate limit login 5x/menit per email+IP. Forgot/reset password (email) dalam
   Bahasa Indonesia. Logout mengarah kembali ke portal login role terakhir.
7. Dashboard placeholder untuk tiap role (judul + StatCard dummy).
8. Seeder akun demo sesuai CLAUDE.md.
9. Test Pest: login sukses per role, login ke portal yang salah ditolak,
   akses /guru/dashboard dengan active_role orang tua → 403, rate limit bekerja,
   role switcher.
Hapus/nonaktifkan halaman register publik bawaan starter kit.
```

**Selesai jika:** keempat akun demo bisa login ke portalnya masing-masing, tidak bisa masuk ke portal lain, dan semua test hijau.

---

#### S05 — RBAC

```text
Buat modul Access bagian RBAC:

1. Konfigurasi spatie/laravel-permission. Seeder RolesAndPermissionsSeeder berisi
   role principal, teacher, operator, guardian, applicant dan semua permission
   di matriks §4.2 VIBE_PLANNING (format modul.aksi). Seeder idempoten.
2. Middleware permission spatie didaftarkan; contoh pemakaian di route dashboard.
3. Hook frontend usePermission() / can('attendance.manage') dan komponen <Can>.
4. navigation.ts difilter otomatis berdasarkan permissions + active_role
   dari shared props.
5. Base policy helper di Core: method untuk cek ownership siswa (implementasi
   penuh di S10; untuk sekarang buat interface/kontrak dan TODO yang jelas).
6. Test: menu yang tampil per role sesuai matriks; route dengan permission
   yang tidak dimiliki → 403.
```

---

#### S06 — User management & Role & Permission UI

```text
Lanjutkan modul Access (khusus Operator):

1. /operator/users: daftar user (DataTable: nama, email, role, status aktif,
   login terakhir), filter role, pencarian, buat/edit user, reset password,
   aktif/nonaktifkan, assign multi-role. Operator tidak bisa menonaktifkan
   dirinya sendiri.
2. /operator/roles: daftar role, edit permission per role dalam bentuk matriks
   checkbox yang dikelompokkan per modul. Role inti (5 role) tidak bisa dihapus.
3. Catat last_login_at saat login.
4. Semua aksi di atas masuk audit log (siapkan pemanggilan; activitylog
   dikonfigurasi penuh di S07).
5. Test akses dan validasi.
```

---

#### S07 — Core services: file, audit, settings, notifikasi dasar

```text
Lengkapi modul Core:

1. FileService + tabel files (sesuai §15 spesifikasi + kolom disk, visibility,
   fileable morph, public_id). Dua disk: 'private' (storage/app/private) dan
   'public'. Validasi MIME asli (bukan ekstensi), ukuran maksimum dari config,
   nama file acak, kompres gambar > 1600px dengan intervention/image.
2. Route GET /files/{file:public_id} yang men-stream file privat setelah cek Policy
   (FilePolicy mendelegasikan ke policy pemilik file via morph). Untuk disk
   public, sediakan route fallback jika storage:link tidak tersedia.
3. Audit log: konfigurasi spatie/activitylog (log_name per modul, simpan ip dan
   user_agent di properties). Halaman /operator/audit-log (filter modul, user,
   tanggal) dan versi read-only untuk kepala sekolah.
4. Settings: tabel settings key-value + SettingsService dengan cache; halaman
   /operator/settings (nama sekolah, logo, alamat, kontak, zona waktu,
   ukuran maksimum upload).
5. Tabel notifications Laravel + endpoint daftar/tandai dibaca; lonceng di
   topbar menampilkan jumlah belum dibaca (realtime menyusul di S19).
6. Scheduler: jadwalkan queue:work --stop-when-empty --max-time=50 setiap menit
   dengan withoutOverlapping(), plus model:prune dan activitylog:clean berkala.
7. Test: upload file ditolak jika MIME palsu, akses file privat oleh user
   tak berhak → 403.
```

---

#### S07b — ⛳ Checkpoint: deploy staging pertama

```text
Siapkan deployment ke cPanel shared hosting sesuai §9 VIBE_PLANNING:

1. Script scripts/build-release.sh: composer install --no-dev --optimize-autoloader,
   npm ci && npm run build, lalu buat arsip rilis (kecualikan node_modules,
   .env, tests, storage/logs/*).
2. Dokumen docs/DEPLOY_CPANEL.md langkah demi langkah: struktur folder di
   server, document root, .env produksi, perintah artisan pertama kali
   (key:generate, migrate --force, db:seed khusus roles, optimize),
   cron job, permission folder storage & bootstrap/cache, troubleshooting umum
   (500 error, mixed content, symlink).
3. Route /health (JSON: status app, DB, queue terakhir diproses, versi) yang
   dilindungi token dari .env.
4. Pastikan APP_DEBUG=false tidak membocorkan error, dan halaman error
   403/404/419/500 bergaya Skoolia.
```

**Selesai jika:** login multi-role berjalan di domain staging di cPanel. **Menemukan masalah hosting sekarang jauh lebih murah daripada di akhir proyek.**

---

### FASE 2 — MASTER DATA

#### S08 — Sekolah & periode akademik

```text
Buat modul School:
1. Tabel schools (profil tunggal; satu instalasi = satu sekolah; dukungan
   multi-sekolah di masa depan tidak dikerjakan sekarang), academic_years
   (nama "2026/2027", tanggal mulai-selesai, is_active), semesters
   (GANJIL/GENAP, is_active).
2. Hanya satu tahun ajaran dan satu semester aktif pada satu waktu (dijaga di Action).
3. Lengkapi app/Support/ActivePeriod: academicYear(), semester(), di-cache,
   dibagikan ke Inertia shared props dan ditampilkan sebagai badge periode di topbar.
4. CRUD di /operator/master-data/tahun-ajaran.
5. Test: mengaktifkan tahun ajaran baru menonaktifkan yang lama.
```

#### S09 — Guru & Orang Tua

```text
Buat modul People bagian guru dan orang tua:
1. Tabel teachers (user_id nullable, nip, nuptk, nama, jenis kelamin,
   telepon, foto, status kepegawaian, is_active) dan guardians (user_id nullable,
   nama, nik opsional, telepon/WA, email, pekerjaan, alamat).
2. Membuat guru/orang tua bisa sekaligus membuat akun login (checkbox), dengan
   role otomatis dan password sementara.
3. CRUD di /operator/master-data/guru dan /operator/master-data/orang-tua,
   lengkap dengan pencarian, filter, dan foto (FileService).
4. Halaman profil untuk guru dan orang tua sendiri (edit data terbatas + ganti password).
5. Kepala sekolah: daftar guru read-only di /kepala-sekolah/guru.
```

#### S10 — Siswa + relasi orang tua (N:M) + scope ownership

```text
Lanjutkan modul People bagian siswa:
1. Tabel students (public_id, nis, nisn, nama, jenis kelamin, tempat/tanggal lahir,
   agama, alamat, foto, status: AKTIF/LULUS/PINDAH/KELUAR, tanggal masuk)
   dan pivot guardian_student (relationship: AYAH/IBU/WALI, is_primary).
2. CRUD siswa di /operator/master-data/siswa; tab detail: Identitas, Orang Tua
   (hubungkan/lepas orang tua yang ada atau buat baru), Riwayat Kelas (menyusul).
3. Import Excel siswa + orang tua (template bisa diunduh; validasi per baris;
   laporan baris gagal; diproses via Job dengan chunk).
4. **Scope Student::visibleTo(User $user)** sesuai §4.3 VIBE_PLANNING
   (bagian guru melengkapi setelah S11; tulis test-nya sekarang dengan
   skenario yang akan hijau setelah S11 atau tandai skip dengan alasan).
5. StudentPolicy memakai scope tersebut. Selesaikan helper ownership di Core dari S05.
6. Test ownership: orang tua A tidak bisa membuka detail siswa B (403),
   termasuk lewat manipulasi public_id di URL.
```

#### S11 — Rombel, mata pelajaran, penugasan mengajar

```text
Buat modul Academic bagian master:
1. Tabel classrooms (nama rombel "VII-A", tingkat, academic_year_id,
   homeroom_teacher_id, kapasitas), subjects (kode, nama, kelompok),
   class_enrollments (student, classroom, academic_year, status),
   teaching_assignments (teacher, classroom, subject, academic_year).
2. UI operator: kelola rombel per tahun ajaran; tambah siswa ke rombel
   (multi-select + filter siswa tanpa rombel); set wali kelas; matriks
   penugasan guru–mapel–rombel.
3. Fitur "Naik Kelas": salin struktur rombel ke tahun ajaran baru dan pindahkan
   siswa secara massal (dengan preview sebelum eksekusi).
4. Lengkapi bagian guru pada Student::visibleTo; aktifkan test dari S10.
5. Guru: /guru/kelas (Kelas Saya) dan /guru/siswa (hanya siswa yang diajar).
```

**Tag:** `v0.2-master-data`

---

### FASE 3 — LEARNING

#### S12 — Akademik: jadwal & materi

```text
Lanjutkan modul Academic:
1. Tabel schedules (teaching_assignment_id, hari, jam mulai, jam selesai, ruang)
   dengan validasi bentrok guru dan bentrok rombel.
2. Operator: editor jadwal per rombel dalam tampilan grid mingguan.
3. Guru: jadwal saya (hari ini + mingguan); upload materi per jadwal/mapel
   (file atau tautan, deskripsi).
4. Orang tua: jadwal & mapel anak (dengan StudentSwitcher jika anak > 1).
5. Kepala sekolah: ringkasan jadwal per rombel (read-only).
```

#### S13 — Presensi siswa

```text
Buat modul Attendance (siswa):
1. attendance_sessions (classroom, tanggal, schedule_id nullable untuk mode per mapel,
   dibuat oleh) + attendance_records (session, student, status enum
   HADIR/SAKIT/IZIN/ALPA/TERLAMBAT, catatan). Mode presensi (harian atau per mapel)
   diatur di settings.
2. Guru: pilih kelas → daftar siswa dengan tombol status cepat (default HADIR,
   tombol "Tandai semua hadir"), simpan; bisa diedit di hari yang sama
   (batas edit diatur di settings, lewat dari itu hanya operator).
3. Event AttendanceRecorded → notifikasi ke orang tua jika anak ALPA/TERLAMBAT
   (notification database + email; realtime menyusul di S19).
4. Rekap: harian dan bulanan per kelas/siswa, kalender kehadiran per siswa,
   persentase kehadiran.
5. Orang tua: kehadiran hari ini + riwayat kalender anak.
6. Kepala sekolah: statistik periode + drill-down kelas → siswa.
7. Test: guru tidak bisa presensi kelas yang tidak diajarnya; orang tua hanya melihat anaknya.
```

#### S14 — Presensi guru (sederhana)

```text
Tambahkan presensi guru di modul Attendance:
1. teacher_attendances (teacher, tanggal, jam masuk, jam pulang, status, catatan).
2. Guru: tombol "Presensi Masuk/Pulang" di dashboard (satu kali per hari).
   Validasi lokasi/IP sekolah bersifat opsional lewat settings (daftar IP atau
   radius GPS); default nonaktif.
3. Operator: rekap, koreksi manual (tercatat di audit), input izin/sakit guru.
4. Kepala sekolah: rekap kehadiran guru per periode.
```

#### S15 — Penilaian

```text
Buat modul Assessment:
1. grade_components (teaching_assignment, nama: Tugas/UH/PTS/PAS/Praktik, bobot %,
   semester) — total bobot per assignment wajib 100%.
2. grades (component, student, nilai 0–100, catatan).
3. Guru: input nilai dalam tampilan seperti spreadsheet (navigasi keyboard,
   simpan otomatis per sel dengan debounce, indikator tersimpan), hitung nilai
   akhir otomatis, isi deskripsi capaian per siswa (dengan template kalimat
   berdasarkan rentang nilai yang bisa diubah).
4. Import/export nilai via Excel per assignment.
5. Event GradePublished (guru memilih "Publikasikan ke orang tua" per komponen) →
   notifikasi orang tua.
6. Orang tua: nilai terbaru yang sudah dipublikasikan.
7. Kepala sekolah: rekap nilai per rombel/mapel, distribusi nilai.
```

#### S16 — Raport + workflow approval + PDF

```text
Buat modul ReportCard:
1. report_cards (student, classroom, semester, status enum sesuai §5 VIBE_PLANNING,
   catatan wali kelas, kehadiran tersnapshot, published_at) + report_card_details
   (subject, nilai akhir, predikat, deskripsi) + ekstrakurikuler & prestasi.
2. Enum ReportCardStatus dengan canTransitionTo(); semua transisi lewat
   TransitionReportCardAction yang mencatat siapa, kapan, dan catatan
   (tabel report_card_logs).
3. Alur: wali kelas generate draft dari nilai (Assessment) + rekap presensi →
   isi catatan → SUBMITTED → review wali kelas → operator VALIDATED →
   kepala sekolah APPROVED (bisa massal per rombel) → PUBLISHED.
   Setiap tahap bisa mengembalikan ke REVISION dengan catatan wajib.
4. PDF raport via dompdf (template rapi, logo sekolah dari settings, tanda
   tangan kepala sekolah & wali kelas berupa gambar dari settings); cetak massal
   per rombel diproses sebagai Job dan hasilnya berupa ZIP untuk diunduh.
5. Orang tua hanya melihat/unduh raport berstatus PUBLISHED + notifikasi.
6. Test: transisi ilegal ditolak; orang tua tidak bisa mengakses raport DRAFT;
   guru non-wali tidak bisa review.
```

#### S17 — Portofolio

```text
Buat modul Portfolio:
1. portfolio_categories + portfolios (student, kategori: Karya/Tugas/Prestasi/
   Sertifikat/Proyek/Dokumentasi, judul, deskripsi, tanggal, visibility
   DRAFT/PUBLISHED, created_by) + media via FileService (multi-file).
2. Guru: tambah portofolio untuk satu atau beberapa siswa sekaligus.
3. Orang tua: galeri portofolio anak (grid kartu dengan lightbox, filter kategori).
4. Notifikasi saat portofolio baru dipublikasikan.
```

#### S18 — Kegiatan

```text
Buat modul Activity:
1. activities (judul, jenis HARIAN/KELAS/SEKOLAH, tanggal, lokasi, deskripsi,
   status) + activity_participants (classroom atau student) + activity_media.
2. Guru/operator: buat kegiatan, pilih peserta per kelas atau per siswa,
   upload foto (kompresi otomatis), publikasikan.
3. Orang tua: feed kegiatan anak (gaya timeline, mobile-first).
4. Kalender kegiatan sekolah untuk semua role.
```

**Tag:** `v0.3-learning`

---

### FASE 4 — ENGAGEMENT

#### S19 — Pusher broadcasting & notifikasi realtime

```text
Setup realtime dengan Pusher (tanpa Reverb):
1. Jalankan install broadcasting dan pilih driver Pusher; BROADCAST_CONNECTION=pusher,
   PUSHER_APP_CLUSTER=ap1, useTLS true. Frontend: laravel-echo + pusher-js
   (atau @laravel/echo-react jika tersedia di versi terpasang), konfigurasi
   di resources/js/lib/echo.ts dari env VITE_PUSHER_*.
2. routes/channels.php: private channel App.Models.User.{id} dan pola
   otorisasi yang akan dipakai chat.
3. Notification center: semua Notification yang sudah ada ditambah channel
   'broadcast'. Lonceng topbar menerima notifikasi realtime, menampilkan toast,
   dan update badge. Halaman /notifikasi lengkap (filter, tandai semua dibaca).
4. Event yang menyangkut pengguna aktif memakai ShouldBroadcastNow agar tidak
   menunggu cron queue; notifikasi email tetap lewat queue.
5. Fallback: jika koneksi Pusher gagal, UI tetap berfungsi (fetch ulang saat
   halaman fokus/reconnect). Tampilkan indikator koneksi kecil hanya bila terputus.
6. Hook useEchoChannel() yang otomatis leave saat komponen unmount.
```

#### S20 — Chat: backend

```text
Buat modul Chat (backend):
1. conversations (public_id, type DIRECT, student_id nullable sebagai konteks,
   last_message_at), conversation_participants (user, last_read_at, archived_at,
   muted), messages (conversation, sender, body, type TEXT/IMAGE/FILE, edited_at,
   soft delete), message_attachments via FileService (privat).
2. ChatEligibilityService — siapa boleh memulai percakapan dengan siapa:
   - Orang tua ↔ guru yang mengajar/menjadi wali kelas anaknya di tahun aktif
   - Guru ↔ orang tua dari siswa yang diajarnya
   - Operator & kepala sekolah ↔ semua user aktif
   - Orang tua ↔ orang tua lain: TIDAK diizinkan
   Endpoint "kontak yang bisa dihubungi" berdasarkan aturan ini.
3. Actions: StartConversation (reuse percakapan 1:1 yang sudah ada), SendMessage
   (simpan ke DB dulu, lalu broadcast MessageSent via ShouldBroadcastNow ke private
   channel conversation.{public_id}), MarkAsRead (update last_read_at, broadcast
   ConversationRead), dan ArchiveConversation.
4. Otorisasi channel conversation.{id}: hanya participant. Presence channel
   untuk status online bersifat opsional; buat di balik flag settings.
5. Endpoint pesan dengan cursor pagination (muat pesan lama saat scroll ke atas).
6. Jumlah unread per percakapan dan total (untuk badge menu).
7. Rate limit kirim pesan (misalnya 30/menit); validasi lampiran (gambar/PDF, maks 5MB).
8. Notifikasi pesan baru untuk penerima yang tidak sedang membuka percakapan
   (email digest opsional, bukan per pesan).
9. Test: non-participant tidak bisa membaca pesan maupun subscribe channel;
   orang tua tidak bisa memulai chat dengan guru yang tidak mengajar anaknya.
```

#### S21 — Chat: UI

```text
Buat UI modul Chat untuk semua role:
1. Layout dua panel di desktop (daftar percakapan | ruang chat) dan satu panel
   dengan navigasi di mobile.
2. Daftar percakapan: avatar, nama + peran ("Bu Rina · Wali Kelas VII-A"),
   konteks anak, cuplikan pesan terakhir, waktu relatif, badge unread,
   pencarian, dan tab Arsip.
3. Ruang chat: bubble (pesan sendiri brand-600 teks putih, lawan bicara putih
   dengan border), pemisah tanggal, status terkirim/dibaca (centang), preview
   gambar/lampiran, infinite scroll ke atas, auto-scroll saat pesan baru,
   dan optimistic update dengan retry bila gagal.
4. Komposer: textarea auto-resize, Enter kirim, Shift+Enter baris baru,
   tombol lampiran.
5. Dialog "Percakapan Baru" berisi kontak dari endpoint eligibility, dikelompokkan
   per anak (untuk orang tua) atau per kelas (untuk guru).
6. Realtime via useEchoChannel; badge unread di sidebar/bottom-nav ter-update realtime.
```

#### S22 — Kondisi Anak

```text
Buat modul ChildCondition:
1. child_conditions (public_id, student, guardian_user, jenis enum: SAKIT/
   KELUHAN_KESEHATAN/EMOSIONAL/IZIN_KHUSUS/ALERGI_MAKANAN/LAINNYA, deskripsi,
   tanggal berlaku, target_teacher_id: default wali kelas, status sesuai
   spesifikasi SUBMITTED→RECEIVED→REVIEWED→RESPONDED→CLOSED) + media +
   child_condition_responses.
2. Orang tua: form ramah mobile (pilih anak → jenis → deskripsi → foto opsional),
   riwayat laporan beserta status dan balasan guru.
3. Guru: inbox kondisi anak (prioritas belum dibaca), otomatis RECEIVED saat
   dibuka, tombol tanggapi → RESPONDED, tutup → CLOSED.
4. Jenis SAKIT/IZIN_KHUSUS dapat ditautkan ke presensi hari itu (usulan status
   otomatis untuk guru, bukan diubah langsung).
5. Notifikasi realtime ke guru saat laporan baru dan ke orang tua saat ditanggapi.
6. Widget "Kondisi Anak Hari Ini" di dashboard guru.
Data kondisi anak bersifat sensitif: hanya orang tua pelapor, guru tujuan,
wali kelas, dan kepala sekolah yang boleh melihat. Tulis test untuk ini.
```

#### S23 — Pengumuman & penyempurnaan portal orang tua

```text
1. Modul Announcement: pengumuman dengan target (semua, per role, per rombel),
   lampiran, jadwal tayang, pin; notifikasi saat terbit.
2. Portal orang tua: StudentSwitcher global (anak aktif disimpan di session),
   dashboard ringkas mobile-first, dan halaman "Timeline Anak" yang menggabungkan
   presensi, nilai, kegiatan, portofolio, dan raport secara kronologis.
3. Uji semua halaman orang tua di lebar 360px dan perbaiki.
```

**Tag:** `v0.4-engagement`

---

### FASE 5 — OPERATION

#### S24 — Keuangan dasar

```text
Buat modul Finance (tanpa gateway dulu):
1. fee_types (nama, nominal default, periodik BULANAN/SEKALI, aktif),
   invoices (public_id, nomor INV/2026/09/0001, student, periode, jatuh tempo,
   total, status UNPAID/PENDING/PAID/FAILED/CANCELLED, catatan), invoice_items,
   payments (invoice, metode MANUAL/GATEWAY, jumlah, tanggal, bukti, dicatat oleh).
2. Operator: generate tagihan massal per rombel/tingkat/semua siswa (Job, dengan
   preview jumlah & total sebelum eksekusi), mencegah duplikat untuk periode yang sama;
   potongan/beasiswa per siswa; catat pembayaran manual + upload bukti.
3. Orang tua: daftar tagihan anak, detail, riwayat, kwitansi PDF.
4. Orang tua juga bisa upload bukti transfer → status PENDING → operator verifikasi.
5. Rekap: tunggakan per rombel/siswa, penerimaan per periode, export Excel.
6. Uang disimpan sebagai integer (rupiah) dan diformat di frontend.
7. Notifikasi tagihan baru dan pengingat jatuh tempo (scheduler harian).
```

#### S25 — Payment gateway (Midtrans)

```text
Integrasikan Midtrans Snap ke modul Finance:
1. PaymentGatewayInterface + MidtransGateway (config dari .env, sandbox/production).
2. Orang tua klik "Bayar" → buat transaksi (order_id unik per percobaan)
   → Snap popup.
3. Webhook POST /webhooks/midtrans: dikecualikan dari CSRF, verifikasi
   signature_key, simpan setiap callback ke payment_callbacks, proses secara
   idempotent (callback ganda tidak menggandakan pembayaran), update status invoice,
   lalu kirim notifikasi ke orang tua + operator.
4. Halaman status setelah bayar; jangan pernah mempercayai status dari redirect
   frontend, selalu ambil dari DB/webhook.
5. Tambahkan domain Midtrans ke CSP (dikerjakan penuh di S32).
6. Test webhook: signature salah ditolak, callback ganda aman, status settlement →
   PAID, expire → FAILED.
```

#### S26 — PPDB: pendaftaran

```text
Buat modul Ppdb bagian publik:
1. ppdb_periods (nama, tahun ajaran tujuan, dibuka/ditutup, kuota, jalur,
   persyaratan dokumen dinamis), ppdb_applicants (public_id, nomor pendaftaran,
   akun applicant, data calon siswa, data orang tua, status), ppdb_documents.
2. Registrasi akun applicant (email + verifikasi email) di /ppdb/daftar,
   login di /ppdb/masuk.
3. Formulir multi-step (Data Siswa → Data Orang Tua → Dokumen → Review → Submit)
   dengan simpan draft otomatis.
4. Dashboard applicant: status pendaftaran dengan stepper visual, catatan
   perbaikan dari operator, cetak bukti pendaftaran PDF.
5. Status enum: DRAFT, SUBMITTED, NEEDS_REVISION, VERIFIED, IN_SELECTION,
   ACCEPTED, REJECTED, RE_REGISTERED.
```

#### S27 — PPDB: verifikasi, seleksi, daftar ulang

```text
Lanjutkan modul Ppdb bagian internal:
1. Operator: antrean verifikasi, cek dokumen satu per satu (valid/tidak + catatan),
   kembalikan untuk perbaikan atau verifikasi.
2. Seleksi: daftar terverifikasi dengan nilai/skor sesuai jalur; operator
   mengusulkan, kepala sekolah memutuskan diterima/tidak (massal atau satuan).
3. Pengumuman: publikasi hasil di halaman publik (cek dengan nomor pendaftaran)
   dan notifikasi email.
4. Daftar ulang: applicant yang diterima melengkapi data → operator konfirmasi
   → sistem membuat Student + Guardian + menautkan akun (role applicant diganti
   guardian) + opsional membuat tagihan daftar ulang (Finance).
5. Dashboard PPDB: jumlah pendaftar per status/jalur, grafik harian.
```

#### S28 — CMS: backend

```text
Buat modul Cms (panel operator):
1. website_pages (halaman statis: profil, visi misi, sambutan, struktur
   organisasi, fasilitas, kontak, custom), website_posts (berita & agenda,
   kategori, featured image, SEO title/description), website_galleries,
   website_banners, website_settings.
2. Editor rich text (misalnya TipTap) dengan upload gambar ke disk public,
   sanitasi HTML di server (whitelist tag) untuk mencegah XSS.
3. Status konten DRAFT → REVIEW → PUBLISHED → ARCHIVED, jadwal terbit,
   preview sebelum publish.
4. Data guru di website diambil dari modul People (hanya guru yang ditandai tampil publik).
```

#### S29 — Website publik (Blade)

```text
Buat website profil sekolah memakai Blade + Tailwind (BUKAN Inertia) di
resources/views/public:
1. Halaman: Beranda (banner, sambutan, berita terbaru, agenda, galeri, CTA PPDB),
   Profil, Visi Misi, Guru, Fasilitas, Berita (list + detail), Agenda, Galeri,
   Kontak, PPDB (info + tombol daftar + cek hasil), dan halaman custom.
2. Desain senada Elegant Blue, cepat, dan mobile-first. Tanpa JS berat
   (Alpine.js opsional untuk menu/slider).
3. SEO: meta tag dinamis, Open Graph, sitemap.xml, robots.txt, URL slug,
   dan cache halaman (response cache sederhana, dibersihkan saat konten dipublikasikan).
4. Tautan "Masuk" menuju halaman pilihan portal /login.
```

**Tag:** `v0.5-operation`

---

### FASE 6 — DASHBOARD & REPORTING

#### S30 — Dashboard per role

```text
Buat modul Dashboard sesuai §9 spesifikasi:
1. DashboardService per role dengan query agregat efisien, di-cache 5–10 menit
   (kecuali widget personal seperti chat terbaru).
2. Kepala Sekolah: KPI cards (siswa, guru, kehadiran hari ini %, sakit/izin/alpa,
   PPDB, tagihan vs terbayar), grafik tren kehadiran 30 hari, distribusi nilai,
   raport menunggu approval, aktivitas terbaru.
3. Guru: jadwal hari ini, presensi yang belum diisi, kondisi anak baru, nilai
   yang belum lengkap, raport yang harus diselesaikan, chat terbaru.
4. Operator: data belum lengkap (siswa tanpa orang tua/rombel), verifikasi
   PPDB, pembayaran menunggu verifikasi, konten draft, aktivitas sistem.
5. Orang tua: kartu per anak (kehadiran hari ini, nilai terbaru, kegiatan,
   tagihan jatuh tempo, pengumuman).
6. Grafik dengan Recharts memakai palet brand (seri utama brand-500, seri kedua
   brand-300, status memakai warna semantik).
```

#### S31 — Laporan & export

```text
1. Pusat laporan (/laporan) untuk kepala sekolah & operator: presensi (siswa/guru),
   nilai, raport, keuangan (penerimaan, tunggakan), PPDB — dengan filter periode,
   rombel, dan status.
2. Export PDF (dompdf, kop sekolah) dan Excel (maatwebsite). Export besar diproses
   via Job, lalu notifikasi berisi link unduh (file privat, kedaluwarsa 24 jam).
3. Guru: export rekap presensi & nilai kelasnya sendiri.
```

**Tag:** `v0.6-dashboard`

---

### FASE 7 — HARDENING & DEPLOY

#### S32 — Audit keamanan

```text
Lakukan hardening keamanan menyeluruh:
1. Middleware security headers: CSP (izinkan Pusher, Midtrans, Google Fonts),
   X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy,
   dan HSTS (produksi).
2. Buat test suite "Ownership" yang, untuk SETIAP route yang menerima public_id
   siswa/invoice/raport/percakapan/kondisi anak/file, memastikan user dari role
   lain dan orang tua lain mendapat 403/404. Gunakan dataset agar mudah diperluas.
3. Audit seluruh controller: semua aksi punya authorize(); cari query Student
   yang tidak memakai visibleTo.
4. Cek upload: MIME, ukuran, tidak ada file eksekusi di disk public
   (.htaccess deny php di folder upload).
5. Session: secure cookie, http_only, same_site=lax, dan lifetime wajar;
   logout semua sesi saat ganti password.
6. Laporkan temuan dalam docs/SECURITY_REVIEW.md beserta perbaikannya.
```

#### S33 — Performa

```text
1. Audit N+1 (aktifkan Model::preventLazyLoading di non-produksi) dan perbaiki.
2. Tambahkan index database untuk kolom filter/join yang sering dipakai
   (academic_year_id, student_id, status, tanggal, public_id unik).
3. Cache: settings, ActivePeriod, navigasi, dashboard. Jalankan
   php artisan optimize saat deploy.
4. Frontend: code-splitting per modul (sudah lewat glob), lazy load komponen berat
   (editor, chart), dan ukuran bundle dicek.
5. Gambar: pastikan kompresi dan thumbnail dipakai di list/galeri.
```

#### S34 — Backup, logging, observability

```text
1. spatie/laravel-backup: backup DB harian + file mingguan, retensi 7/4/3,
   notifikasi email jika gagal, dan opsi tujuan backup ke Google Drive/S3 bila tersedia.
2. Logging: channel daily, retensi 14 hari, dan log khusus payment & auth.
3. Halaman /operator/sistem: status queue (job gagal + tombol retry), backup
   terakhir, ukuran storage, versi aplikasi.
4. Mode maintenance dengan halaman bergaya Skoolia.
```

#### S35 — Deploy produksi

```text
1. Perbarui scripts/build-release.sh dan docs/DEPLOY_CPANEL.md berdasarkan
   pengalaman staging.
2. Checklist go-live: APP_ENV=production, APP_DEBUG=false, kunci Pusher & Midtrans
   produksi, mail produksi, cron aktif, backup teruji (restore!), akun demo
   dihapus/dinonaktifkan, password operator kuat, HTTPS aktif + redirect.
3. Seeder produksi: hanya roles/permissions + satu akun operator awal dari .env.
4. Panduan singkat per role dalam docs/PANDUAN_PENGGUNA.md (untuk pelatihan).
```

**Tag:** `v1.0.0`

---

## 9. Panduan Deploy cPanel (ringkas)

### Struktur folder yang disarankan

```text
/home/<user>/
├── skoolia/                 # seluruh project Laravel (DI LUAR public_html)
│   ├── app/ bootstrap/ config/ ...
│   └── public/              # ← document root subdomain diarahkan ke sini
└── public_html/             # website lain / tidak dipakai
```

Jika document root tidak bisa diubah: salin isi `skoolia/public/` ke `public_html/`, lalu ubah path di `public_html/index.php` menjadi `__DIR__.'/../skoolia/vendor/autoload.php'` dan `__DIR__.'/../skoolia/bootstrap/app.php'`.

### Cron job (satu baris saja)

```bash
* * * * * cd /home/<user>/skoolia && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
```

Path PHP berbeda per hosting; cek di cPanel (misalnya `/opt/cpanel/ea-php83/root/usr/bin/php`).

### Alur rilis

1. Lokal: `scripts/build-release.sh` → arsip rilis.
2. Upload & extract di server (File Manager/FTP), jangan menimpa `.env` dan `storage/`.
3. Terminal cPanel: `php artisan down` → `php artisan migrate --force` → `php artisan optimize` → `php artisan up`.
4. Cek `/health`.

---

## 10. Definition of Done per Sesi

Selain DoD di §25 spesifikasi, setiap sesi dianggap selesai jika:

- [ ] Test Pest untuk akses role **dan** ownership lulus.
- [ ] `pint`, `phpstan`, `tsc`, dan `npm run build` bersih.
- [ ] Tampilan dicek di 360px dan 1440px.
- [ ] Empty state, loading state, dan pesan error berbahasa Indonesia tersedia.
- [ ] Aksi penting tercatat di audit log.
- [ ] Seeder demo diperbarui sehingga fitur baru bisa langsung dicoba.
- [ ] Di-commit dengan pesan yang jelas.

---

## 11. Backlog Lanjutan (P3 — setelah v1.0)

- PWA: installable, push notification web, cache offline untuk portal orang tua.
- Integrasi WhatsApp (notifikasi presensi, tagihan, pengumuman) lewat provider resmi.
- Sinkronisasi/impor data Dapodik.
- Multi-sekolah (tenant) untuk yayasan dengan beberapa unit.
- Analitik lanjutan: deteksi dini siswa berisiko (pola presensi + nilai) sebagai decision support.
- Asisten AI untuk guru: draf deskripsi capaian raport dari data nilai (tetap direview guru).
- Aplikasi mobile native jika adopsi portal orang tua tinggi.
