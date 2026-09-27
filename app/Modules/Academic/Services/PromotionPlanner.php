<?php

namespace App\Modules\Academic\Services;

use App\Modules\Academic\Enums\EnrollmentStatus;
use App\Modules\Academic\Models\Classroom;
use App\Modules\School\Enums\SchoolLevel;
use App\Modules\School\Models\AcademicYear;
use App\Modules\School\Services\SchoolProfile;
use Illuminate\Support\Collection;

/**
 * Rencana "Naik Kelas": rombel tahun asal → rombel tahun tujuan (tingkat + 1).
 *
 * @phpstan-type PlanRow array{
 *     source_id: int, source: string, grade: int, students: int,
 *     action: 'promote'|'graduate', target: string|null, target_grade: int|null, target_exists: bool
 * }
 * @phpstan-type Plan array{rows: list<PlanRow>, new_empty: list<array{name: string, grade: int, exists: bool}>, promote: int, graduate: int}
 */
class PromotionPlanner
{
    /**
     * @return Plan
     */
    public function plan(AcademicYear $from, AcademicYear $to): array
    {
        $level = app(SchoolProfile::class)->level();
        $range = $level->gradeRange();
        $lastGrade = max($range);
        $firstGrade = min($range);

        $existingTargets = Classroom::query()->where('academic_year_id', $to->getKey())->pluck('name')->flip();

        /** @var Collection<int, Classroom> $sources */
        $sources = Classroom::query()
            ->where('academic_year_id', $from->getKey())
            ->withCount(['enrollments as students_count' => fn ($q) => $q->where('status', EnrollmentStatus::Active)])
            ->orderBy('grade')
            ->orderBy('name')
            ->get();

        $rows = [];
        $promote = 0;
        $graduate = 0;

        foreach ($sources as $classroom) {
            $students = (int) $classroom->getAttribute('students_count');

            if ($classroom->grade >= $lastGrade) {
                $rows[] = [
                    'source_id' => $classroom->id, 'source' => $classroom->name, 'grade' => $classroom->grade,
                    'students' => $students, 'action' => 'graduate',
                    'target' => null, 'target_grade' => null, 'target_exists' => false,
                ];
                $graduate += $students;

                continue;
            }

            $target = self::nextName($classroom->name, $classroom->grade, $level);
            $rows[] = [
                'source_id' => $classroom->id, 'source' => $classroom->name, 'grade' => $classroom->grade,
                'students' => $students, 'action' => 'promote',
                'target' => $target, 'target_grade' => $classroom->grade + 1,
                'target_exists' => $existingTargets->has($target),
            ];
            $promote += $students;
        }

        // Rombel tingkat terendah tahun baru (untuk siswa baru) meniru nama rombel tingkat terendah tahun asal.
        $newEmpty = $sources->where('grade', $firstGrade)
            ->map(fn (Classroom $c) => ['name' => $c->name, 'grade' => $firstGrade, 'exists' => $existingTargets->has($c->name)])
            ->values()
            ->all();

        return ['rows' => $rows, 'new_empty' => $newEmpty, 'promote' => $promote, 'graduate' => $graduate];
    }

    /**
     * VII-A → VIII-A, 7A → 8A, 1B → 2B; nama lain diberi awalan label tingkat baru.
     */
    public static function nextName(string $name, int $grade, SchoolLevel $level): string
    {
        foreach ([$level->gradeLabel($grade) => $level->gradeLabel($grade + 1), (string) $grade => (string) ($grade + 1)] as $from => $to) {
            if (preg_match('/^'.preg_quote($from, '/').'(?![0-9IVX])/', $name) === 1) {
                return $to.substr($name, strlen($from));
            }
        }

        return $level->gradeLabel($grade + 1).'-'.$name;
    }
}
