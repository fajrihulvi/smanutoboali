# Panduan Pengguna Skoolia

Panduan singkat untuk pelatihan. Setiap bagian berisi tugas utama per peran beserta letak menunya. Nama menu mengikuti tampilan aplikasi versi 1.0.

**Daftar isi**

1. [Hal umum untuk semua pengguna](#1-hal-umum-untuk-semua-pengguna)
2. [Operator / Admin Tata Usaha](#2-operator--admin-tata-usaha)
3. [Kepala Sekolah](#3-kepala-sekolah)
4. [Guru & Wali Kelas](#4-guru--wali-kelas)
5. [Orang Tua](#5-orang-tua)
6. [Calon Orang Tua (PPDB)](#6-calon-orang-tua-ppdb)
7. [Pertanyaan yang sering muncul](#7-pertanyaan-yang-sering-muncul)

---

## 1. Hal umum untuk semua pengguna

**Masuk (login)**

1. Buka alamat sekolah, lalu klik **Masuk** di pojok kanan atas website (atau langsung ke `/login`).
2. Pilih portal sesuai peran Anda: Kepala Sekolah, Guru, Operator, atau Orang Tua.
3. Masukkan email dan password dari operator sekolah.
4. Setelah 5 kali salah dalam 1 menit, login dikunci sementara. Tunggu sebentar lalu coba lagi.

**Punya lebih dari satu peran?** (mis. guru yang juga orang tua murid) Pilih peran aktif dari menu profil di pojok kanan atas. Menu yang tampil mengikuti peran aktif.

**Profil & keamanan**

- Menu **Profil** → ubah nama/email dan **ganti password**.
- Setelah password diganti, perangkat lain yang masih login otomatis dikeluarkan.
- **Lupa password**: klik "Lupa password" di halaman login. Tautan reset dikirim ke email Anda.

**Notifikasi**: ikon lonceng di atas menampilkan pemberitahuan terbaru (pengumuman, chat, tagihan, raport, laporan siap diunduh). Klik untuk membuka halamannya.

**Chat**: menu **Chat** untuk percakapan guru ↔ orang tua dan antar staf. Pesan bisa berisi foto/PDF (maks. 5 MB). Percakapan dengan orang tua selalu terkait satu anak.

**Tampilan ponsel**: semua halaman bisa dibuka di ponsel. Portal orang tua dirancang khusus untuk ponsel (menu di bagian bawah layar).

---

## 2. Operator / Admin Tata Usaha

Operator menyiapkan data dan menjalankan administrasi harian. Urutan persiapan awal tahun ajaran:

### 2.1 Persiapan sekali di awal

1. **Pengaturan Sekolah** → nama, logo, alamat, NPSN, kepala sekolah & tanda tangan (untuk raport), batas ukuran upload, mode presensi (harian atau per mapel), KKM & predikat nilai.
2. **Master Data → Tahun Ajaran** → buat tahun ajaran, lalu aktifkan tahun ajaran & semester yang berjalan. Pilihan di pojok atas (mis. "TA 2026/2027 · Ganjil") mengikuti yang aktif.
3. **Master Data → Mata Pelajaran**.
4. **Master Data → Guru** → tambah guru. Centang *buat akun* agar guru bisa login; password sementara ditampilkan sekali.
5. **Master Data → Kelas** → buat rombel, pilih wali kelas, lalu masukkan siswa ke rombel.
6. **Master Data → Siswa** → tambah satu per satu atau **Impor** dari Excel (unduh templatnya dulu). Tautkan orang tua di halaman ubah siswa.
7. **Master Data → Orang Tua** → buat akun orang tua agar bisa memakai portal.
8. **Akademik → Penugasan Mengajar** (guru–mapel–rombel), lalu **Akademik → Jadwal Pelajaran**.
9. **User Management** & **Role & Permission** → kelola akun dan hak akses. Menyembunyikan menu tidak sama dengan mencabut akses: selalu cabut *permission*-nya.

### 2.2 Tugas rutin

| Tugas | Menu |
|---|---|
| Rekap/koreksi presensi siswa & guru | **Presensi → Presensi Siswa / Presensi Guru** |
| Validasi raport setelah direview wali kelas | **Raport** → pilih rombel → validasi (atau kembalikan untuk revisi dengan catatan) |
| Pengumuman untuk semua / peran / rombel tertentu, bisa dijadwalkan | **Pengumuman → Buat pengumuman** |
| Kegiatan sekolah & dokumentasi | **Kegiatan** |

### 2.3 Keuangan

1. **Keuangan → Jenis Biaya** (SPP, uang kegiatan, dsb.) dan **Potongan** (beasiswa per siswa: persen atau nominal).
2. **Keuangan → Buat Tagihan** → pilih jenis biaya, bulan, jatuh tempo, dan sasaran (semua / rombel / siswa). Tagihan massal diproses di latar belakang (±1 menit); tagihan ganda otomatis dilewati.
3. **Keuangan → Verifikasi** → periksa bukti transfer dari orang tua, lalu **Terima** (kwitansi terbit otomatis) atau **Tolak** dengan alasan.
4. Pembayaran tunai: buka tagihan → **Catat pembayaran** (boleh dicicil).
5. Pembayaran online (bila Midtrans aktif) tercatat lunas otomatis.
6. **Keuangan → Rekap** → penerimaan per bulan, tunggakan per rombel/siswa.
7. Pengingat otomatis dikirim ke orang tua H-3 dan H+1 jatuh tempo.

### 2.4 PPDB

1. **PPDB → Periode & Jalur** → buat periode (tanggal buka–tutup, tanggal pengumuman), jalur, kuota, dan dokumen persyaratan. Aktifkan periode.
2. **PPDB → Pendaftar** → buka pendaftar berstatus *Terkirim*:
   - periksa dokumen satu per satu (Valid / Tidak valid + alasan);
   - lalu **Verifikasi**, atau **Kembalikan untuk perbaikan** (pendaftar diberi tahu).
3. **PPDB → Seleksi** → isi skor, klik **Hitung usulan** (peringkat sesuai kuota). Kepala sekolah yang memutuskan.
4. Setelah keputusan kepala sekolah: **Umumkan hasil** di halaman periode. Pendaftar diberi tahu dan bisa mengecek di website (`/ppdb/hasil`).
5. **Daftar ulang**: buka pendaftar yang sudah mengirim daftar ulang → isi NIS (ada saran nomor) → **Konfirmasi**. Sistem membuat data siswa & orang tua, dan opsional tagihan daftar ulang.
6. **PPDB → Dashboard** → jumlah pendaftar per status & jalur, grafik harian.

### 2.5 Website sekolah (CMS)

1. **CMS Website → Halaman** → isi Profil, Visi Misi, Sambutan, Struktur Organisasi, Fasilitas, Kontak, lalu ubah status ke **Terbit**. Halaman yang belum terbit tidak tampil di website.
2. **Berita** & **Agenda** → tulis dengan editor (judul, tebal, daftar, tautan, gambar). Bisa **Pratinjau** sebelum terbit dan **dijadwalkan** tayang.
3. **Galeri** → buat album, unggah banyak foto sekaligus, beri keterangan, atur urutan.
4. **Banner** → gambar besar di beranda (atur urutan & periode tayang).
5. **Guru di Website** → centang guru yang tampil & jabatannya.
6. **Pengaturan** → slogan, kontak, WhatsApp, media sosial, peta Google Maps, teks ajakan PPDB.

### 2.6 Laporan, audit, sistem

- **Laporan** → pilih jenis (presensi siswa/guru, nilai, raport, penerimaan, tunggakan, PPDB), atur filter, periksa pratinjau, lalu **Unduh PDF/Excel**. Laporan lebih dari 300 baris disiapkan di latar belakang; notifikasi berisi tautan unduh (berlaku 24 jam) dikirim saat siap.
- **Audit Log** → riwayat siapa mengubah apa dan kapan.
- **Sistem** → kondisi aplikasi:
  - cron & antrean, **job gagal** (tombol *Ulang* / *Hapus*);
  - backup terakhir (+ **Backup database sekarang**);
  - ukuran penyimpanan.
  - Bila ada tanda "Perlu dicek", hubungi pengelola IT.

---

## 3. Kepala Sekolah

| Tugas | Menu |
|---|---|
| Ringkasan sekolah: siswa, guru, kehadiran hari ini, tagihan terbayar, PPDB, **tren kehadiran 30 hari**, **distribusi nilai**, raport menunggu persetujuan | **Dashboard** |
| Jadwal & rekap nilai per rombel | **Monitoring Akademik → Jadwal / Nilai** |
| Kehadiran siswa per rombel (bisa dibuka sampai per siswa) & kehadiran guru | **Monitoring Presensi → Siswa / Guru** |
| Data siswa & guru (baca saja) | **Monitoring Siswa**, **Monitoring Guru** |
| **Menyetujui raport** yang sudah divalidasi operator (satu per satu atau massal), atau kembalikan untuk revisi | **Raport** |
| **Memutuskan hasil seleksi PPDB**: setujui semua usulan per jalur, atau terima/tolak satuan | **PPDB → Seleksi** |
| Penerimaan & tunggakan (baca saja) | **Keuangan** |
| Pengumuman sekolah | **Pengumuman** |
| Laporan PDF/Excel | **Laporan** |
| Riwayat aktivitas pengguna | **Audit Log** |

Grafik di dashboard: arahkan kursor ke grafik untuk melihat angka per tanggal, atau klik **Tampilkan tabel** untuk melihat datanya dalam bentuk tabel.

---

## 4. Guru & Wali Kelas

**Dashboard** menampilkan tugas hari ini:
- jadwal mengajar dan presensi yang belum diisi (tombol **Isi**);
- nilai yang belum lengkap dan tugas raport;
- kondisi anak baru dari orang tua, dan chat terbaru.

| Tugas | Menu | Catatan |
|---|---|---|
| Presensi mandiri (masuk & pulang) | **Dashboard** → kartu *Presensi Saya* | Bisa memerlukan lokasi sekolah bila diaktifkan |
| Isi presensi siswa | **Presensi** → pilih rombel/jadwal hari ini | Batas koreksi mundur mengikuti pengaturan sekolah; siswa alpa/terlambat memicu notifikasi ke orang tua |
| Daftar siswa yang diajar | **Kelas Saya**, **Siswa** | |
| Jadwal mengajar | **Akademik → Jadwal** | |
| Materi pembelajaran (file/tautan) untuk rombel | **Akademik → Materi** | |
| **Nilai**: buat komponen (tugas, UH, PTS, PAS) dengan bobot total 100%, isi nilai (tersimpan otomatis per sel), terbitkan agar terlihat orang tua | **Akademik → Nilai** → pilih mapel-rombel | Bisa **impor/ekspor Excel**; deskripsi capaian bisa diisi dari templat predikat |
| Konfirmasi nilai mapel untuk raport | **Akademik → Nilai** / **Raport** | Muncul di dashboard bila raport rombel sudah dibuat |
| **Wali kelas**: lengkapi catatan, ekstrakurikuler, prestasi, lalu review & ajukan raport | **Raport** | Raport yang dikembalikan untuk revisi ditandai |
| Portofolio karya siswa | **Portofolio** | Bisa privat (hanya sekolah) atau terbit (orang tua melihat) |
| Kegiatan kelas/sekolah | **Kegiatan** | |
| Tanggapi laporan kondisi anak dari orang tua | **Kondisi Anak** | Tandai diterima → ditinjau → tanggapi/selesai |
| Laporan presensi & nilai **kelas sendiri** (PDF/Excel) | **Laporan** | Hanya rombel yang diajar/diwalikan |

---

## 5. Orang Tua

Portal orang tua dibuat untuk ponsel. Menu utama ada di bawah layar; menu lain lewat ikon ☰.

1. **Beranda**:
   - **kartu per anak** (kehadiran hari ini & tagihan) — ketuk kartu untuk mengganti anak aktif;
   - tagihan yang belum lunas, kegiatan hari ini, nilai terbaru, kondisi anak, portofolio, pengumuman, chat terbaru.
2. **Ganti anak** (bila punya lebih dari satu anak): pilih nama anak di bagian atas. Semua halaman mengikuti anak yang dipilih.
3. **Presensi** → kalender kehadiran bulanan. **Nilai** & **Akademik** → nilai yang sudah diterbitkan guru, jadwal, materi.
4. **Raport** → lihat & unduh PDF raport yang sudah terbit.
5. **Tagihan**:
   - buka tagihan → **Bayar online** (bila tersedia; kartu, e-wallet, virtual account), atau **Kirim bukti transfer** (foto/PDF);
   - status berubah setelah diverifikasi sekolah, lalu kwitansi bisa diunduh.
6. **Kondisi Anak** → laporkan anak sakit/izin/keluhan kesehatan/kondisi emosional ke wali kelas (bisa melampirkan foto surat dokter).
7. **Chat** → hubungi wali kelas atau guru mapel anak.
8. **Timeline Anak** → ringkasan kejadian per bulan (ketidakhadiran, portofolio, laporan kondisi).
9. **Pengumuman** dan **Kegiatan** sekolah.

---

## 6. Calon Orang Tua (PPDB)

1. Website sekolah → menu **PPDB** → baca jadwal, jalur, kuota, dan dokumen yang diperlukan.
2. **Daftar sekarang** → buat akun dengan email aktif → buka email dan klik tautan verifikasi.
3. Masuk lewat **PPDB → Masuk akun pendaftar**:
   - pilih jalur, lalu isi formulir 4 langkah (data siswa, orang tua, dokumen, periksa & kirim);
   - isian **tersimpan otomatis** sebagai draf.
4. Setelah dikirim, unduh **bukti pendaftaran** (berisi nomor pendaftaran). Bila sekolah meminta perbaikan, perbaiki bagian yang ditandai lalu kirim ulang.
5. Setelah hasil diumumkan: cek di akun, atau di website **PPDB → Cek hasil** dengan nomor pendaftaran + tanggal lahir.
6. Bila diterima, lengkapi **Daftar ulang** di akun. Setelah dikonfirmasi sekolah, akun berubah menjadi akun **orang tua** siswa.

---

## 7. Pertanyaan yang sering muncul

| Pertanyaan | Jawaban |
|---|---|
| Menu yang saya perlukan tidak ada | Menu mengikuti peran aktif & hak akses. Periksa peran aktif (menu profil), atau minta operator memeriksa *Role & Permission*. |
| "Halaman sudah kedaluwarsa (419)" | Halaman terlalu lama dibuka. Klik **Muat ulang**, lalu ulangi. |
| Nilai tidak muncul di portal orang tua | Guru belum **menerbitkan** komponen nilai tersebut. |
| Raport tidak bisa diunduh orang tua | Raport belum disetujui kepala sekolah dan diterbitkan. |
| Bukti transfer sudah dikirim tapi tagihan belum lunas | Menunggu verifikasi operator; status *Menunggu verifikasi* tampil di tagihan. |
| Laporan besar belum bisa diunduh | Laporan sedang disiapkan (±1–2 menit). Tunggu notifikasi, atau lihat daftar *Ekspor terbaru* di halaman Laporan. |
| Unggah file gagal | Ukuran melebihi batas (lihat keterangan di formulir) atau jenis file tidak didukung (gunakan JPG, PNG, WebP, atau PDF). |
| Lupa email akun | Hubungi operator sekolah. |
