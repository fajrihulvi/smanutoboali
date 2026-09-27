<?php

namespace App\Modules\Access\Actions;

use App\Models\User;
use App\Modules\Access\Services\UserSessionTerminator;
use App\Modules\Core\Services\AuditLogger;

class ResetUserPasswordAction
{
    public function __construct(
        private AuditLogger $audit,
        private UserSessionTerminator $sessions,
    ) {}

    public function handle(User $user, string $password): void
    {
        $user->forceFill(['password' => $password])->save();

        // Password lama tidak boleh lagi dipakai di perangkat mana pun.
        $this->sessions->terminate($user);

        $this->audit->record('access', 'password_reset', "Mereset password user {$user->email}", $user);
    }
}
