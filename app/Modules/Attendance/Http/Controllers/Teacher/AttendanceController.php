<?php

namespace App\Modules\Attendance\Http\Controllers\Teacher;

use App\Models\User;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\Schedule;
use App\Modules\Attendance\Http\Controllers\RecordsAttendanceController;
use App\Modules\People\Models\Teacher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Presensi guru: rombel yang diwalikan/diajar (harian) atau jadwal miliknya (per mapel).
 */
class AttendanceController extends RecordsAttendanceController
{
    protected function base(): string
    {
        return '/guru/presensi';
    }

    protected function classrooms(User $user, int $yearId): Collection
    {
        $teacher = Teacher::activeForUser($user);

        if ($teacher === null) {
            return new Collection;
        }

        return Classroom::query()
            ->where('academic_year_id', $yearId)
            ->where(fn (Builder $q) => $q
                ->where('homeroom_teacher_id', $teacher->id)
                ->orWhereHas('teachingAssignments', fn (Builder $a) => $a->where('teacher_id', $teacher->id)))
            ->orderBy('grade')
            ->orderBy('name')
            ->get();
    }

    protected function schedules(User $user, int $yearId, int $weekday): Collection
    {
        $teacher = Teacher::activeForUser($user);

        if ($teacher === null) {
            return new Collection;
        }

        return Schedule::query()
            ->where('academic_year_id', $yearId)
            ->where('day_of_week', $weekday)
            ->whereHas('teachingAssignment', fn (Builder $a) => $a->where('teacher_id', $teacher->id))
            ->with(['teachingAssignment.classroom', 'teachingAssignment.subject:id,name', 'teachingAssignment.teacher:id,name'])
            ->orderBy('start_time')
            ->get();
    }

    protected function extraIndexProps(User $user): array
    {
        return ['hasTeacherProfile' => Teacher::activeForUser($user) !== null];
    }
}
