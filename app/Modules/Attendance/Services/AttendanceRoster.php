<?php

namespace App\Modules\Attendance\Services;

use App\Modules\Academic\Enums\EnrollmentStatus;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\Schedule;
use App\Modules\Attendance\Models\AttendanceSession;
use App\Modules\People\Models\Student;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Daftar siswa yang dipresensi (anggota aktif rombel) + sesi yang sudah ada.
 */
class AttendanceRoster
{
    /**
     * @return Collection<int, Student>
     */
    public function students(Classroom $classroom): Collection
    {
        return Student::query()
            ->whereHas('enrollments', fn (Builder $e) => $e
                ->where('classroom_id', $classroom->id)
                ->where('status', EnrollmentStatus::Active))
            ->orderBy('name')
            ->get(['id', 'public_id', 'nis', 'name', 'gender']);
    }

    public function session(Classroom $classroom, CarbonImmutable $date, ?Schedule $schedule): ?AttendanceSession
    {
        return AttendanceSession::query()
            ->where('classroom_id', $classroom->id)
            ->whereDate('date', $date->toDateString())
            ->where('schedule_id', $schedule?->id)
            ->first();
    }
}
