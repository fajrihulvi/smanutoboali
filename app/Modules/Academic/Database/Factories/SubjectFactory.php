<?php

namespace App\Modules\Academic\Database\Factories;

use App\Modules\Academic\Enums\SubjectGroup;
use App\Modules\Academic\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subject>
 */
class SubjectFactory extends Factory
{
    protected $model = Subject::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('MP-###')),
            'name' => fake()->randomElement(['Matematika', 'Bahasa Indonesia', 'IPA', 'IPS', 'Bahasa Inggris', 'PJOK', 'Seni Budaya']),
            'group' => SubjectGroup::General,
            'is_active' => true,
        ];
    }
}
