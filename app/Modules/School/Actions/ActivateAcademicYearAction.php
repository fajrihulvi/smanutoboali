<?php

namespace App\Modules\School\Actions;

use App\Modules\Core\Services\AuditLogger;
use App\Modules\School\Enums\SemesterType;
use App\Modules\School\Models\AcademicYear;
use App\Modules\School\Models\Semester;
use App\Support\ActivePeriod;
use Illuminate\Support\Facades\DB;

/**
 * Hanya satu tahun ajaran aktif. Semester aktif selalu milik tahun ajaran aktif:
 * bila tahun yang diaktifkan belum punya semester aktif, GANJIL diaktifkan.
 */
class ActivateAcademicYearAction
{
    public function __construct(
        private AuditLogger $audit,
        private ActivePeriod $period,
    ) {}

    public function handle(AcademicYear $year): AcademicYear
    {
        DB::transaction(function () use ($year) {
            // Kunci semua baris agar dua aktivasi bersamaan tidak menghasilkan dua tahun aktif.
            AcademicYear::query()->lockForUpdate()->get(['id']);

            $previous = AcademicYear::query()->where('is_active', true)->whereKeyNot($year->getKey())->value('name');

            AcademicYear::query()->whereKeyNot($year->getKey())->update(['is_active' => false]);
            Semester::query()->where('academic_year_id', '!=', $year->getKey())->update(['is_active' => false]);

            $year->forceFill(['is_active' => true])->save();

            if (! $year->semesters()->where('is_active', true)->exists()) {
                $year->semesters()->where('type', SemesterType::Ganjil)->update(['is_active' => true]);
            }

            $this->audit->record('school', 'activated', "Mengaktifkan tahun ajaran {$year->name}", $year, [
                'previous' => $previous,
            ]);
        });

        $this->period->flush();

        return $year->refresh();
    }
}
