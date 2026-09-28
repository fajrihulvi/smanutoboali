<?php

namespace App\Modules\Attendance\Actions;

use App\Modules\Attendance\Models\TeacherAttendance;
use App\Modules\People\Models\Teacher;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

/**
 * Presensi pulang mandiri: hanya setelah presensi masuk hari ini, sekali.
 */
class CheckOutTeacherAction
{
    /**
     * @throws ValidationException
     */
    public function handle(Teacher $teacher): TeacherAttendance
    {
        $record = TeacherAttendance::query()
            ->where('teacher_id', $teacher->id)
            ->whereDate('date', CarbonImmutable::today()->toDateString())
            ->first();

        if ($record?->check_in_at === null) {
            throw ValidationException::withMessages(['attendance' => 'Lakukan presensi masuk terlebih dahulu.']);
        }

        if ($record->check_out_at !== null) {
            throw ValidationException::withMessages(['attendance' => 'Anda sudah presensi pulang hari ini.']);
        }

        $record->update(['check_out_at' => CarbonImmutable::now()]);

        return $record;
    }
}
