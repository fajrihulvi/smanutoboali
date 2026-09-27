<?php

namespace App\Modules\Academic\Actions;

use App\Modules\Academic\Enums\EnrollmentStatus;
use App\Modules\Academic\Models\ClassEnrollment;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Services\PromotionPlanner;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\People\Actions\MarkStudentsGraduatedAction;
use App\Modules\School\Models\AcademicYear;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Eksekusi Naik Kelas sesuai PromotionPlanner (satu transaksi, insert ber-chunk).
 */
class PromoteStudentsAction
{
    public function __construct(
        private PromotionPlanner $planner,
        private MarkStudentsGraduatedAction $graduate,
        private AuditLogger $audit,
    ) {}

    /**
     * @return array{promoted: int, graduated: int, classrooms_created: int}
     *
     * @throws ValidationException
     */
    public function handle(AcademicYear $from, AcademicYear $to, bool $graduateFinalGrade): array
    {
        if ($from->is($to) || $to->start_date->lessThanOrEqualTo($from->start_date)) {
            throw ValidationException::withMessages(['to' => 'Tahun ajaran tujuan harus setelah tahun ajaran asal.']);
        }

        $plan = $this->planner->plan($from, $to);

        if ($plan['rows'] === []) {
            throw ValidationException::withMessages(['from' => "Tahun ajaran {$from->name} belum memiliki rombel."]);
        }

        return DB::transaction(function () use ($plan, $from, $to, $graduateFinalGrade) {
            $created = 0;
            $promoted = 0;
            $graduatedIds = [];

            $targetFor = function (string $name, int $grade) use ($to, &$created): Classroom {
                $classroom = Classroom::query()->firstOrCreate(
                    ['academic_year_id' => $to->getKey(), 'name' => $name],
                    ['grade' => $grade],
                );
                $created += $classroom->wasRecentlyCreated ? 1 : 0;

                return $classroom;
            };

            // Siswa yang sudah punya rombel aktif di tahun tujuan tidak disentuh lagi.
            $alreadyEnrolled = ClassEnrollment::query()
                ->where('academic_year_id', $to->getKey())
                ->where('status', EnrollmentStatus::Active)
                ->pluck('student_id')
                ->flip();

            foreach ($plan['rows'] as $row) {
                $target = $row['action'] === 'promote'
                    ? $targetFor((string) $row['target'], (int) $row['target_grade'])
                    : null;

                ClassEnrollment::query()
                    ->where('classroom_id', $row['source_id'])
                    ->where('status', EnrollmentStatus::Active)
                    ->select(['id', 'student_id'])
                    ->chunkById(200, function ($enrollments) use ($target, $to, $alreadyEnrolled, &$promoted, &$graduatedIds, $graduateFinalGrade) {
                        $now = now();
                        $inserts = [];

                        foreach ($enrollments as $enrollment) {
                            if ($target === null) {
                                if ($graduateFinalGrade) {
                                    $graduatedIds[] = $enrollment->student_id;
                                }

                                continue;
                            }

                            if ($alreadyEnrolled->has($enrollment->student_id)) {
                                continue;
                            }

                            $inserts[] = [
                                'student_id' => $enrollment->student_id,
                                'classroom_id' => $target->getKey(),
                                'academic_year_id' => $to->getKey(),
                                'status' => EnrollmentStatus::Active->value,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                        }

                        ClassEnrollment::query()->whereKey($enrollments->modelKeys())->update(['status' => EnrollmentStatus::Completed]);

                        if ($inserts !== []) {
                            ClassEnrollment::query()->insert($inserts);
                            $promoted += count($inserts);
                        }
                    });
            }

            foreach ($plan['new_empty'] as $empty) {
                $targetFor($empty['name'], $empty['grade']);
            }

            $graduated = $this->graduate->handle($graduatedIds);

            $this->audit->record('academic', 'promoted', "Naik kelas {$from->name} → {$to->name}: {$promoted} siswa naik, {$graduated} lulus", properties: [
                'from' => $from->name,
                'to' => $to->name,
                'promoted' => $promoted,
                'graduated' => $graduated,
                'classrooms_created' => $created,
            ]);

            return ['promoted' => $promoted, 'graduated' => $graduated, 'classrooms_created' => $created];
        });
    }
}
