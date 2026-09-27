<?php

namespace App\Modules\Access\Actions;

use App\Models\User;
use App\Modules\Core\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class CreateUserAction
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  array{name: string, email: string, password: string, roles: list<string>, is_active: bool}  $data
     */
    public function handle(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'is_active' => $data['is_active'],
            ]);

            $user->syncRoles($data['roles']);

            $this->audit->record('access', 'created', "Membuat user {$user->email}", $user, [
                'attributes' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_active' => $user->is_active,
                    'roles' => $data['roles'],
                ],
            ]);

            return $user;
        });
    }
}
