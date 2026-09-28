<?php

namespace App\Modules\Assessment\Services;

use App\Modules\Academic\Enums\EnrollmentStatus;
use App\Modules\Academic\Models\ClassEnrollment;
use App\Modules\Academic\Models\TeachingAssignment;
use App\Modules\Assessment\Models\Grade;
use App\Modules\Assessment\Models\GradeComponent;
use App\Modules\School\Models\Semester;
use Illuminate\Support\Collection;

/**
 * Nilai akhir massal satu semester (rekap kepala sekolah, nanti raport S16) dengan aturan yang sama
 * seperti Gradebook, tetapi dalam beberapa query saja.
 */
class GradeRecap
{
    public function __construct(private AssessmentSettings $settings) {}

    /**
     * Nilai akhir per penugasan → siswa (null bila belum bisa dihitung).
     *
     * @param  Collection<int, TeachingAssignment>  $assignments
     * @return array<int, array<int, int|null>>
     */
    public function finals(Semester $semester, Collection $assignments): array
    {
        $components = GradeComponent::query()
            ->where('semester_id', $semester->id)
            ->whereIn('teaching_assignment_id', $assignments->pluck('id'))
            ->get(['id', 'teaching_assignment_id', 'weight'])
            ->groupBy('teaching_assignment_id');

        $scores = Grade::query()
            ->whereIn('grade_component_id', $components->flatten()->pluck('id'))
            ->get(['grade_component_id', 'student_id', 'score'])
            ->groupBy('grade_component_id')
            ->map(fn (Collection $grades) => $grades->mapWithKeys(fn (Grade $g) => [$g->student_id => $g->value()]));

        $members = ClassEnrollment::query()
            ->whereIn('classroom_id', $assignments->pluck('classroom_id')->unique())
            ->where('status', EnrollmentStatus::Active)
            ->get(['classroom_id', 'student_id'])
            ->groupBy('classroom_id');

        $finals = [];

        foreach ($assignments as $assignment) {
            $parts = $components->get($assignment->id, collect());
            $complete = $parts->isNotEmpty() && (int) $parts->sum('weight') === 100;

            foreach ($members->get($assignment->classroom_id, collect()) as $member) {
                $finals[$assignment->id][$member->student_id] = $complete
                    ? $this->final($parts, $scores, $member->student_id)
                    : null;
            }
        }

        return $finals;
    }

    /**
     * Ringkasan satu kumpulan nilai akhir: rata-rata, di bawah KKM, distribusi predikat.
     *
     * @param  array<int, int|null>  $finals
     * @return array{students: int, graded: int, average: float|null, below_kkm: int, distribution: array<string, int>}
     */
    public function summarize(array $finals): array
    {
        $graded = array_values(array_filter($finals, fn (?int $f) => $f !== null));
        $distribution = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0];

        foreach ($graded as $final) {
            $distribution[$this->settings->predicateFor($final)->value]++;
        }

        return [
            'students' => count($finals),
            'graded' => count($graded),
            'average' => $graded === [] ? null : round(array_sum($graded) / count($graded), 1),
            'below_kkm' => count(array_filter($graded, fn (int $f) => $f < $this->settings->kkm())),
            'distribution' => $distribution,
        ];
    }

    /**
     * @param  Collection<int, GradeComponent>  $parts
     * @param  Collection<int, Collection<int, float>>  $scores
     */
    private function final(Collection $parts, Collection $scores, int $studentId): ?int
    {
        $sum = 0.0;

        foreach ($parts as $component) {
            $score = $scores->get($component->id)?->get($studentId);

            if ($score === null) {
                return null;
            }

            $sum += $score * $component->weight;
        }

        return (int) round($sum / 100, 0, PHP_ROUND_HALF_UP);
    }
}
