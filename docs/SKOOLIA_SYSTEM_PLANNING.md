# SKOOLIA — System Development Planning

## 1. Project Overview

**Nama sistem:** Skoolia  
**Konsep:** Integrated School Management System  
**Platform:** Single Web Application dengan halaman login berbeda berdasarkan role  
**Target role:**
- Kepala Sekolah
- Guru
- Admin/Operator
- Orang Tua

Sistem dibangun sebagai satu aplikasi web terintegrasi. Seluruh role menggunakan backend dan database yang sama, tetapi setelah login diarahkan ke dashboard dan menu sesuai hak akses.

### Domain utama

```text
SKOOLIA
│
├── LEARNING
│   ├── Akademik
│   ├── Presensi
│   ├── Raport
│   ├── Portofolio
│   └── Kegiatan
│
├── OPERATION
│   ├── Keuangan
│   ├── PPDB
│   └── CMS Website Profile
│
└── ENGAGEMENT
    ├── Orang Tua
    ├── Guru
    ├── Data Siswa
    ├── Chat
    └── Kondisi Anak
```

---

# 2. Tujuan Sistem

Skoolia bertujuan menyediakan satu platform untuk:

1. Mengelola proses akademik sekolah.
2. Mengelola kehadiran siswa.
3. Mengelola raport dan perkembangan siswa.
4. Mendokumentasikan portofolio dan kegiatan siswa.
5. Mengelola keuangan sekolah.
6. Mengelola PPDB secara digital.
7. Mengelola website profile sekolah melalui CMS.
8. Menghubungkan guru dan orang tua.
9. Menyediakan informasi siswa secara terintegrasi.
10. Menyediakan komunikasi internal melalui chat.
11. Memungkinkan orang tua menyampaikan kondisi anak kepada sekolah.
12. Menyediakan dashboard monitoring bagi kepala sekolah.

---

# 3. Prinsip Arsitektur

## 3.1 Single Web Application

Semua fitur berada dalam satu aplikasi:

```text
https://skoolia.domain.com
```

Namun login dapat dibedakan berdasarkan role:

```text
https://skoolia.domain.com/login/kepala-sekolah
https://skoolia.domain.com/login/guru
https://skoolia.domain.com/login/operator
https://skoolia.domain.com/login/orang-tua
```

Setelah autentikasi:

```text
Kepala Sekolah → /kepala-sekolah/dashboard
Guru           → /guru/dashboard
Operator       → /operator/dashboard
Orang Tua      → /orang-tua/dashboard
```

> Semua role tetap menggunakan satu database, satu backend, satu sistem autentikasi, dan satu codebase.

---

# 4. Role dan Hak Akses

## 4.1 Kepala Sekolah

Fokus utama: **monitoring, approval, evaluasi dan pengambilan keputusan.**

Akses:
- Dashboard sekolah
- Monitoring siswa
- Monitoring guru
- Monitoring presensi
- Monitoring akademik
- Monitoring raport
- Monitoring kegiatan
- Monitoring portofolio
- Monitoring keuangan
- Monitoring PPDB
- Laporan
- Data sekolah
- Persetujuan tertentu
- Chat sesuai kewenangan

Kepala sekolah pada prinsipnya tidak menjadi operator input seluruh data operasional.

---

## 4.2 Guru

Fokus utama: **aktivitas pembelajaran dan perkembangan siswa.**

Akses:
- Dashboard guru
- Data kelas
- Data siswa yang diajar
- Akademik
- Presensi
- Nilai
- Raport
- Portofolio
- Kegiatan siswa
- Chat dengan orang tua
- Kondisi anak
- Laporan perkembangan siswa

Guru hanya dapat mengakses siswa dan kelas yang menjadi kewenangannya.

---

## 4.3 Admin / Operator

Fokus utama: **administrasi dan operasional sistem sekolah.**

Akses:
- Dashboard operator
- Master data
- Data siswa
- Data guru
- Data orang tua
- Data kelas
- Akademik
- Presensi
- Keuangan
- PPDB
- CMS website
- Pengaturan sekolah
- User management
- Role & permission
- Laporan
- Konfigurasi sistem

Operator memiliki hak akses operasional paling luas.

---

## 4.4 Orang Tua

