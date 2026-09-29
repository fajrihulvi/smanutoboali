<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Rilis & operasional
    |--------------------------------------------------------------------------
    |
    | version      : ditampilkan di /health (diisi skrip rilis).
    | force_https  : paksa URL https (hosting di balik proxy/SSL terminator).
    | health_token : token untuk GET /health; kosong = endpoint dimatikan (404).
    | trusted_proxies : proxy yang dipercaya agar https & IP klien terdeteksi.
    |
    */

    // APP_VERSION (bila diisi) > file VERSION dari skrip rilis > penanda dev.
    'version' => env('APP_VERSION') ?: (is_file(base_path('VERSION')) ? trim((string) file_get_contents(base_path('VERSION'))) : '0.1.0-dev'),
    'force_https' => (bool) env('APP_FORCE_HTTPS', false),
    'health_token' => env('HEALTH_TOKEN'),
    // '*' atau daftar IP dipisah koma (cPanel/Cloudflare di depan aplikasi).
    'trusted_proxies' => env('TRUSTED_PROXIES'),

    /*
    |--------------------------------------------------------------------------
    | Upload
    |--------------------------------------------------------------------------
    |
    | default_max_kb : batas awal sebelum diubah di Pengaturan Sekolah.
    | hard_max_kb    : batas tertinggi yang boleh diset lewat Pengaturan
    |                  (sesuaikan dengan upload_max_filesize PHP di hosting).
    | image_max_dimension : gambar lebih besar dari ini (px) diperkecil.
    |
    */

    'upload' => [
        'default_max_kb' => (int) env('UPLOAD_MAX_SIZE_KB', 5120),
        'hard_max_kb' => 20480,
        'image_max_dimension' => 1600,
        'image_quality' => 80,
    ],

    /*
    |--------------------------------------------------------------------------
    | Laporan (S31)
    |--------------------------------------------------------------------------
    |
    | direct_limit : laporan dengan baris <= nilai ini langsung diunduh;
    |                lebih besar diproses Job (antrean database lewat scheduler).
    |
    */

    'reports' => [
        'direct_limit' => (int) env('REPORT_DIRECT_LIMIT', 300),
    ],

];
