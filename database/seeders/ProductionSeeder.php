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
 * Seeder produksi: TANPA data demo.
 *
 * - role & permission (aman dijalankan ulang; perubahan di menu Role & Permission tidak dicabut);
 * - profil sekolah awal (nama/jenjang dari SCHOOL_NAME / SCHOOL_LEVEL bila diisi);
 * - empat akun default (operator, kepala sekolah, guru, orang tua) @ACCOUNT_DOMAIN dengan
 *   password awal DEFAULT_ACCOUNT_PASSWORD (aturan password produksi). Pengguna wajib
 *   mengganti password saat pertama login. Akun yang sudah ada tidak diubah.
 *
 * Nilai dibaca lewat config('skoolia.setup') agar tetap jalan saat config di-cache.
 * Hapus DEFAULT_ACCOUNT_PASSWORD dari .env setelah selesai.
 */
class ProductionSeeder extends Seeder
{
    /**
     * Akun default: [bagian lokal email, nama tampilan, role].
     *
     * @var list<array{0: string, 1: string, 2: Role}>
     */
    public const ACCOUNTS = [
        ['operator', 'Operator Sekolah', Role::Operator],
        ['kepsek', 'Kepala Sekolah', Role::Principal],
        ['guru', 'Guru', Role::Teacher],
        ['ortu', 'Orang Tua', Role::Guardian],
    ];

    public function run(CreateUserAction $createUser): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $school = School::current();
        $name = trim((string) config('skoolia.setup.school_name'));
        $level = SchoolLevel::tryFrom(strtoupper(trim((string) config('skoolia.setup.school_level'))));

        if ($name !== '' && $school->name === School::DEFAULT_NAME) {
            $school->update(array_filter(['name' => $name, 'level' => $level]));
        }

        $domain = strtolower(trim((string) config('skoolia.setup.account_domain'), " \t\n\r\0\x0B@"));
        $accounts = array_map(fn (array $a) => ['email' => "{$a[0]}@{$domain}", 'name' => $a[1], 'role' => $a[2]], self::ACCOUNTS);
        $missing = array_values(array_filter($accounts, fn (array $a) => ! User::query()->where('email', $a['email'])->exists()));

        if ($missing === []) {
            $this->command?->info('Akun default sudah ada — dilewati.');

            return;
        }

        $password = (string) config('skoolia.setup.default_password');

        if ($password === '') {
            $this->command?->warn('DEFAULT_ACCOUNT_PASSWORD kosong: akun default tidak dibuat. Isi di .env lalu jalankan ulang, atau pakai `php artisan skoolia:make-operator`.');

            return;
        }

        $validator = Validator::make(
            ['domain' => "operator@{$domain}", 'password' => $password],
            ['domain' => ['required', 'email', 'max:255'], 'password' => ['required', Password::default()]],
            [],
            ['domain' => 'ACCOUNT_DOMAIN', 'password' => 'DEFAULT_ACCOUNT_PASSWORD'],
        );

        if ($validator->fails()) {
            throw new RuntimeException('Akun default tidak dibuat: '.implode(' ', $validator->errors()->all()));
        }

        foreach ($missing as $account) {
            $user = $createUser->handle([
                'name' => $account['name'],
                'email' => $account['email'],
                'password' => $password,
                'roles' => [$account['role']->value],
                'is_active' => true,
            ]);
            $user->forceFill(['email_verified_at' => now(), 'must_change_password' => true])->save();

            $this->command?->info("Akun {$account['role']->label()} dibuat: {$account['email']}");
        }

        $this->command?->warn('Hapus DEFAULT_ACCOUNT_PASSWORD dari .env sekarang. Setiap akun wajib mengganti password saat pertama login.');
    }
}