Fokus utama: **memantau perkembangan anak dan berkomunikasi dengan sekolah.**

Akses:
- Dashboard orang tua
- Data anak
- Presensi anak
- Akademik anak
- Raport
- Portofolio
- Kegiatan anak
- Tagihan
- Pembayaran
- Chat
- Kondisi anak
- Pengumuman sekolah
- Profil orang tua

Orang tua hanya dapat melihat data anak yang terhubung dengan akun tersebut.

---

# 5. Struktur Menu Berdasarkan Role

## 5.1 Kepala Sekolah

```text
Dashboard
│
├── Monitoring Akademik
├── Monitoring Presensi
├── Monitoring Siswa
├── Monitoring Guru
├── Raport
├── Portofolio
├── Kegiatan
├── Keuangan
├── PPDB
├── Laporan
├── Chat
└── Profil
```

## 5.2 Guru

```text
Dashboard
│
├── Kelas Saya
├── Siswa
├── Akademik
│   ├── Jadwal
│   ├── Materi
│   └── Nilai
├── Presensi
├── Raport
├── Portofolio
├── Kegiatan
├── Kondisi Anak
├── Chat
└── Profil
```

## 5.3 Admin / Operator

```text
Dashboard
│
├── Master Data
│   ├── Siswa
│   ├── Guru
│   ├── Orang Tua
│   ├── Kelas
│   └── Tahun Ajaran
│
├── Akademik
├── Presensi
├── Raport
├── Portofolio
├── Kegiatan
├── Keuangan
├── PPDB
├── CMS Website
├── User Management
├── Role & Permission
├── Laporan
├── Pengaturan Sekolah
└── Profil
```

## 5.4 Orang Tua

```text
Dashboard
│
├── Anak Saya
├── Akademik
├── Presensi
├── Raport
├── Portofolio
├── Kegiatan
├── Keuangan
├── Chat
├── Kondisi Anak
├── Pengumuman
└── Profil
```

---

# 6. LEARNING DOMAIN

# 6.1 Modul Akademik

## Tujuan

Mengelola proses akademik sekolah dari tingkat tahun ajaran sampai aktivitas pembelajaran.

## Fitur

### Master Akademik
- Tahun ajaran
- Semester
- Kelas
- Mata pelajaran
- Guru mata pelajaran
- Wali kelas
- Jadwal pelajaran

### Guru
- Melihat jadwal
- Melihat kelas
- Melihat siswa
- Input nilai
- Input materi
- Input aktivitas pembelajaran

### Operator
- Mengelola tahun ajaran
- Mengelola semester
- Mengelola kelas
- Mengelola mata pelajaran
- Mengatur guru
- Mengatur jadwal

### Orang Tua
- Melihat jadwal anak
- Melihat mata pelajaran
- Melihat perkembangan akademik

### Kepala Sekolah
- Monitoring akademik
- Rekap nilai
- Statistik akademik

---

# 6.2 Modul Presensi

## Fitur

- Presensi siswa
- Presensi guru
- Status hadir
- Sakit
- Izin
- Alpa
- Terlambat
- Rekap harian
- Rekap bulanan
- Kalender kehadiran
- Laporan presensi

## Alur Guru

```text
Guru
 ↓
Pilih Kelas
 ↓
Pilih Mata Pelajaran / Jadwal
 ↓
Daftar Siswa
 ↓
Input Status Kehadiran
 ↓
Simpan
 ↓
Database
```

## Alur Orang Tua

```text
Login
 ↓
Pilih Anak
 ↓
Presensi
 ↓
Lihat Kehadiran Hari Ini
 ↓
Lihat Riwayat
```

## Kepala Sekolah

```text
Dashboard
 ↓
Presensi
 ↓
Pilih Periode
 ↓
Statistik
 ↓
Detail Kelas / Siswa
```

---

# 6.3 Modul Raport

## Fitur

- Nilai akademik
- Nilai tugas
- Nilai ujian
- Deskripsi perkembangan
- Kehadiran
- Catatan wali kelas
- Ekstrakurikuler
- Prestasi
- Cetak raport
- Raport digital

## Workflow

