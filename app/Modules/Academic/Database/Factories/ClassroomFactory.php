<?php

namespace App\Modules\Academic\Database\Factories;

use App\Modules\Academic\Models\Classroom;
use App\Modules\School\Models\AcademicYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Classroom>
 */
class ClassroomFactory extends Factory
{
    protected $model = Classroom::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'academic_year_id' => AcademicYear::factory(),
            'name' => 'VII-'.fake()->unique()->bothify('?##'),
            'grade' => 7,
            'capacity' => 32,
        ];
    }
}
