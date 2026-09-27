<?php

namespace App\Modules\People\Database\Factories;

use App\Models\User;
use App\Modules\Core\Enums\Role;
use App\Modules\People\Enums\EmploymentStatus;
use App\Modules\People\Enums\Gender;
use App\Modules\People\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Teacher>
 */
class TeacherFactory extends Factory
{
    protected $model = Teacher::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gender = fake()->randomElement(Gender::cases());

        return [
            'nip' => fake()->unique()->numerify('19##########0#####'),
            'nuptk' => fake()->unique()->numerify('################'),
            'name' => fake()->name($gender === Gender::Male ? 'male' : 'female').', S.Pd.',
            'gender' => $gender,
            'phone' => fake()->numerify('08##########'),
            'employment_status' => fake()->randomElement(EmploymentStatus::cases()),
            'is_active' => true,
        ];
    }

    /**
     * Guru dengan akun login ber-role teacher.
     */
    public function withAccount(?User $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user?->getKey() ?? User::factory()->withRole(Role::Teacher)->create(['name' => $attributes['name']])->getKey(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
