<?php

namespace App\Modules\Academic\Services;

use App\Modules\Academic\Enums\Weekday;
use App\Modules\Academic\Models\Schedule;
use Illuminate\Database\Eloquent\Builder;

/**
 * Cek bentrok jadwal: guru yang sama atau rombel yang sama pada hari & jam yang beririsan
 * (dalam satu tahun ajaran). Mengembalikan pesan Bahasa Indonesia, atau null bila aman.
 */
class ScheduleConflictChecker
{
    /**
     * @param  list<int>  $ignoreScheduleIds
     */
    public function forTeacher(int $teacherId, int $academicYearId, Weekday $day, string $start, string $end, array $ignoreScheduleIds = []): ?string
    {
        $clash = $this->overlapping($academicYearId, $day, $start, $end, $ignoreScheduleIds)
            ->whereHas('teachingAssignment', fn (Builder $a) => $a->where('teacher_id', $teacherId))
            ->first();

        return $clash ? 'Guru sudah mengajar '.$this->describe($clash).' pada jam tersebut.' : null;
    }

    /**
     * @param  list<int>  $ignoreScheduleIds
     */
    public function forClassroom(int $classroomId, int $academicYearId, Weekday $day, string $start, string $end, array $ignoreScheduleIds = []): ?string
    {
        $clash = $this->overlapping($academicYearId, $day, $start, $end, $ignoreScheduleIds)
            ->whereHas('teachingAssignment', fn (Builder $a) => $a->where('classroom_id', $classroomId))
            ->first();

        return $clash ? 'Rombel sudah ada jadwal '.$this->describe($clash).'.' : null;
    }

    /**
     * @param  list<int>  $ignoreScheduleIds
     * @return Builder<Schedule>
     */
    private function overlapping(int $academicYearId, Weekday $day, string $start, string $end, array $ignoreScheduleIds): Builder
    {
        return Schedule::query()
            ->where('academic_year_id', $academicYearId)
            ->overlapping($day, $start, $end)
            ->whereNotIn('id', $ignoreScheduleIds)
            ->with(['teachingAssignment.subject:id,name', 'teachingAssignment.classroom:id,name']);
    }

    private function describe(Schedule $schedule): string
    {
        $assignment = $schedule->teachingAssignment;

        return "{$assignment->subject->name} di {$assignment->classroom->name} "
            ."({$schedule->day_of_week->label()} {$schedule->startLabel()}–{$schedule->endLabel()})";
    }
}
