<?php

namespace App\Modules\Academic\Services;

use App\Modules\Academic\Models\Schedule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Query jadwal siap tampil (eager load mapel/guru/rombel, urut hari & jam).
 */
class Timetable
{
    /**
     * @return Collection<int, Schedule>
     */
    public function forClassroom(int $classroomId): Collection
    {
        return $this->base()
            ->whereHas('teachingAssignment', fn (Builder $a) => $a->where('classroom_id', $classroomId))
            ->get();
    }

    /**
     * @return Collection<int, Schedule>
     */
    public function forTeacher(int $teacherId, int $academicYearId): Collection
    {
        return $this->base()
            ->where('academic_year_id', $academicYearId)
            ->whereHas('teachingAssignment', fn (Builder $a) => $a->where('teacher_id', $teacherId))
            ->get();
    }

    /**
     * @return Builder<Schedule>
     */
    private function base(): Builder
    {
        return Schedule::query()
            ->with([
                'teachingAssignment:id,public_id,teacher_id,classroom_id,subject_id',
                'teachingAssignment.subject:id,public_id,code,name',
                'teachingAssignment.teacher:id,public_id,name',
                'teachingAssignment.classroom:id,public_id,name',
            ])
            ->chronological();
    }
}
