<?php

namespace App\Modules\Core\Contracts;

use App\Models\User;

/**
 * Kontrak cakupan data siswa (VIBE_PLANNING §4.3), dipakai semua Policy yang
 * menyangkut siswa: presensi, nilai, raport, portofolio, kondisi anak, tagihan, chat.
 *
 * TODO(S10): implementasi di modul People dengan Student::visibleTo($user),
 * lalu rebind kontrak ini di PeopleServiceProvider.
 */
interface StudentOwnershipGate
{
    /**
     * Boleh-kah user MELIHAT data siswa ini?
     */
    public function canViewStudent(User $user, int $studentId): bool;
}
