<?php

namespace App\Modules\Access\Actions;

use App\Models\User;
use App\Modules\Access\Services\UserSessionTerminator;
use App\Modules\Core\Contracts\UserDeletionGuard;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Services\AuditLogger;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Hapus permanen akun user. Ditolak untuk akun sendiri, operator aktif terakhir,
 * atau bila modul lain masih menautkan data ke akun ini ({@see UserDeletionGuard}).
 */
class DeleteUserAction
{
    public function __construct(
        private AuditLogger $audit,
        private UserSessionTerminator $sessions,
        private Container $container,
    ) {}

    /**
     * @throws ValidationException
     */
    public function handle(User $user, User $actor): void
    {
        if ($user->is($actor)) {
            throw ValidationException::withMessages(['user' => 'Anda tidak dapat menghapus akun Anda sendiri.']);
        }

        if ($user->hasRoleEnum(Role::Operator) && $user->is_active && $this->isLastActiveOperator($user)) {
            throw ValidationException::withMessages(['user' => 'Operator aktif terakhir tidak dapat dihapus.']);
        }

        /** @var iterable<UserDeletionGuard> $guards */
        $guards = $this->container->tagged(UserDeletionGuard::TAG);

        foreach ($guards as $guard) {
            if (($reason = $guard->blockingReason($user)) !== null) {
                throw ValidationException::withMessages(['user' => $reason]);
            }
        }

        DB::transaction(function () use ($user) {
            // Catat sebelum dihapus; nama & email disimpan di properties agar jejak tetap terbaca.
            $this->audit->record('access', 'deleted', "Menghapus user {$user->email}", $user, [
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames()->all(),
            ]);

            $this->sessions->terminate($user);
            $user->notifications()->delete();
            $user->delete();
        });
    }

    private function isLastActiveOperator(User $user): bool
    {
        return ! User::query()
            ->role(Role::Operator->value)
            ->where('is_active', true)
            ->whereKeyNot($user->getKey())
            ->exists();
    }
}
