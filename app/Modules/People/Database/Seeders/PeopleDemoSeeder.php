<?php

namespace App\Modules\People\Database\Seeders;

use App\Models\User;
use App\Modules\People\Enums\EmploymentStatus;
use App\Modules\People\Enums\Gender;
use App\Modules\People\Enums\GuardianRelationship;
use App\Modules\People\Models\Guardian;
use App\Modules\People\Models\Student;
use App\Modules\People\Models\Teacher;
use Illuminate\Database\Seeder;

/**
 * Data demo guru & orang tua, tertaut ke akun demo. Idempoten; dilewati di produksi.
 */
class PeopleDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        if (! Teacher::query()->exists()) {
            $this->seedTeachersAndGuardians();
        }

        if (! Student::query()->exists()) {
            $this->seedStudents();
        }
    }

    private function seedTeachersAndGuardians(): void
    {
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

    /**
     * 30 siswa: ortu@ punya 2 anak (kakak-adik, ayah & ibu sama), guru.ortu@ punya 1 anak.
     */
    private function seedStudents(): void
    {
        $ortu = Guardian::query()->whereHas('user', fn ($q) => $q->where('email', 'ortu@skoolia.test'))->first();
        $guruOrtu = Guardian::query()->whereHas('user', fn ($q) => $q->where('email', 'guru.ortu@skoolia.test'))->first();
        $mother = Guardian::factory()->create(['name' => 'Rahmawati', 'occupation' => 'Ibu Rumah Tangga']);

        foreach (['Aisyah Putri Hidayat' => Gender::Female, 'Rafi Pratama Hidayat' => Gender::Male] as $name => $gender) {
            $student = Student::factory()->create(['name' => $name, 'gender' => $gender]);

            if ($ortu) {
                $student->guardians()->attach($ortu->id, ['relationship' => GuardianRelationship::Father->value, 'is_primary' => true]);
            }

            $student->guardians()->attach($mother->id, ['relationship' => GuardianRelationship::Mother->value, 'is_primary' => false]);
        }

        $child = Student::factory()->create(['name' => 'Nayla Lestari', 'gender' => Gender::Female]);

        if ($guruOrtu) {
            $child->guardians()->attach($guruOrtu->id, ['relationship' => GuardianRelationship::Mother->value, 'is_primary' => true]);
        }

        $others = Guardian::query()->whereNull('user_id')->whereKeyNot($mother->id)->get();

        Student::factory()->count(27)->create()->each(function (Student $student, int $index) use ($others) {
            if ($others->isNotEmpty()) {
                $student->guardians()->attach($others[$index % $others->count()]->id, [
                    'relationship' => GuardianRelationship::Mother->value,
                    'is_primary' => true,
                ]);
            }
        });
    }
}
