<?php

namespace App\Modules\Auth\Database\Seeders;

use App\Models\User;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Notifications\WelcomeNotification;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role as RoleModel;

/**
 * Akun demo (password: "password"). Idempoten. JANGAN dijalankan di produksi.
 */
class DemoUserSeeder extends Seeder
{
    /**
     * @var list<array{email: string, name: string, roles: list<Role>}>
     */
    public const ACCOUNTS = [
        ['email' => 'kepsek@skoolia.test', 'name' => 'Dra. Siti Rahmawati, M.Pd.', 'roles' => [Role::Principal]],
        ['email' => 'guru@skoolia.test', 'name' => 'Budi Santoso, S.Pd.', 'roles' => [Role::Teacher]],
        ['email' => 'operator@skoolia.test', 'name' => 'Rina Operator', 'roles' => [Role::Operator]],
        ['email' => 'ortu@skoolia.test', 'name' => 'Ahmad Hidayat', 'roles' => [Role::Guardian]],
        // Guru yang juga orang tua murid — untuk mencoba role switcher.
        ['email' => 'guru.ortu@skoolia.test', 'name' => 'Dewi Lestari, S.Pd.', 'roles' => [Role::Teacher, Role::Guardian]],
    ];

    public function run(): void
    {
        if (app()->isProduction()) {
            // Kosong bila seeder dipanggil langsung (bukan lewat artisan db:seed).
            if (isset($this->command)) { // @phpstan-ignore isset.property
                $this->command->warn('DemoUserSeeder dilewati: APP_ENV=production. Gunakan php artisan skoolia:make-operator.');
            }

            return;
        }

        foreach (Role::cases() as $role) {
            RoleModel::findOrCreate($role->value, 'web');
        }

        foreach (self::ACCOUNTS as $account) {
            $user = User::query()->updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => 'password',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ],
            );

            $user->syncRoles(array_map(fn (Role $role) => $role->value, $account['roles']));

            if (! $user->notifications()->exists()) {
                $user->notify(new WelcomeNotification);
            }
        }
    }
}
