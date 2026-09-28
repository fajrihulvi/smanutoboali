<?php

namespace App\Modules\Assessment\Database\Seeders;

use App\Models\User;
use App\Modules\Academic\Enums\EnrollmentStatus;
use App\Modules\Academic\Models\ClassEnrollment;
use App\Modules\Academic\Models\TeachingAssignment;
use App\Modules\Assessment\Enums\ComponentType;
use App\Modules\Assessment\Models\Grade;
use App\Modules\Assessment\Models\GradeComponent;
use App\Support\ActivePeriod;
use Illuminate\Database\Seeder;

/**
 * Nilai demo semester aktif: tiap mapel punya Tugas 20%, UH 30%, PTS 20%, PAS 30%;
 * Tugas & UH dipublikasikan. MTK VII-A (guru@): dua siswa belum punya nilai PAS.
 */
class AssessmentDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction() || GradeComponent::query()->exists()) {
            return;
        }

        $period = app(ActivePeriod::class);
        $period->flush();
        $semesterId = $period->semesterId();
        $yearId = $period->academicYearId();

        if ($semesterId === null || $yearId === null) {
            return;
        }

        mt_srand(15);
        $members = ClassEnrollment::query()->where('academic_year_id', $yearId)->where('status', EnrollmentStatus::Active)
            ->get(['classroom_id', 'student_id'])->groupBy('classroom_id');
        $publisher = User::query()->where('email', 'guru@skoolia.test')->value('id');

        $assignments = TeachingAssignment::query()->where('academic_year_id', $yearId)->with(['subject:id,code', 'classroom:id,name'])->get();

        foreach ($assignments as $assignment) {
            $isDemo = $assignment->subject->code === 'MTK' && $assignment->classroom->name === 'VII-A';
            $students = $members->get($assignment->classroom_id, collect())->pluck('student_id')->values();

            foreach ($this->components() as $position => [$type, $name, $weight, $published]) {
                $component = GradeComponent::query()->create([
                    'academic_year_id' => $yearId,
                    'semester_id' => $semesterId,
                    'teaching_assignment_id' => $assignment->id,
                    'type' => $type,
                    'name' => $name,
                    'weight' => $weight,
                    'position' => $position + 1,
                    'published_at' => $published ? now()->subDays(7 - $position) : null,
                    'published_by' => $published ? $publisher : null,
                ]);

                foreach ($students as $index => $studentId) {
                    if ($isDemo && $type === ComponentType::FinalTerm && $index < 2) {
                        continue;
                    }

                    Grade::query()->create([
                        'grade_component_id' => $component->id,
                        'student_id' => $studentId,
                        'score' => mt_rand(62, 98),
                    ]);
                }
            }
        }
    }

    /**
     * @return list<array{ComponentType, string, int, bool}>
     */
    private function components(): array
    {
        return [
            [ComponentType::Assignment, 'Tugas 1', 20, true],
            [ComponentType::DailyTest, 'UH 1', 30, true],
            [ComponentType::MidTerm, 'PTS', 20, false],
            [ComponentType::FinalTerm, 'PAS', 30, false],
        ];
    }
}
