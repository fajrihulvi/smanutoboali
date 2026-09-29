# Security Review — SKOOLIA

**Tanggal:** 29 September 2026 · **Sesi:** S32 · **Versi:** menjelang v1.0.0
**Cakupan:**
- seluruh route aplikasi (portal 4 role + pendaftar PPDB), website publik, API chat, webhook Midtrans;
- upload/unduh file, sesi & autentikasi, ekspor PDF/Excel, dependensi Composer & npm.

## Ringkasan

| Tingkat | Jumlah | Status |
|---|---|---|
| Tinggi | 1 | Diperbaiki |
| Sedang | 3 | Diperbaiki |
| Rendah | 3 | Diperbaiki |
| Informasi / risiko diterima | 3 | Dicatat |

Tidak ditemukan celah akses silang data (IDOR) pada route yang menerima `public_id` siswa, tagihan, kwitansi, raport, percakapan, kondisi anak, file, pendaftaran PPDB, dan ekspor laporan. Hal ini sekarang dijaga oleh test suite **Ownership** (lihat §3).

## 1. Temuan & perbaikan

| ID | Tingkat | Area | Temuan | Perbaikan | Test |
|---|---|---|---|---|---|
| S-01 | Tinggi | HTTP headers | Belum ada Content-Security-Policy, anti-framing, Referrer-Policy, Permissions-Policy, dan HSTS. XSS yang lolos (mis. lewat dependensi) bisa menjalankan script apa pun, dan halaman bisa di-*iframe* situs lain (clickjacking). | Middleware global `SecurityHeaders`. Isinya dijelaskan di bawah tabel. | `HardeningTest` › security headers |
| S-02 | Sedang | Ekspor Excel | *Formula/CSV injection*: teks dari pengguna (nama siswa, catatan) yang diawali `=`, `+`, `-`, `@` ditulis PhpSpreadsheet sebagai **formula**, mis. `=HYPERLINK(...)` di laporan yang dibuka staf TU. | `App\Support\Excel\SafeValueBinder`: semua string disimpan sebagai teks, angka tetap angka. Dipasang di semua ekspor (pusat laporan, tunggakan, penerimaan, nilai guru). Binder global dikembalikan ke bawaan setelah render agar worker antrean tidak terpengaruh. | `HardeningTest` › ekspor Excel |
| S-03 | Sedang | Cache website publik | (a) HTML yang di-cache menyimpan nonce CSP milik request pertama, sehingga script inline gagal di request berikutnya setelah CSP aktif. (b) Setelah rilis baru, HTML cache masih merujuk file aset ber-hash lama (CSS/JS hilang) hingga 10 menit. | (a) Nonce diganti placeholder saat disimpan dan diisi nonce request saat disajikan. (b) Hash manifest Vite ikut kunci cache. | `HardeningTest` › nonce dari cache |
| S-04 | Sedang | Sesi | Ganti password sendiri dan reset password lewat email **tidak** mengeluarkan sesi di perangkat lain. Perangkat yang dicuri atau sesi lama tetap aktif. (Reset oleh operator, nonaktif, dan hapus user sudah benar sejak S07.) | Ganti password sendiri: sesi dibuat ulang (anti *session fixation*), sesi lain dihapus, token "ingat saya" diganti. Reset lewat email: semua sesi dihapus. Tercatat di audit log. | `HardeningTest` › session |
| S-05 | Rendah | Cookie sesi | `SESSION_SECURE_COOKIE` tanpa default, sehingga cookie sesi bisa terkirim lewat HTTP bila `.env` lupa diisi. | Default `secure` = `true` saat `APP_ENV=production`. `http_only=true`, `same_site=lax`, lifetime 120 menit. | `HardeningTest` › cookie sesi |
| S-06 | Rendah | Urutan otorisasi | `PaymentRequest` (bukti transfer), `SendMessageRequest`, dan `UpdateConversationRequest` memvalidasi input **sebelum** otorisasi. Pengguna lain mendapat pesan validasi (302) alih-alih 403. Data tidak bocor karena controller tetap menolak payload valid. | Otorisasi dipindah ke `FormRequest::authorize()` (policy `pay`/`manage` & peserta percakapan). | `OwnershipTest` (bukti transfer, kirim pesan, arsipkan percakapan) |
| S-07 | Rendah | Upload | Tidak ada pelindung di folder upload bila hosting memakai `storage:link` atau folder `storage` ikut terekspos. | `.htaccess`: `storage/` & `storage/app/private` menolak semua; `storage/app/public` mematikan eksekusi PHP/skrip dan listing. Nama file tetap acak dengan ekstensi dari MIME hasil deteksi isi. | `HardeningTest` › `.htaccess` |
| S-08 | Info | Chat | `StartConversationRequest` mencari siswa dari `public_id` sebelum cek kelayakan chat, sehingga ID asing mendapat 404, bukan 403. | Diterima: `public_id` berupa ULID acak (tidak bisa ditebak), dan kelayakan tetap dicek sebelum percakapan dibuat. | — |
| S-09 | Info | Monitoring kepala sekolah | Route presensi per rombel kepala sekolah hanya dibatasi role (tanpa permission spesifik). | Sesuai desain: kepala sekolah berhak melihat semua rombel. | `RouteProtectionTest` |
| S-10 | Info | CSP | `style-src` memuat `'unsafe-inline'` karena sonner, Radix, dan Recharts menyisipkan style saat runtime. | Diterima: `script-src` tetap ketat (nonce, tanpa `unsafe-inline`/`unsafe-eval`). Injeksi CSS murni berdampak kecil. | — |

