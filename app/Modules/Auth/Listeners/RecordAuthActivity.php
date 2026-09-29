<?php

namespace App\Modules\Auth\Listeners;

use App\Models\User;
use App\Modules\Auth\Services\AuthLog;
use App\Modules\Core\Services\AuditLogger;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

/**
 * Audit log modul 'auth' (masuk, keluar, reset password mandiri) + log file `auth` (S34),
 * termasuk lockout karena terlalu banyak percobaan login.
 */
class RecordAuthActivity
{
    public function __construct(private AuditLogger $audit) {}

    public function handle(Login|Logout|PasswordReset|Lockout $event): void
    {
        if ($event instanceof Lockout) {
            AuthLog::record('lockout', ['email' => Str::lower((string) $event->request->input('email'))], 'warning');

            return;
        }

        $user = $event->user;

        if (! $user instanceof User) {
            return;
        }

        [$name, $description] = match (true) {
            $event instanceof Login => ['login', 'Masuk ke aplikasi'],
            $event instanceof Logout => ['logout', 'Keluar dari aplikasi'],
            default => ['password_reset', 'Mengatur ulang password lewat email'],
        };

        $this->audit->record('auth', $name, $description, $user, causer: $user);
        AuthLog::record($name, ['user_id' => $user->id, 'email' => $user->email]);
    }
}
