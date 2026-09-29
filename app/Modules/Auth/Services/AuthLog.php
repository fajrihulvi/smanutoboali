<?php

namespace App\Modules\Auth\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Log khusus autentikasi (S34) di storage/logs/auth-YYYY-MM-DD.log: email, id user, IP,
 * user agent. Tidak pernah mencatat password atau token.
 */
class AuthLog
{
    /**
     * @param  array<string, mixed>  $context
     */
    public static function record(string $event, array $context = [], string $level = 'info'): void
    {
        $request = request();

        Log::channel('auth')->log($level, $event, [
            ...$context,
            'ip' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 150, ''),
        ]);
    }
}