```text
Guru Input Nilai
       ↓
Guru Input Deskripsi
       ↓
Wali Kelas Review
       ↓
Operator Validasi
       ↓
Kepala Sekolah Approval
       ↓
Raport Published
       ↓
Orang Tua Melihat Raport
```

Status:

```text
DRAFT
 ↓
SUBMITTED
 ↓
REVIEW
 ↓
APPROVED
 ↓
PUBLISHED
```

---

# 6.4 Modul Portofolio

## Tujuan

Mendokumentasikan perkembangan siswa secara berkelanjutan.

## Jenis konten

- Karya siswa
- Tugas
- Foto kegiatan
- Prestasi
- Sertifikat
- Proyek
- Dokumentasi perkembangan
- Catatan guru

## Workflow

```text
Guru
 ↓
Tambah Portofolio
 ↓
Pilih Siswa
 ↓
Upload Dokumentasi
 ↓
Judul + Deskripsi
 ↓
Publish
 ↓
Orang Tua Melihat
```

---

# 6.5 Modul Kegiatan

## Fitur

- Kegiatan harian
- Kegiatan kelas
- Kegiatan sekolah
- Dokumentasi foto
- Deskripsi kegiatan
- Peserta
- Jadwal kegiatan

## Workflow

```text
Guru / Operator
 ↓
Buat Kegiatan
 ↓
Pilih Kelas / Siswa
 ↓
Upload Foto
 ↓
Deskripsi
 ↓
Publish
 ↓
Orang Tua
```

---

# 7. OPERATION DOMAIN

# 7.1 Modul Keuangan

## Fitur

- Jenis tagihan
- SPP
- Uang kegiatan
- Uang daftar ulang
- Tagihan lainnya
- Invoice
- Pembayaran
- Status pembayaran
- Riwayat transaksi
- Rekap keuangan
- Laporan pembayaran

## Status pembayaran

```text
UNPAID
PENDING
PAID
FAILED
CANCELLED
```

## Workflow

```text
Operator
 ↓
Buat Tagihan
 ↓
Tagihan Siswa
 ↓
Orang Tua
 ↓
Pilih Tagihan
 ↓
Pembayaran
 ↓
Payment Gateway
 ↓
Callback
 ↓
Update Status
 ↓
Bukti Pembayaran
```

---

# 7.2 Modul PPDB

## Actor

- Calon orang tua
- Calon siswa
- Operator PPDB
- Kepala sekolah

## Fitur

- Periode PPDB
- Formulir
- Data calon siswa
- Data orang tua
- Upload dokumen
- Verifikasi dokumen
- Status pendaftaran
- Seleksi
- Pengumuman
- Registrasi ulang

## Workflow

```text
Calon Siswa / Orang Tua
 ↓
Registrasi
 ↓
Isi Formulir
 ↓
Upload Dokumen
 ↓
Submit
 ↓
Operator
 ↓
Verifikasi
 ├── Tidak Valid → Perbaikan
 │                    ↓
 │                  Submit
 │
 └── Valid
       ↓
     Seleksi
       ↓
 Kepala Sekolah
       ↓
 Pengumuman
       ↓
 Diterima / Tidak
       ↓
 Registrasi Ulang
```

---

# 7.3 CMS Website Profile

## Tujuan

Memungkinkan sekolah mengelola website tanpa coding.

## Fitur

### Website
- Profil sekolah
- Visi misi
- Sambutan kepala sekolah
- Struktur organisasi
- Guru
- Fasilitas
- Berita
- Pengumuman
- Agenda
- Galeri
- Kontak
- PPDB
- Banner
- Halaman custom

## Workflow

```text
Operator
 ↓
CMS
 ↓
Buat Konten
 ↓
Draft
 ↓
Preview
 ↓
Publish
 ↓
Website Sekolah
```

Status konten:

```text
DRAFT
 ↓
REVIEW
 ↓
PUBLISHED
 ↓
ARCHIVED
```

---

# 8. ENGAGEMENT DOMAIN

# 8.1 Modul Orang Tua

Orang tua menjadi pusat interaksi dengan sekolah.

## Fitur

- Profil orang tua
- Data anak
- Multiple children
- Akademik anak
- Presensi
- Raport
- Portofolio
- Kegiatan
- Keuangan
- Chat
- Kondisi anak
- Notifikasi

Contoh:

