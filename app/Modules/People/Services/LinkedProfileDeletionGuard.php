<?php

namespace App\Modules\People\Services;

use App\Models\User;
use App\Modules\Core\Contracts\UserDeletionGuard;
use App\Modules\People\Models\Guardian;
use App\Modules\People\Models\Teacher;

/**
 * Akun yang masih tertaut ke data guru/orang tua tidak boleh dihapus langsung;
 * lepaskan dari profilnya dulu agar tautan tidak hilang diam-diam.
 */
class LinkedProfileDeletionGuard implements UserDeletionGuard
{
    public function blockingReason(User $user): ?string
    {
        $teacher = Teacher::query()->where('user_id', $user->getKey())->value('name');

        if ($teacher !== null) {
            return "Akun ini masih tertaut ke data guru {$teacher}. Lepaskan akun dari data guru terlebih dahulu.";
        }

        $guardian = Guardian::query()->where('user_id', $user->getKey())->value('name');

        if ($guardian !== null) {
            return "Akun ini masih tertaut ke data orang tua {$guardian}. Lepaskan akun dari data orang tua terlebih dahulu.";
        }

        return null;
    }
}
