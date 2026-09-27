<?php

namespace App\Modules\Auth\Listeners;

use App\Models\User;
use App\Modules\Core\Services\AuditLogger;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;

/**
 * Audit log modul 'auth': masuk, keluar, dan reset password mandiri.
 */
class RecordAuthActivity
{
    public function __construct(private AuditLogger $audit) {}

    public function handle(Login|Logout|PasswordReset $event): void
    {
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
    }
}
