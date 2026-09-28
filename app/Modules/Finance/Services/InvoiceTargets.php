<?php

namespace App\Modules\Finance\Services;

use App\Modules\Academic\Enums\EnrollmentStatus;
use App\Modules\Academic\Models\ClassEnrollment;
use App\Support\ActivePeriod;
use Illuminate\Database\Eloquent\Builder;

/**
 * Siswa sasaran generate tagihan: siswa dengan enrollment AKTIF di tahun ajaran aktif,
 * untuk semua / satu tingkat / rombel tertentu.
 *
 * @phpstan-type Scope array{type: string, grade?: int|null, classroom_ids?: list<int>}
 */
class InvoiceTargets
{
    public const ALL = 'SEMUA';

    public const GRADE = 'TINGKAT';

    public const CLASSROOMS = 'ROMBEL';

    public function __construct(private ActivePeriod $period) {}

    /**
     * @param  Scope  $scope
     * @return Builder<ClassEnrollment>
     */
    public function enrollments(array $scope): Builder
    {
        return ClassEnrollment::query()
            ->where('academic_year_id', $this->period->academicYearId() ?? 0)
            ->where('status', EnrollmentStatus::Active)
            ->when($scope['type'] === self::GRADE, fn (Builder $q) => $q->whereHas('classroom', fn (Builder $c) => $c->where('grade', $scope['grade'] ?? 0)))
            ->when($scope['type'] === self::CLASSROOMS, fn (Builder $q) => $q->whereIn('classroom_id', $scope['classroom_ids'] ?? []));
    }

    /**
     * @param  Scope  $scope
     * @return list<int>
     */
    public function studentIds(array $scope): array
    {
        return $this->enrollments($scope)->distinct()->orderBy('student_id')->pluck('student_id')->map(fn ($id) => (int) $id)->all();
    }
}
