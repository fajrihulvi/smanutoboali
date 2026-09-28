<?php

namespace App\Modules\ChildCondition\Services;

use App\Modules\Academic\Enums\EnrollmentStatus;
use App\Modules\Academic\Models\ClassEnrollment;
use App\Modules\People\Models\Student;
use App\Modules\People\Models\Teacher;
use App\Support\ActivePeriod;

/**
 * Guru yang bisa dituju laporan: wali kelas (default) & guru mapel rombel aktif anak.
 */
class ConditionTeachers
{
    public function __construct(private ActivePeriod $period) {}

    /**
     * @return list<array{teacher: Teacher, label: string, homeroom: bool}> wali kelas lebih dulu
     */
    public function forStudent(Student $student): array
    {
        $yearId = $this->period->academicYearId();
        $enrollment = ClassEnrollment::query()
            ->where('student_id', $student->id)
            ->where('academic_year_id', $yearId)
            ->where('status', EnrollmentStatus::Active)
            ->with([
                'classroom.homeroomTeacher',
                'classroom.teachingAssignments.teacher',
                'classroom.teachingAssignments.subject',
            ])
            ->first();

        if ($enrollment === null) {
            return [];
        }

        $classroom = $enrollment->classroom;
        $rows = [];
        $homeroom = $classroom->getRelation('homeroomTeacher');

        if ($homeroom instanceof Teacher && $homeroom->is_active && $homeroom->user_id !== null) {
            $rows[$homeroom->id] = ['teacher' => $homeroom, 'subjects' => [], 'homeroom' => true];
        }

        foreach ($classroom->teachingAssignments->where('academic_year_id', $yearId) as $assignment) {
            $teacher = $assignment->getRelation('teacher');

            if ($teacher instanceof Teacher && $teacher->is_active && $teacher->user_id !== null) {
                $rows[$teacher->id] ??= ['teacher' => $teacher, 'subjects' => [], 'homeroom' => false];
                $rows[$teacher->id]['subjects'][] = $assignment->subject->name;
            }
        }

        $result = array_map(fn (array $row) => [
            'teacher' => $row['teacher'],
            'label' => implode(' · ', array_filter([
                $row['homeroom'] ? 'Wali Kelas '.$classroom->name : null,
                $row['subjects'] !== [] ? 'Guru '.implode(', ', array_unique($row['subjects'])) : null,
            ])),
            'homeroom' => $row['homeroom'],
        ], array_values($rows));

        usort($result, fn (array $a, array $b) => [$b['homeroom'], $a['teacher']->name] <=> [$a['homeroom'], $b['teacher']->name]);

        return $result;
    }

    public function homeroomOf(Student $student): ?Teacher
    {
        foreach ($this->forStudent($student) as $row) {
            if ($row['homeroom']) {
                return $row['teacher'];
            }
        }

        return null;
    }
}
