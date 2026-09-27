<?php

namespace App\Modules\Academic\Services;

use App\Modules\People\Models\Teacher;
use App\Modules\School\Models\AcademicYear;
use App\Modules\School\Services\SchoolProfile;
use App\Support\ActivePeriod;
use Illuminate\Http\Request;

/**
 * Pilihan dropdown bersama halaman akademik (tahun ajaran, guru, tingkat).
 */
class AcademicOptions
{
    public function __construct(
        private ActivePeriod $period,
        private SchoolProfile $school,
    ) {}

    /**
     * Tahun ajaran terpilih: ?tahun=<public_id>, default tahun ajaran aktif, lalu yang terbaru.
     */
    public function selectedYear(Request $request): ?AcademicYear
    {
        $publicId = $request->string('tahun')->value();

        if ($publicId !== '') {
            return AcademicYear::query()->where('public_id', $publicId)->first();
        }

        return $this->period->academicYear() ?? AcademicYear::query()->latest('start_date')->first();
    }

    /**
     * @return list<array{value: string, label: string, is_active: bool}>
     */
    public function years(): array
    {
        return AcademicYear::query()->orderByDesc('start_date')->get(['public_id', 'name', 'is_active'])
            ->map(fn (AcademicYear $year) => ['value' => $year->public_id, 'label' => $year->name, 'is_active' => $year->is_active])
            ->values()->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function teachers(): array
    {
        return Teacher::query()->where('is_active', true)->orderBy('name')->get(['public_id', 'name'])
            ->map(fn (Teacher $teacher) => ['value' => $teacher->public_id, 'label' => $teacher->name])
            ->values()->all();
    }

    /**
     * @return list<array{value: int, label: string}>
     */
    public function grades(): array
    {
        $level = $this->school->level();

        return array_map(fn (int $grade) => ['value' => $grade, 'label' => 'Kelas '.$level->gradeLabel($grade)], $level->gradeRange());
    }
}
