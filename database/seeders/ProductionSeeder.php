<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Access\Actions\CreateUserAction;
use App\Modules\Access\Database\Seeders\RolesAndPermissionsSeeder;
use App\Modules\Core\Enums\Role;
use App\Modules\School\Enums\SchoolLevel;
use App\Modules\School\Models\School;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use RuntimeException;

/**
 * Seeder produksi (S35): TANPA data demo.
 *
 * - role & permission (aman dijalankan ulang; perubahan di menu Role & Permission tidak dicabut);
 * - profil sekolah awal (nama/jenjang dari SCHOOL_NAME / SCHOOL_LEVEL bila diisi);
 * Nilai dibaca lewat config('skoolia.setup') agar tetap jalan saat config di-cache.
 * - satu akun operator dari OPERATOR_NAME, OPERATOR_EMAIL, OPERATOR_PASSWORD (password kuat).
 *   Bila email sudah ada, akun tidak diubah. Hapus OPERATOR_PASSWORD dari .env setelah selesai.
 */
class ProductionSeeder extends Seeder
{
    public function run(CreateUserAction $createUser): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $school = School::current();
        $name = trim((string) config('skoolia.setup.school_name'));
        $level = SchoolLevel::tryFrom(strtoupper(trim((string) config('skoolia.setup.school_level'))));

        if ($name !== '' && $school->name === 'Sekolah Skoolia') {
            $school->update(array_filter(['name' => $name, 'level' => $level]));
        }

        $email = strtolower(trim((string) config('skoolia.setup.operator_email')));

        if ($email === '') {
            $this->command?->warn('OPERATOR_EMAIL kosong: akun operator tidak dibuat. Gunakan `php artisan skoolia:make-operator`.');

            return;
        }

        if (User::query()->where('email', $email)->exists()) {
            $this->command?->info("Operator {$email} sudah ada — dilewati.");

            return;
        }

        $data = [
            'name' => trim((string) config('skoolia.setup.operator_name', 'Operator Sekolah')),
            'email' => $email,
            'password' => (string) config('skoolia.setup.operator_password'),
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', Password::default()],
        ], [], ['password' => 'OPERATOR_PASSWORD']);

        if ($validator->fails()) {
            throw new RuntimeException('Akun operator tidak dibuat: '.implode(' ', $validator->errors()->all()));
        }

        $user = $createUser->handle([...$data, 'roles' => [Role::Operator->value], 'is_active' => true]);
        $user->forceFill(['email_verified_at' => now()])->save();

        $this->command?->info("Operator {$email} dibuat. Hapus OPERATOR_PASSWORD dari .env sekarang.");
    }
}
