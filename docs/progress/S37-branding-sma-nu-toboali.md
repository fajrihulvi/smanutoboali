# S37 — Branding SMA NU Toboali

## Rencana

1. Logo `public/images/logo.png` dipakai di halaman login, sidebar, header portal orang tua & PPDB, website publik, halaman error, favicon, dan kop PDF (raport, laporan, kwitansi, bukti PPDB).
2. Nama "Skoolia" yang tampil ke pengguna diganti "SMA NU Toboali".
3. Warna brand biru → hijau (diturunkan dari logo) dengan gradasi.

## Keputusan

- **Logo bawaan, bukan logo tetap.**
  - logo yang diunggah di Pengaturan Sekolah tetap diutamakan; `public/images/logo.png` dipakai bila belum ada;
  - `SchoolProfile::logo_url` tidak pernah null lagi; `SchoolLogo::dataUri()` dipakai bersama oleh semua PDF.
- **Nama di logo aplikasi mengikuti nama sekolah** (`school.name`), sehingga perubahan di Pengaturan Sekolah langsung terlihat. Nama aplikasi (`APP_NAME`, judul tab, email) = "SMA NU Toboali".
- **Nama bawaan sekolah** = `School::DEFAULT_NAME` ("SMA NU Toboali").
  - pemeriksaan go-live "profil sekolah sudah diisi" kini mengecek NPSN & alamat, bukan nama;
  - `.env.example`: `SCHOOL_NAME="SMA NU Toboali"`, `SCHOOL_LEVEL=SMA`.
- **Palet hijau di `brand.css`** (satu-satunya tempat hex brand), dengan aksen lime `#a3e635`.
  - gradasi: sidebar 950→900→800, header portal orang tua/PPDB, tombol utama (`from-brand-500 to-brand-700`), panel login, hero website;
  - warna status (info biru, sukses, peringatan) tidak diubah karena maknanya semantik.
- **Identitas teknis tetap `skoolia`**: perintah `skoolia:*`, `config/skoolia.php`, nama event, cache key, akun demo `@skoolia.test`, dan nama dokumen. Semuanya tidak terlihat pengguna.

## Hasil

- **Logo:**
  - `AppLogo` memakai logo + nama sekolah;
  - halaman pilih portal menampilkan logo besar;
  - halaman error memakai logo + nama aplikasi;
  - favicon (`favicon.ico` 48 px, `apple-touch-icon.png` 180 px, `images/favicon-192.png`) dibuat dari logo; `favicon.svg` lama dihapus.
- **Kop PDF:** raport & laporan memakai logo bawaan; kwitansi & bukti PPDB kini juga memakai logo. Garis/aksen kop berwarna hijau.
- **Warna:** `theme-color` hijau tua; demo banner CMS hijau.
- **Teks:** email reset password & notifikasi selamat datang memakai `config('app.name')`; footer website "Sistem Informasi Sekolah".
- **Pemeriksaan visual:** login (1440/360), dashboard operator, portal orang tua 360, website, 404, raport, kwitansi, bukti PPDB — tanpa overflow, tanpa gambar rusak, tanpa error konsol.
- 661 test Pest + 31 Vitest lulus; pint, phpstan, eslint, prettier, tsc, dan build hijau.
