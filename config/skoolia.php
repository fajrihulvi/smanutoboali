<?php

return [

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

];
