# S36 — Impor Excel guru (tambahan pasca v1.0.0)

## Rencana

1. Impor guru di `/operator/master-data/guru/impor`, mengikuti pola impor siswa (S10):
   - file disimpan privat lewat FileService (MIME diperiksa dari isi);
   - diproses `ImportTeachersJob` di antrean (driver database, dibaca per chunk 200 baris);
   - riwayat impor + baris gagal ditampilkan di halaman.
2. Template `.xlsx` di `/operator/master-data/guru/impor/template`.
3. Tombol "Impor Excel" di halaman Data Guru.
4. Test: akses per role, template, baris valid/gagal, duplikat, file palsu.

## Keputusan

- **Impor hanya membuat data guru, tanpa akun login.** Password sementara tidak bisa diserahkan satu per satu dari proses massal di latar belakang, jadi akun tetap dibuat per guru lewat halaman Ubah (AccountSection).
- **Kolom template:** `nip`, `nuptk`, `nama`, `jenis_kelamin`, `status_kepegawaian`, `telepon`.
  - wajib: nama, jenis kelamin, status kepegawaian;
  - aturan validasi sama dengan form guru (NIP 18 digit, NUPTK 16 digit, unik termasuk data terhapus);
  - guru hasil impor langsung aktif.
- **Masukan longgar yang diterima:**
  - jenis kelamin: L/P/Laki-laki/Perempuan;
  - status kepegawaian: kode (PNS, GTY, …) atau label ("Guru Tetap Yayasan");
  - NIP berspasi ("19800101 200501 1 001");
  - telepon tanpa 0 di depan.
- **NIP/NUPTK yang terbaca sebagai angka di .xlsx ditolak dengan pesan khusus.** Excel hanya menyimpan 15 digit presisi, sehingga digit terakhir NIP sudah berubah. Template memformat kolom NIP/NUPTK/telepon sebagai Teks.
- **Tabel terpisah `teacher_imports`** (struktur sama dengan `student_imports`), bukan tabel impor umum, agar tidak mengubah skema yang sudah rilis.
- **UI dipakai bersama:** isi halaman impor siswa dipindah ke `Components/ImportWorkspace.tsx`; tipe `StudentImportRow` diganti nama menjadi `ImportRow`.

## Hasil

- **Backend:**
  - `TeacherImport` + migrasi, `TeacherImportRowProcessor`, `TeachersImport`, `ImportTeachersJob`;
  - `StartTeacherImportAction`, `TeacherImportRequest`, `TeacherImportResource`, `TeacherImportController`, `TeacherImportTemplate`;
  - route `operator.teachers.import`, `.import.store`, `.import.template`.
- **Audit:** satu entri audit per file ("Import guru …: X berhasil, Y gagal"). `CreateTeacherAction` mendapat parameter `audit` (sama seperti `CreateStudentAction`).
- **Pemeriksaan di browser:**
  - dicek di 1440 & 360 px, tanpa overflow dan tanpa error konsol;
  - unduh template, unggah CSV, dan proses antrean berhasil; baris gagal tampil.
- 8 test baru (`tests/Feature/People/TeacherImportTest.php`). 660 test Pest + 31 Vitest lulus; pint, phpstan, eslint, prettier, tsc, dan build hijau.