```text
Orang Tua
│
├── Anak 1
│   ├── Akademik
│   ├── Presensi
│   ├── Raport
│   ├── Portofolio
│   └── Kegiatan
│
└── Anak 2
    ├── Akademik
    ├── Presensi
    ├── Raport
    ├── Portofolio
    └── Kegiatan
```

---

# 8.2 Modul Guru

Guru menjadi penghubung antara proses pembelajaran dan orang tua.

Fitur:

- Profil guru
- Kelas
- Siswa
- Presensi
- Nilai
- Raport
- Portofolio
- Kegiatan
- Chat
- Kondisi anak

---

# 8.3 Modul Data Siswa

Data siswa menjadi **central entity** sistem.

## Data utama

```text
Siswa
├── Identitas
├── NIS
├── NISN
├── Jenis Kelamin
├── Tempat/Tanggal Lahir
├── Alamat
├── Foto
├── Kelas
├── Tahun Ajaran
├── Orang Tua
├── Wali
├── Akademik
├── Presensi
├── Raport
├── Portofolio
├── Kegiatan
└── Kondisi Anak
```

Relasi:

```text
ORANG TUA
    │
    │ 1:N
    ▼
 SISWA
    │
    ├──── Akademik
    ├──── Presensi
    ├──── Raport
    ├──── Portofolio
    ├──── Kegiatan
    └──── Kondisi
```

---

# 8.4 Modul Chat

## Tujuan

Menyediakan komunikasi internal antara sekolah dan orang tua.

## Actor

- Orang tua
- Guru
- Operator
- Kepala sekolah

## Fitur

- Conversation
- Direct message
- Attachment
- Image
- Read status
- Timestamp
- Notification
- Block/archive conversation jika diperlukan

## Workflow

```text
Orang Tua
   │
   ▼
Pilih Guru
   │
   ▼
Kirim Pesan
   │
   ▼
Chat Server
   │
   ▼
Guru
   │
   ▼
Balasan
```

---

# 8.5 Modul Kondisi Anak

## Tujuan

Memungkinkan orang tua memberikan informasi kondisi anak kepada guru/sekolah.

## Data

- Anak
- Jenis kondisi
- Deskripsi
- Foto
- Waktu laporan
- Guru tujuan
- Status
- Respons guru

## Status

```text
SUBMITTED
 ↓
RECEIVED
 ↓
REVIEWED
 ↓
RESPONDED
 ↓
CLOSED
```

## Workflow

```text
Orang Tua
 ↓
Pilih Anak
 ↓
Input Kondisi
 ↓
Deskripsi
 ↓
Foto (optional)
 ↓
Kirim
 ↓
Guru
 ↓
Review
 ↓
Respons
 ↓
Orang Tua
```

---

# 9. Dashboard Per Role

## 9.1 Dashboard Kepala Sekolah

KPI:

- Total siswa
- Total guru
- Kehadiran hari ini
- Siswa sakit
- Siswa izin
- Siswa alpa
- Aktivitas hari ini
- Progress akademik
- Status PPDB
- Total tagihan
- Pembayaran
- Grafik perkembangan

---

## 9.2 Dashboard Guru

Widget:

- Jadwal hari ini
- Kelas hari ini
- Jumlah siswa
- Presensi belum selesai
- Tugas/penilaian
- Kondisi anak
- Chat terbaru
- Kegiatan terbaru
- Raport yang perlu diselesaikan

---

## 9.3 Dashboard Operator

Widget:

- Total siswa
- Total guru
- Total orang tua
- PPDB aktif
- Tagihan
- Pembayaran
- Konten website
- Data belum lengkap
- Aktivitas sistem

---

## 9.4 Dashboard Orang Tua

Widget:

- Anak
- Kehadiran hari ini
- Kegiatan hari ini
- Kondisi anak
- Nilai terbaru
- Portofolio terbaru
- Tagihan
- Pengumuman
- Chat terbaru

---

# 10. Database Core

Database minimal perlu memiliki kelompok tabel berikut.

## Authentication & Authorization

```text
users
roles
permissions
role_permissions
user_roles
sessions
```

## Master Data

```text
schools
academic_years
semesters
students
parents
teachers
classes
class_students
subjects
teacher_subjects
class_subjects
```

