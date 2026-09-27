<?php

namespace App\Modules\Access\Console;

use App\Models\User;
use App\Modules\Access\Actions\CreateUserAction;
use App\Modules\Core\Enums\Role;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Membuat akun operator pertama di server (staging/produksi tanpa akun demo).
 * Password hanya diminta lewat prompt tersembunyi agar tidak tersimpan di riwayat shell.
 */
class MakeOperatorCommand extends Command
{
    protected $signature = 'skoolia:make-operator {email : Email login operator} {--name= : Nama lengkap}';

    protected $description = 'Membuat akun operator (role operator) untuk login pertama';

    public function handle(CreateUserAction $createUser): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        $name = (string) ($this->option('name') ?: $this->ask('Nama lengkap operator'));
        $password = (string) $this->secret('Password (tidak ditampilkan)');
        $confirmation = (string) $this->secret('Ulangi password');

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password, 'password_confirmation' => $confirmation],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', Rule::unique(User::class)],
                'password' => ['required', 'confirmed', Password::default()],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->components->error($message);
            }

            return self::FAILURE;
        }

        $user = $createUser->handle([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'roles' => [Role::Operator->value],
            'is_active' => true,
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        $this->components->info("Operator {$user->email} berhasil dibuat. Login di /login/operator.");

        return self::SUCCESS;
    }
}