**Rincian S-01.** Middleware global `SecurityHeaders` mengirim:
- **CSP:**
  - `script-src` ketat dengan nonce per request (`Vite::useCspNonce`), tanpa `unsafe-inline`;
  - sumber yang diizinkan: Midtrans (`*.midtrans.com`), Pusher (`*.pusher.com`, `wss:`), Google Fonts, dan peta Google (frame) di halaman Kontak;
  - `object-src 'none'`, `frame-ancestors 'self'`, `base-uri 'self'`, `form-action 'self'`, dan `upgrade-insecure-requests` di produksi.
- **Header lain:**
  - `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`;
  - `Permissions-Policy` (kamera & mikrofon mati, geolokasi hanya untuk situs sendiri untuk presensi guru);
  - HSTS 1 tahun (hanya produksi & HTTPS).
- **Pengaturan `.env`:** `CSP_ENABLED`, `CSP_REPORT_ONLY` (mode pantau saat go-live), `HSTS_ENABLED`.
- **Verifikasi:** diperiksa di Chrome dengan aset build produksi: tidak ada pelanggaran CSP di website publik dan portal keempat role.

## 2. Audit yang dilakukan (tanpa temuan)

- **Otorisasi controller.**
  - Semua route yang menerima parameter model dipindai otomatis untuk mencari aksi tanpa `authorize()`, policy, atau pemeriksaan setara. Dari 32 kandidat, semuanya ternyata aman:
    - memakai helper (`authorizeManage`, `authorizePost`);
    - memeriksa per item (`bulkTransition` → `canPerform`);
    - query yang dibatasi pemilik (notifikasi);
    - data sekolah yang cukup dibatasi permission (periode PPDB, konten CMS);
    - route publik.
  - Semua `FormRequest` tanpa `authorize()` ditinjau; tiga diperbaiki (S-06).
- **Scope data siswa.** Semua query `Student` di luar portal operator memakai `visibleTo()`, atau dibatasi rombel yang sudah diotorisasi (roster presensi, buku nilai).
- **Route.**
  - Test arsitektur memastikan setiap route portal memakai `auth` + `active.role:<role>`.
  - Setiap route tanpa login harus terdaftar di daftar publik.
  - Setiap route tulis publik memakai `throttle`/`signed`. Login portal & PPDB dibatasi di `AttemptPortalLoginAction`.
- **Upload.** MIME dideteksi dari isi file (finfo), bukan ekstensi; ekstensi file tersimpan diturunkan dari MIME tersebut, dan file disajikan dengan `Content-Type` hasil deteksi + `nosniff`. Gambar di atas 1600 px di-encode ulang (dan sejak S33 dibuatkan thumbnail); gambar kecil disimpan apa adanya. File PHP yang menyamar sebagai `.jpg` ditolak (test sejak S05). File privat hanya lewat `/files/{id}` dengan policy pemilik. `/media/{id}` hanya melayani file publik.
- **SQL.** Tidak ada query mentah yang menyisipkan input pengguna. Satu-satunya interpolasi (`AttendanceRecap`) dibangun dari konstanta enum.
- **XSS.**
  - HTML dari editor CMS disanitasi di server (whitelist, S28), dan konten lain di-escape Blade/React.
  - JSON-LD & script inline website memakai nonce.
- **Lain-lain.**
  - CSRF aktif untuk semua route web. Pengecualian hanya `webhooks/*`, yang diverifikasi dengan signature Midtrans dan idempoten.
  - Parameter `kembali` saat ganti anak aktif dibatasi ke path portal orang tua (anti open redirect).
  - Mass assignment: model memakai `$fillable`, dan update dari request memakai data tervalidasi.
- **Dependensi.** `composer audit` dan `npm audit` (termasuk dev): 0 kerentanan pada 29 Sep 2026.

## 3. Test suite keamanan (`tests/Feature/Security`)

| File | Isi |
|---|---|
| `OwnershipTest.php` | Dataset 27 route: siswa, tagihan, kwitansi, bukti transfer, raport, percakapan (halaman & API), kondisi anak, file privat, pendaftaran PPDB, unduhan ekspor. Aktor sah harus lolos; role lain dan orang tua/guru/pendaftar/operator lain harus 403/404. **Tambahkan baris untuk setiap route baru yang menerima `public_id` data pribadi.** |
| `RouteProtectionTest.php` | Middleware wajib untuk seluruh route (lihat §2). |
| `HardeningTest.php` | Header keamanan, nonce + cache, report-only, HSTS, sesi, cookie, formula injection Excel, `.htaccess`. |

## 4. Checklist sebelum go-live (terkait keamanan)

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` memakai `https://`.
- [ ] HTTPS aktif + redirect HTTP ke HTTPS (AutoSSL cPanel). HSTS otomatis aktif setelah itu.
- [ ] `SESSION_SECURE_COOKIE` tidak diisi `false`.
- [ ] Buka situs dengan `CSP_REPORT_ONLY=true` beberapa jam pertama bila ragu, periksa konsol browser, lalu kembalikan ke `false`.
- [ ] Kunci Midtrans & Pusher produksi; URL notifikasi Midtrans `https://<domain>/webhooks/midtrans`.
- [ ] Akun demo tidak ada di produksi (seeder demo menolak berjalan di produksi); password operator kuat.
- [ ] Folder proyek di luar `public_html`; bila `storage:link` dipakai, pastikan `storage/app/public/.htaccess` ikut terunggah.
