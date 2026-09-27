<?php

namespace App\Modules\Access\Actions;

use App\Models\User;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Services\AuditLogger;
use Illuminate\Support\Str;

/**
 * Siapkan akun login untuk data master (guru/orang tua):
 * - email belum terdaftar → buat user baru dengan password sementara acak;
 * - email sudah terdaftar → pakai user itu dan tambahkan role (mis. guru yang juga orang tua).
 */
class ProvisionAccountAction
{
    public function __construct(
        private CreateUserAction $createUser,
        private AuditLogger $audit,
    ) {}

    /**
     * @return array{user: User, temporary_password: string|null}
     */
    public function handle(string $name, string $email, Role $role): array
    {
        $email = Str::lower(trim($email));
        $existing = User::query()->where('email', $email)->first();

        if ($existing) {
            if (! $existing->hasRoleEnum($role)) {
                $existing->assignRole($role->value);

                $this->audit->record('access', 'role_granted', "Menambahkan role {$role->label()} ke {$existing->email}", $existing, [
                    'role' => $role->value,
                ]);
            }

            return ['user' => $existing, 'temporary_password' => null];
        }

        $password = self::temporaryPassword();

        $user = $this->createUser->handle([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'roles' => [$role->value],
            'is_active' => true,
        ]);

        return ['user' => $user, 'temporary_password' => $password];
    }

    /**
     * 12 karakter tanpa karakter yang mudah tertukar (0/O, 1/l/I).
     */
    public static function temporaryPassword(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
        $password = '';

        for ($i = 0; $i < 12; $i++) {
            $password .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $password;
    }
}
