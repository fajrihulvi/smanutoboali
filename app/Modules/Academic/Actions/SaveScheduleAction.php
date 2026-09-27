<?php

namespace App\Modules\Academic\Actions;

use App\Modules\Academic\Enums\Weekday;
use App\Modules\Academic\Models\Schedule;
use App\Modules\Academic\Models\TeachingAssignment;
use App\Modules\Academic\Services\ScheduleConflictChecker;
use App\Modules\Core\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Buat/ubah slot jadwal; menolak bentrok guru dan bentrok rombel.
 */
class SaveScheduleAction
{
    public function __construct(
        private ScheduleConflictChecker $conflicts,
        private AuditLogger $audit,
    ) {}

    /**
     * @param  array{teaching_assignment: TeachingAssignment, day_of_week: Weekday, start_time: string, end_time: string, room: string|null}  $data
     *
     * @throws ValidationException
     */
    public function handle(array $data, ?Schedule $schedule = null): Schedule
    {
        $assignment = $data['teaching_assignment'];
        $day = $data['day_of_week'];
        $start = $data['start_time'];
        $end = $data['end_time'];

        if ($end <= $start) {
            throw ValidationException::withMessages(['end_time' => 'Jam selesai harus setelah jam mulai.']);
        }

        return DB::transaction(function () use ($assignment, $day, $start, $end, $data, $schedule) {
            $ignore = $schedule ? [$schedule->id] : [];

            $classroomClash = $this->conflicts->forClassroom($assignment->classroom_id, $assignment->academic_year_id, $day, $start, $end, $ignore);

            if ($classroomClash !== null) {
                throw ValidationException::withMessages(['start_time' => $classroomClash]);
            }

            $teacherClash = $this->conflicts->forTeacher($assignment->teacher_id, $assignment->academic_year_id, $day, $start, $end, $ignore);

            if ($teacherClash !== null) {
                throw ValidationException::withMessages(['teaching_assignment' => $teacherClash]);
            }

            $schedule ??= new Schedule;
            $isNew = ! $schedule->exists;

            $schedule->fill([
                'academic_year_id' => $assignment->academic_year_id,
                'teaching_assignment_id' => $assignment->id,
                'day_of_week' => $day,
                'start_time' => $start,
                'end_time' => $end,
                'room' => $data['room'],
            ])->save();

            $assignment->loadMissing(['subject:id,name', 'classroom:id,name']);

            $this->audit->record(
                'academic',
                $isNew ? 'schedule_created' : 'schedule_updated',
                ($isNew ? 'Menambah' : 'Mengubah')." jadwal {$assignment->subject->name} {$assignment->classroom->name} ({$day->label()} {$start}–{$end})",
                $schedule,
            );

            return $schedule;
        });
    }
}
