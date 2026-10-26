<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Verifikasi email akun PPDB dihapus: akun calon orang tua yang terlanjur menunggu
 * verifikasi dianggap terverifikasi agar bisa langsung mendaftarkan calon siswa.
 */
return new class extends Migration
{
    public function up(): void
    {
        $applicantIds = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('roles.name', 'applicant')
            ->where('model_has_roles.model_type', (new User)->getMorphClass())
            ->pluck('model_has_roles.model_id');

        DB::table('users')
            ->whereIn('id', $applicantIds)
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        // Tidak dikembalikan: tidak bisa membedakan akun yang dulu belum terverifikasi.
    }
};
