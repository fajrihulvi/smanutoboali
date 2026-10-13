# S38 — Akun default produksi & panduan unggah lewat SSH

## Rencana

1. `ProductionSeeder` membuat empat akun default tanpa data demo: operator, kepala sekolah, guru, orang tua `@smanutoboali.sch.id`.
2. Password awal dari `.env`; wajib diganti saat login pertama.
3. Panduan unggah ke cPanel lewat SSH di `docs/DEPLOY_CPANEL.md`.

## Keputusan (dari jawaban pengguna)

- **Domain** `smanutoboali.sch.id`; bisa diubah lewat `ACCOUNT_DOMAIN`.
- **Seeder tanpa data demo** — hanya role & permission, profil sekolah, dan 4 akun.
- **Password awal** `DEFAULT_ACCOUNT_PASSWORD` (aturan password produksi: min. 12 karakter, huruf besar & kecil, angka, simbol, tidak bocor). `OPERATOR_NAME/EMAIL/PASSWORD` dihapus; `skoolia:make-operator` tetap ada.
- **Wajib ganti password:** kolom `users.must_change_password`.
  - `active.role` mengalihkan semua halaman ke *Pengaturan Akun → Keamanan* (permintaan JSON → 403);
  - flag hilang otomatis setiap password diganti (profil, reset, atau oleh operator).
- **Akun guru & orang tua tidak dibuatkan data guru/siswa palsu.** Operator menautkannya lewat Master Data dengan email yang sama (alur "pakai akun yang ada").

## Hasil

- `ProductionSeeder`: 4 akun, idempoten (akun yang ada tidak diubah), password lemah ditolak, password kosong → peringatan tanpa akun.
- Halaman Keamanan menampilkan peringatan "Ganti password awal"; setelah berhasil diarahkan ke dashboard.
- `skoolia:go-live-check`: "DEFAULT_ACCOUNT_PASSWORD sudah dihapus" + "akun default sudah mengganti password awal" (peringatan).
- `docs/DEPLOY_CPANEL.md`:
  - §2a SSH: aktifkan, kunci ed25519, Authorize, `~/.ssh/config`, `scp`, `sha256sum -c`, `unzip`, PHP CLI;
  - §3/§4 akun default; §10 update lewat `rsync`; troubleshooting SSH.
- 668 test Pest lulus; pint, phpstan, eslint, prettier, tsc, dan build hijau.
