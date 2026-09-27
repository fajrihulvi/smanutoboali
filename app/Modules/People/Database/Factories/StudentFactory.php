<?php

namespace App\Modules\People\Database\Factories;

use App\Modules\People\Enums\Gender;
use App\Modules\People\Enums\GuardianRelationship;
use App\Modules\People\Enums\Religion;
use App\Modules\People\Enums\StudentStatus;
use App\Modules\People\Models\Guardian;
use App\Modules\People\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gender = fake()->randomElement(Gender::cases());

        return [
            'nis' => fake()->unique()->numerify('2026####'),
            'nisn' => fake()->unique()->numerify('00########'),
            'name' => fake()->name($gender === Gender::Male ? 'male' : 'female'),
            'gender' => $gender,
            'birth_place' => fake()->city(),
            'birth_date' => fake()->dateTimeBetween('-15 years', '-12 years')->format('Y-m-d'),
            'religion' => Religion::Islam,
            'address' => fake()->address(),
            'status' => StudentStatus::Active,
            'entry_date' => '2026-07-13',
        ];
    }

    /**
     * Tautkan ke orang tua (dibuat baru bila tidak diberikan) sebagai orang tua utama.
     */
    public function withGuardian(?Guardian $guardian = null, GuardianRelationship $relationship = GuardianRelationship::Mother): static
    {
        return $this->afterCreating(function (Student $student) use ($guardian, $relationship) {
            $student->guardians()->attach($guardian ?? Guardian::factory()->create(), [
                'relationship' => $relationship->value,
                'is_primary' => true,
            ]);
        });
    }
}
