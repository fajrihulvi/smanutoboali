<?php

namespace App\Modules\Access\Actions;

use App\Models\User;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateUserAction
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  array{name: string, email: string, roles: list<string>}  $data
     *
     * @throws ValidationException
     */
    public function handle(User $user, array $data, User $actor): User
    {
        if ($user->is($actor) && ! in_array(Role::Operator->value, $data['roles'], true)) {
            throw ValidationException::withMessages([
                'roles' => 'Anda tidak dapat mencabut role Operator dari akun Anda sendiri.',
            ]);
        }

        return DB::transaction(function () use ($user, $data) {
            $old = [
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames()->sort()->values()->all(),
            ];

            $user->fill(['name' => $data['name'], 'email' => $data['email']]);

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            $user->save();
            $user->syncRoles($data['roles']);

            $new = [
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames()->sort()->values()->all(),
            ];

            if ($old !== $new) {
                $this->audit->record('access', 'updated', "Mengubah user {$user->email}", $user, [
                    'old' => $old,
                    'attributes' => $new,
                ]);
            }

            return $user;
        });
    }
}
