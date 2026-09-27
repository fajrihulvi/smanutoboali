<?php

namespace App\Modules\School\Database\Factories;

use App\Modules\School\Actions\CreateAcademicYearAction;
use App\Modules\School\Models\AcademicYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Tahun ajaran beserta dua semesternya (lewat CreateAcademicYearAction::createSemesters).
 *
 * @extends Factory<AcademicYear>
 */
class AcademicYearFactory extends Factory
{
    protected $model = AcademicYear::class;

    private static int $nextYear = 2020;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = self::$nextYear++;

        return [
            'name' => $start.'/'.($start + 1),
            'start_date' => "{$start}-07-01",
            'end_date' => ($start + 1).'-06-30',
            'is_active' => false,
        ];
    }

    public function startingIn(int $year): static
    {
        return $this->state(fn () => [
            'name' => $year.'/'.($year + 1),
            'start_date' => "{$year}-07-01",
            'end_date' => ($year + 1).'-06-30',
        ]);
    }

    public function configure(): static
    {
        return $this->afterCreating(fn (AcademicYear $year) => CreateAcademicYearAction::createSemesters($year));
    }
}