## Learning

```text
schedules
attendances
attendance_details
grades
grade_components
report_cards
report_card_details
portfolios
portfolio_categories
activities
activity_participants
activity_media
```

## Operation

```text
fee_types
invoices
invoice_items
payments
payment_transactions

ppdb_periods
ppdb_applicants
ppdb_documents
ppdb_verifications
ppdb_selections
ppdb_announcements

website_pages
website_posts
website_categories
website_galleries
website_banners
website_settings
```

## Engagement

```text
conversations
conversation_participants
messages
message_attachments

child_conditions
child_condition_media
child_condition_responses

notifications
```

## System

```text
files
audit_logs
settings
```

---

# 11. Relasi Data Utama

```text
USER
 │
 ├── Parent
 ├── Teacher
 ├── Operator
 └── Principal

PARENT
 │
 └────< STUDENT >──── CLASS
               │
               ├────< ATTENDANCE
               ├────< GRADE
               ├────< REPORT_CARD
               ├────< PORTFOLIO
               ├────< ACTIVITY
               └────< CHILD_CONDITION

TEACHER
 │
 ├────< CLASS
 ├────< SUBJECT
 ├────< GRADE
 ├────< ATTENDANCE
 ├────< PORTFOLIO
 └────< ACTIVITY

STUDENT
 │
 └──── PARENT

PARENT
 │
 └────< CONVERSATION >──── TEACHER
```

---

# 12. Authentication Flow

## Login

Setiap role mempunyai halaman login berbeda tetapi menggunakan service autentikasi yang sama.

```text
Login Page
    │
    ├── /login/kepala-sekolah
    ├── /login/guru
    ├── /login/operator
    └── /login/orang-tua
            │
            ▼
       Auth Service
            │
            ▼
        Validate User
            │
       ┌────┴────┐
       │         │
     Valid     Invalid
       │         │
       ▼         ▼
  Load Role    Error
       │
       ▼
Permission Check
       │
       ▼
Role Dashboard
```

---

# 13. Authorization

Gunakan pendekatan **RBAC (Role-Based Access Control)**.

Contoh:

```text
ROLE: GURU

academic.view       ✓
academic.manage     ✓
attendance.view     ✓
attendance.manage   ✓
finance.view        ✗
finance.manage      ✗
ppdb.manage         ✗
cms.manage          ✗
```

Contoh Kepala Sekolah:

```text
ROLE: KEPALA SEKOLAH

academic.view       ✓
academic.manage     △
attendance.view     ✓
finance.view        ✓
ppdb.view           ✓
cms.manage          ✗
report.approve      ✓
```

---

# 14. Notifikasi

Sistem perlu memiliki notification center.

Trigger:

```text
Presensi
 → Orang Tua

Nilai
 → Orang Tua

Raport Published
 → Orang Tua

Kondisi Anak
 → Guru

Chat Baru
 → User

Tagihan Baru
 → Orang Tua

Pembayaran
 → Orang Tua + Operator

PPDB Status
 → Calon Orang Tua
```

Jenis:

- In-app notification
- Email
- Push notification
- WhatsApp integration sebagai fase lanjutan

---

# 15. File Management

Karena sistem memiliki banyak dokumen dan media, perlu centralized file storage.

Jenis:

```text
/storage
├── students
├── teachers
├── portfolios
├── activities
├── ppdb
├── payments
├── website
├── chat
└── child-conditions
```

Setiap file perlu menyimpan:

```text
id
original_name
stored_name
path
mime_type
size
uploaded_by
created_at
```

---

# 16. Audit Log

Aktivitas penting harus dicatat.

Contoh:

```text
Operator membuat siswa
Guru mengubah nilai
Guru melakukan presensi
Operator membuat tagihan
Orang tua melakukan pembayaran
Operator publish berita
Kepala sekolah approve raport
```

Struktur:

```text
audit_logs

id
user_id
action
module
record_id
old_value
new_value
ip_address
user_agent
created_at
```

---

# 17. Security Requirement

Minimal:

- Password hashing
- Session security
- CSRF protection
- XSS protection
- SQL Injection protection
- RBAC
- File upload validation
- MIME validation
- Maximum file size
- Secure password reset
- Login rate limiting
- Audit log
- HTTPS
- Secure headers
- Access control pada file
- Validasi ownership data

