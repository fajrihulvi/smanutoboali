<?php

namespace App\Modules\School\Actions;

use App\Modules\Core\Services\AuditLogger;
use App\Modules\School\Models\AcademicYear;
use App\Support\ActivePeriod;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class UpdateAcademicYearAction
{
    public function __construct(
        private AuditLogger $audit,
        private ActivePeriod $period,
    ) {}

    /**
     * @param  array{name: string, start_date: string, end_date: string}  $data
     *
     * @throws ValidationException
     */
    public function handle(AcademicYear $year, array $data): AcademicYear
    {
        $year->load('semesters');
        $start = CarbonImmutable::parse($data['start_date']);
        $end = CarbonImmutable::parse($data['end_date']);

        // Semester harus tetap berada di dalam rentang tahun ajaran.
        foreach ($year->semesters as $semester) {
            if ($semester->start_date->lessThan($start) || $semester->end_date->greaterThan($end)) {
                throw ValidationException::withMessages([
                    'start_date' => "Rentang tanggal tidak mencakup {$semester->label()}. Ubah tanggal semester terlebih dahulu.",
                ]);
            }
        }

        $old = [
            'name' => $year->name,
            'start_date' => $year->start_date->toDateString(),
            'end_date' => $year->end_date->toDateString(),
        ];
        $year->fill($data)->save();

        if ($year->wasChanged()) {
            $this->audit->record('school', 'updated', "Mengubah tahun ajaran {$year->name}", $year, [
                'old' => $old,
                'attributes' => $data,
            ]);
            $this->period->flush();
        }

        return $year;
    }
}
