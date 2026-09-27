<?php

namespace App\Modules\Academic\Actions;

use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Models\TeachingAssignment;
use App\Modules\Academic\Services\ScheduleConflictChecker;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\People\Models\Teacher;
use Illuminate\Validation\ValidationException;

/**
 * Atur guru pengampu satu mapel di satu rombel (null = kosongkan).
 */
class AssignTeacherAction
{
    public function __construct(
        private AuditLogger $audit,
        private ScheduleConflictChecker $conflicts,
    ) {}

    /**
     * @throws ValidationException
     */
    public function handle(Classroom $classroom, Subject $subject, ?Teacher $teacher): ?TeachingAssignment
    {
        $assignment = TeachingAssignment::query()
            ->where('classroom_id', $classroom->getKey())
            ->where('subject_id', $subject->getKey())
            ->first();

        $previous = $assignment?->teacher_id;

        if ($assignment !== null && $teacher === null) {
            $this->ensureCanBeCleared($assignment);
        }

        if ($assignment !== null && $teacher !== null && $teacher->getKey() !== $previous) {
            $this->ensureNoScheduleClash($assignment, $teacher);
        }

        if ($teacher === null) {
            $assignment?->delete();
            $assignment = null;
        } else {
            $assignment ??= new TeachingAssignment([
                'classroom_id' => $classroom->getKey(),
                'subject_id' => $subject->getKey(),
                'academic_year_id' => $classroom->academic_year_id,
            ]);
            $assignment->teacher_id = $teacher->getKey();
            $assignment->save();
        }

        if ($previous !== $teacher?->getKey()) {
            $this->audit->record('academic', 'assignment_updated', "Penugasan {$subject->name} di {$classroom->name}: ".($teacher->name ?? 'dikosongkan'), $classroom, [
                'subject' => $subject->public_id,
                'teacher' => $teacher?->public_id,
            ]);
        }

        return $assignment;
    }

    /**
     * Penugasan yang sudah dipakai jadwal/materi tidak boleh hilang diam-diam.
     *
     * @throws ValidationException
     */
    private function ensureCanBeCleared(TeachingAssignment $assignment): void
    {
        if ($assignment->schedules()->exists() || $assignment->materials()->exists()) {
            throw ValidationException::withMessages([
                'teacher' => 'Mapel ini sudah memiliki jadwal atau materi. Hapus jadwal/materinya dulu, atau ganti gurunya.',
            ]);
        }
    }

    /**
     * Jadwal penugasan ini akan pindah ke guru baru; pastikan guru baru tidak bentrok.
     *
     * @throws ValidationException
     */
    private function ensureNoScheduleClash(TeachingAssignment $assignment, Teacher $teacher): void
    {
        $own = $assignment->schedules()->get();

        foreach ($own as $schedule) {
            $clash = $this->conflicts->forTeacher(
                $teacher->getKey(),
                $assignment->academic_year_id,
                $schedule->day_of_week,
                $schedule->start_time,
                $schedule->end_time,
                $own->modelKeys(),
            );

            if ($clash !== null) {
                throw ValidationException::withMessages(['teacher' => $clash]);
            }
        }
    }
}