Khusus orang tua:

```text
Parent A
  ↓
Student A

Parent A TIDAK BOLEH mengakses

Student B
```

Validasi ownership harus dilakukan di backend, bukan hanya disembunyikan dari menu.

---

# 18. Recommended Application Structure

Untuk implementasi PHP 8.x:

```text
skoolia/
│
├── app/
│   ├── Controllers/
│   ├── Models/
│   ├── Services/
│   ├── Repositories/
│   ├── Middleware/
│   ├── Policies/
│   ├── Validators/
│   └── Helpers/
│
├── config/
│
├── database/
│   ├── migrations/
│   └── seeders/
│
├── public/
│   ├── index.php
│   ├── assets/
│   └── uploads/
│
├── resources/
│   ├── views/
│   │   ├── auth/
│   │   ├── kepala-sekolah/
│   │   ├── guru/
│   │   ├── operator/
│   │   └── orang-tua/
│   ├── css/
│   └── js/
│
├── routes/
│   ├── web.php
│   └── api.php
│
├── storage/
│
└── tests/
```

---

# 19. Routing Structure

```text
/auth
├── /login/kepala-sekolah
├── /login/guru
├── /login/operator
├── /login/orang-tua
├── /logout
└── /forgot-password

/kepala-sekolah
├── /dashboard
├── /akademik
├── /presensi
├── /siswa
├── /guru
├── /raport
├── /portofolio
├── /kegiatan
├── /keuangan
├── /ppdb
├── /laporan
└── /chat

/guru
├── /dashboard
├── /kelas
├── /siswa
├── /akademik
├── /presensi
├── /raport
├── /portofolio
├── /kegiatan
├── /kondisi-anak
└── /chat

/operator
├── /dashboard
├── /master-data
├── /akademik
├── /presensi
├── /raport
├── /portofolio
├── /kegiatan
├── /keuangan
├── /ppdb
├── /cms
├── /users
├── /roles
├── /reports
└── /settings

/orang-tua
├── /dashboard
├── /anak
├── /akademik
├── /presensi
├── /raport
├── /portofolio
├── /kegiatan
├── /keuangan
├── /kondisi-anak
├── /chat
└── /profil
```

---

# 20. Development Phase

## Phase 1 — Foundation

- Project setup
- Database
- Authentication
- Login multi-role
- RBAC
- Layout
- Dashboard skeleton
- User management
- Master school

**Output:** sistem login dan role sudah berjalan.

---

## Phase 2 — Master Data

- Siswa
- Guru
- Orang tua
- Kelas
- Mata pelajaran
- Tahun ajaran
- Semester

**Output:** master data terintegrasi.

---

## Phase 3 — Learning Core

Implement:

1. Akademik
2. Presensi
3. Nilai
4. Raport
5. Portofolio
6. Kegiatan

**Output:** core learning system berjalan.

---

## Phase 4 — Engagement

Implement:

1. Parent portal
2. Teacher portal
3. Chat
4. Kondisi anak
5. Notifikasi
6. Student timeline

**Output:** komunikasi sekolah-orang tua berjalan.

---

## Phase 5 — Operation

Implement:

1. Keuangan
2. Invoice
3. Payment
4. PPDB
5. CMS website

**Output:** operasional sekolah terintegrasi.

---

## Phase 6 — Dashboard & Reporting

Implement:

- Dashboard kepala sekolah
- Dashboard operator
- Dashboard guru
- Dashboard orang tua
- Grafik
- Statistik
- Laporan
- Export PDF/Excel

---

## Phase 7 — Security & Optimization

- Audit log
- Permission hardening
- File security
- Performance optimization
- Database indexing
- Backup
- Logging
- Error handling
- Security testing

---

# 21. MVP Scope

Untuk versi pertama, fokus pada:

### Authentication
- Multi-role login
- RBAC
- User management

### Master
- Siswa
- Guru
- Orang tua
- Kelas
- Tahun ajaran

### Learning
- Akademik
- Presensi
- Nilai
- Raport
- Kegiatan
- Portofolio

### Engagement
- Parent portal
- Teacher portal
- Chat
- Kondisi anak

