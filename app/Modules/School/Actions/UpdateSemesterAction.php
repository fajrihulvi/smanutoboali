<?php

namespace App\Modules\School\Actions;

use App\Modules\Core\Services\AuditLogger;
use App\Modules\School\Models\Semester;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class UpdateSemesterAction
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  array{start_date: string, end_date: string}  $data
     *
     * @throws ValidationException
     */
    public function handle(Semester $semester, array $data): Semester
    {
        $year = $semester->academicYear;
        $start = CarbonImmutable::parse($data['start_date']);
        $end = CarbonImmutable::parse($data['end_date']);

        if ($start->lessThan($year->start_date) || $end->greaterThan($year->end_date)) {
            throw ValidationException::withMessages([
                'start_date' => "Tanggal semester harus di dalam tahun ajaran {$year->name} ("
                    .$year->start_date->translatedFormat('j M Y').' – '.$year->end_date->translatedFormat('j M Y').').',
            ]);
        }

        $sibling = $year->semesters()->whereKeyNot($semester->getKey())->first();

        if ($sibling && $start->lessThanOrEqualTo($sibling->end_date) && $end->greaterThanOrEqualTo($sibling->start_date)) {
            throw ValidationException::withMessages([
                'start_date' => "Tanggal bertumpang tindih dengan {$sibling->label()}.",
            ]);
        }

        $old = ['start_date' => (string) $semester->start_date->toDateString(), 'end_date' => (string) $semester->end_date->toDateString()];
        $semester->fill($data)->save();

        if ($semester->wasChanged()) {
            $this->audit->record('school', 'updated', "Mengubah {$semester->label()} {$year->name}", $semester, [
                'old' => $old,
                'attributes' => $data,
            ]);
        }

        return $semester;
    }
}
