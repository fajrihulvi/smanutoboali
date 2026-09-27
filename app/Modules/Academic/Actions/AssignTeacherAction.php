<?php

namespace App\Modules\Academic\Actions;

use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Models\TeachingAssignment;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\People\Models\Teacher;

/**
 * Atur guru pengampu satu mapel di satu rombel (null = kosongkan).
 */
class AssignTeacherAction
{
    public function __construct(private AuditLogger $audit) {}

    public function handle(Classroom $classroom, Subject $subject, ?Teacher $teacher): ?TeachingAssignment
    {
        $assignment = TeachingAssignment::query()
            ->where('classroom_id', $classroom->getKey())
            ->where('subject_id', $subject->getKey())
            ->first();

        $previous = $assignment?->teacher_id;

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
}
