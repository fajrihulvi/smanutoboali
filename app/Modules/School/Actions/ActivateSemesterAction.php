<?php

namespace App\Modules\School\Actions;

use App\Modules\Core\Services\AuditLogger;
use App\Modules\School\Models\AcademicYear;
use App\Modules\School\Models\Semester;
use App\Support\ActivePeriod;
use Illuminate\Support\Facades\DB;

/**
 * Hanya satu semester aktif; tahun ajarannya ikut menjadi tahun ajaran aktif.
 */
class ActivateSemesterAction
{
    public function __construct(
        private AuditLogger $audit,
        private ActivePeriod $period,
    ) {}

    public function handle(Semester $semester): Semester
    {
        DB::transaction(function () use ($semester) {
            Semester::query()->lockForUpdate()->get(['id']);

            Semester::query()->whereKeyNot($semester->getKey())->update(['is_active' => false]);
            AcademicYear::query()->whereKeyNot($semester->academic_year_id)->update(['is_active' => false]);
            AcademicYear::query()->whereKey($semester->academic_year_id)->update(['is_active' => true]);

            $semester->forceFill(['is_active' => true])->save();

            $this->audit->record('school', 'activated', "Mengaktifkan {$semester->label()} {$semester->academicYear->name}", $semester);
        });

        $this->period->flush();

        return $semester->refresh();
    }
}