### Operation
- Keuangan dasar
- PPDB dasar
- CMS website dasar

### Dashboard
- Kepala sekolah
- Guru
- Operator
- Orang tua

---

# 22. Prioritas Implementasi

```text
P0 — WAJIB
├── Authentication
├── RBAC
├── Master Data
├── Siswa
├── Guru
├── Orang Tua
├── Kelas
├── Akademik
├── Presensi
├── Nilai
└── Raport

P1 — PENTING
├── Portofolio
├── Kegiatan
├── Chat
├── Kondisi Anak
├── Dashboard
└── Keuangan

P2 — PENGEMBANGAN
├── PPDB
├── CMS Website
├── Advanced Reporting
├── Notification Center
└── Payment Gateway

P3 — LANJUTAN
├── Mobile/PWA enhancement
├── WhatsApp integration
├── Advanced analytics
├── AI assistant
└── Predictive analytics
```

---

# 23. End-to-End Student Journey

Sistem idealnya mengikuti perjalanan siswa:

```text
PPDB
 │
 ▼
Calon Siswa
 │
 ▼
Diterima
 │
 ▼
Registrasi
 │
 ▼
Siswa Aktif
 │
 ├───────────────┐
 ▼               ▼
Akademik      Presensi
 │               │
 ▼               ▼
Nilai         Kehadiran
 │               │
 └───────┬───────┘
         ▼
      Kegiatan
         │
         ▼
     Portofolio
         │
         ▼
       Raport
         │
         ▼
   Orang Tua Monitor
```

---

# 24. End-to-End Parent Journey

```text
Login Orang Tua
       │
       ▼
Dashboard
       │
       ▼
Pilih Anak
       │
 ┌─────┼───────────┬───────────┐
 ▼     ▼           ▼           ▼
Akademik Presensi  Kegiatan  Portofolio
 │       │           │           │
 └───────┴───────────┴───────────┘
                │
                ▼
             Raport
                │
       ┌────────┴────────┐
       ▼                 ▼
   Keuangan            Chat
       │                 │
       ▼                 ▼
 Pembayaran        Guru/Sekolah
       │
       ▼
 Kondisi Anak
```

---

# 25. Definition of Done

Sebuah modul dianggap selesai jika:

- Database migration tersedia.
- Model tersedia.
- Repository/service tersedia bila diperlukan.
- Controller tersedia.
- Route tersedia.
- Middleware permission tersedia.
- Form validation tersedia.
- CRUD berjalan.
- Error handling tersedia.
- Audit log untuk aktivitas penting tersedia.
- Responsive UI tersedia.
- Role access sudah diuji.
- Ownership data sudah diuji.
- Tidak ada akses data lintas siswa yang tidak berwenang.
- Test case utama berhasil.
- Dokumentasi endpoint tersedia jika menggunakan API.

---

# 26. Final Product Architecture

Target akhir:

```text
                         ┌───────────────────┐
                         │      SKOOLIA      │
                         │   SINGLE WEB APP  │
                         └─────────┬─────────┘
                                   │
                         ┌─────────▼─────────┐
                         │   AUTH + RBAC      │
                         └─────────┬─────────┘
                                   │
          ┌────────────────────────┼────────────────────────┐
          │                        │                        │
          ▼                        ▼                        ▼
      LEARNING                 OPERATION                ENGAGEMENT
          │                        │                        │
    ┌─────┼─────┐          ┌──────┼──────┐          ┌──────┼──────┐
    │     │     │          │      │      │          │      │      │
 Akademik Presensi Raport  Keuangan PPDB CMS       Parent Guru   Chat
    │     │     │          │      │      │          │      │      │
    └─────┼─────┘          └──────┼──────┘          └──────┼──────┘
          │                       │                        │
          └───────────────────────┼────────────────────────┘
                                  │
                           CORE DATABASE
                                  │
                         ┌────────▼────────┐
                         │    ANALYTICS     │
                         └────────┬────────┘
                                  │
                     ┌────────────┴────────────┐
                     │                         │
              Kepala Sekolah               Operator
```

**Prinsip utama:** satu aplikasi, satu database, satu ekosistem data, tetapi **dashboard, menu, workflow, dan hak akses berbeda berdasarkan role**.
