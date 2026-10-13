<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Isi alamat SMA NU Toboali untuk kop raport bila belum diisi di Pengaturan Sekolah.
 */
return new class extends Migration
{
    private const ADDRESS = 'Jl. Rias, Desa Rias, Kec. Toboali, Kab. Bangka Selatan, Toboali, Kab. Bangka Selatan, Prov. Kepulauan Bangka Belitung';

    public function up(): void
    {
        DB::table('schools')
            ->where(fn ($query) => $query->whereNull('address')->orWhere('address', ''))
            ->update(['address' => self::ADDRESS, 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('schools')->where('address', self::ADDRESS)->update(['address' => null]);
    }
};
