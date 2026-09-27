<?php

namespace App\Modules\Core\Contracts;

use App\Models\User;

/**
 * Penjaga penghapusan akun user. Modul yang menautkan data ke akun (mis. People: guru/orang tua)
 * mendaftarkan implementasinya dengan tag {@see self::TAG}; Access menolak hapus bila ada alasan.
 */
interface UserDeletionGuard
{
    public const TAG = 'core.user_deletion_guards';

    /**
     * Alasan (Bahasa Indonesia) akun tidak boleh dihapus, atau null bila boleh.
     */
    public function blockingReason(User $user): ?string;
}
