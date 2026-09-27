<?php

namespace App\Modules\Access\Actions;

use App\Models\User;
use App\Modules\Access\Services\UserSessionTerminator;
use App\Modules\Core\Services\AuditLogger;
use Illuminate\Validation\ValidationException;

class SetUserActiveStatusAction
{
    public function __construct(
        private AuditLogger $audit,
        private UserSessionTerminator $sessions,
    ) {}

    /**
     * @throws ValidationException
     */
    public function handle(User $user, bool $active, User $actor): User
    {
        if ($user->is($actor) && ! $active) {
            throw ValidationException::withMessages([
                'is_active' => 'Anda tidak dapat menonaktifkan akun Anda sendiri.',
            ]);
        }

        if ($user->is_active === $active) {
            return $user;
        }

        $user->forceFill(['is_active' => $active])->save();

        if (! $active) {
            $this->sessions->terminate($user);
        }

        $this->audit->record(
            'access',
            $active ? 'activated' : 'deactivated',
            ($active ? 'Mengaktifkan' : 'Menonaktifkan')." user {$user->email}",
            $user,
            ['old' => ['is_active' => ! $active], 'attributes' => ['is_active' => $active]],
        );

        return $user;
    }
}
