<?php

namespace App\Modules\People\Database\Seeders;

use App\Models\User;
use App\Modules\People\Enums\EmploymentStatus;
use App\Modules\People\Enums\Gender;
use App\Modules\People\Models\Guardian;
use App\Modules\People\Models\Teacher;
use Illuminate\Database\Seeder;

/**
 * Data demo guru & orang tua, tertaut ke akun demo. Idempoten; dilewati di produksi.
 */
class PeopleDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction() || Teacher::query()->exists()) {
            return;
        }

        $guru = User::query()->where('email', 'guru@skoolia.test')->first();
        $guruOrtu = User::query()->where('email', 'guru.ortu@skoolia.test')->first();
        $ortu = User::query()->where('email', 'ortu@skoolia.test')->first();

        Teacher::factory()->create([
            'user_id' => $guru?->getKey(),
            'name' => $guru->name ?? 'Budi Santoso, S.Pd.',
            'gender' => Gender::Male,
            'employment_status' => EmploymentStatus::Pns,
        ]);

        Teacher::factory()->create([
            'user_id' => $guruOrtu?->getKey(),
            'name' => $guruOrtu->name ?? 'Dewi Lestari, S.Pd.',
            'gender' => Gender::Female,
            'employment_status' => EmploymentStatus::Pppk,
        ]);

        Teacher::factory()->count(10)->create();
        Teacher::factory()->inactive()->create(['employment_status' => EmploymentStatus::Honorer]);

        Guardian::factory()->create([
            'user_id' => $ortu?->getKey(),
            'name' => $ortu->name ?? 'Ahmad Hidayat',
            'email' => $ortu->email ?? 'ortu@skoolia.test',
            'occupation' => 'Wiraswasta',
        ]);

        Guardian::factory()->create([
            'user_id' => $guruOrtu?->getKey(),
            'name' => $guruOrtu->name ?? 'Dewi Lestari, S.Pd.',
            'email' => $guruOrtu->email ?? 'guru.ortu@skoolia.test',
            'occupation' => 'Guru',
        ]);

        Guardian::factory()->count(15)->create();
    }
}
