<?php

namespace App\Modules\Attendance\Http\Controllers\Operator;

use App\Models\User;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\Schedule;
use App\Modules\Attendance\Http\Controllers\RecordsAttendanceController;
use Illuminate\Database\Eloquent\Collection;

/**
 * Presensi operator: semua rombel/jadwal tahun aktif; boleh mengoreksi kapan pun.
 */
class AttendanceController extends RecordsAttendanceController
{
    protected function base(): string
    {
        return '/operator/presensi';
    }

    protected function classrooms(User $user, int $yearId): Collection
    {
        return Classroom::query()->where('academic_year_id', $yearId)->orderBy('grade')->orderBy('name')->get();
    }

    protected function schedules(User $user, int $yearId, int $weekday): Collection
    {
        return Schedule::query()
            ->where('academic_year_id', $yearId)
            ->where('day_of_week', $weekday)
            ->with(['teachingAssignment.classroom', 'teachingAssignment.subject:id,name', 'teachingAssignment.teacher:id,name'])
            ->orderBy('start_time')
            ->get()
            ->sortBy(fn (Schedule $s) => [$s->teachingAssignment->classroom->grade, $s->teachingAssignment->classroom->name, $s->start_time])
            ->values();
    }
}
