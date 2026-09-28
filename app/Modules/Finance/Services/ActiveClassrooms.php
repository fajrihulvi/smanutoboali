<?php

namespace App\Modules\Finance\Services;

use App\Modules\Academic\Enums\EnrollmentStatus;
use App\Modules\Academic\Models\ClassEnrollment;
use App\Support\ActivePeriod;

/**
 * Nama rombel aktif per siswa (satu query untuk satu halaman daftar).
 */
class ActiveClassrooms
{
    public function __construct(private ActivePeriod $period) {}

    /**
     * @param  iterable<int>  $studentIds
     * @return array<int, string> key = student_id
     */
    public function names(iterable $studentIds): array
    {
        $ids = collect($studentIds)->unique()->values()->all();

        if ($ids === []) {
            return [];
        }

        return ClassEnrollment::query()
            ->where('academic_year_id', $this->period->academicYearId() ?? 0)
            ->where('status', EnrollmentStatus::Active)
            ->whereIn('student_id', $ids)
            ->with('classroom:id,name')
            ->get()
            ->mapWithKeys(fn (ClassEnrollment $e) => [$e->student_id => $e->classroom->name])
            ->all();
    }
}
