<?php

namespace App\Modules\Access\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Keluarkan user dari semua perangkat (session driver database + cookie "ingat saya").
 * `$exceptSessionId` = sesi yang tetap dipertahankan (mis. sesi yang sedang mengganti password).
 */
class UserSessionTerminator
{
    public function terminate(User $user, ?string $exceptSessionId = null): void
    {
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->getKey())
                ->when($exceptSessionId !== null, fn ($q) => $q->where('id', '!=', $exceptSessionId))
                ->delete();
        }

        $user->forceFill(['remember_token' => Str::random(60)])->saveQuietly();
    }
}
