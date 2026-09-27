<?php

namespace App\Modules\Access\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Keluarkan user dari semua perangkat (session driver database + cookie "ingat saya").
 */
class UserSessionTerminator
{
    public function terminate(User $user): void
    {
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->getKey())->delete();
        }

        $user->forceFill(['remember_token' => Str::random(60)])->saveQuietly();
    }
}
