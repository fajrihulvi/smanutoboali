<?php

namespace App\Modules\ReportCard\Services;

use App\Models\User;
use App\Modules\Academic\Enums\EnrollmentStatus;
use App\Modules\Academic\Enums\SubjectGroup;
use App\Modules\Academic\Models\ClassEnrollment;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\TeachingAssignment;
use App\Modules\Assessment\Models\GradeDescription;
use App\Modules\Assessment\Services\AssessmentSettings;
use App\Modules\Assessment\Services\GradeRecap;
use App\Modules\Attendance\Enums\AttendanceStatus;
use App\Modules\Attendance\Services\AttendanceRecap;
use App\Modules\ReportCard\Enums\ReportCardStatus;
use App\Modules\ReportCard\Models\ReportCard;
use App\Modules\ReportCard\Models\ReportCardDetail;
use App\Modules\School\Models\Semester;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Membuat/menyegarkan snapshot isi raport satu rombel: nilai akhir, predikat & deskripsi per mapel
 * (Assessment) serta rekap presensi semester (Attendance). Hanya membaca modul lain.
 */
class ReportCardGenerator
{
    public function __construct(
        private GradeRecap $grades,
        private AssessmentSettings $settings,
        private AttendanceRecap $attendance,
    ) {}

    /**
     * Buat raport DRAFT untuk anggota aktif yang belum punya; segarkan yang masih DRAFT/REVISION.
     *
     * @return Collection<int, ReportCard> raport yang dibuat/disegarkan
     */
    public function generate(Classroom $classroom, Semester $semester, User $actor): Collection
    {
        $studentIds = ClassEnrollment::query()
            ->where('classroom_id', $classroom->id)
            ->where('status', EnrollmentStatus::Active)
            ->pluck('student_id');

        return DB::transaction(function () use ($classroom, $semester, $actor, $studentIds) {
            $existing = ReportCard::query()->where('semester_id', $semester->id)->whereIn('student_id', $studentIds)->get()->keyBy('student_id');

            foreach ($studentIds as $studentId) {
                if (! $existing->has($studentId)) {
                    $existing->put($studentId, ReportCard::query()->create([
                        'academic_year_id' => $classroom->academic_year_id,
                        'semester_id' => $semester->id,
                        'classroom_id' => $classroom->id,
                        'student_id' => $studentId,
                        'status' => ReportCardStatus::Draft,
                        'created_by' => $actor->id,
                    ]));
                }
            }

            /** @var Collection<int, ReportCard> $refreshable */
            $refreshable = new Collection($existing->filter(fn (ReportCard $c) => $c->status->canRegenerate())->values()->all());
            $this->refresh($classroom, $semester, $refreshable);

            return $refreshable;
        });
    }

    /**
     * Tulis ulang detail mapel + presensi untuk raport-raport ini.
     *
     * @param  Collection<int, ReportCard>  $cards
     */
    public function refresh(Classroom $classroom, Semester $semester, Collection $cards): void
    {
        if ($cards->isEmpty()) {
            return;
        }

        $assignments = TeachingAssignment::query()
            ->where('classroom_id', $classroom->id)
            ->with('subject:id,name,group')
            ->get()
            ->sortBy(fn (TeachingAssignment $a) => [array_search($a->subject->group, SubjectGroup::cases(), true), $a->subject->name])
            ->values();

        $finals = $this->grades->finals($semester, $assignments);
        $descriptions = GradeDescription::query()
            ->where('semester_id', $semester->id)
            ->whereIn('teaching_assignment_id', $assignments->pluck('id'))
            ->get(['teaching_assignment_id', 'student_id', 'description'])
            ->groupBy('teaching_assignment_id')
            ->map(fn ($rows) => $rows->pluck('description', 'student_id'));

        $until = CarbonImmutable::today()->min($semester->end_date);
        $daily = $this->attendance->dailyStatuses($semester->start_date, $until, studentIds: $cards->pluck('student_id')->all());
        $now = now();

        ReportCardDetail::query()->whereIn('report_card_id', $cards->modelKeys())->delete();

        $rows = [];

        foreach ($cards as $card) {
            foreach ($assignments as $position => $assignment) {
                $final = $finals[$assignment->id][$card->student_id] ?? null;
                $rows[] = [
                    'report_card_id' => $card->id,
                    'teaching_assignment_id' => $assignment->id,
                    'subject_name' => $assignment->subject->name,
                    'subject_group' => $assignment->subject->group->value,
                    'final_score' => $final,
                    'predicate' => $final === null ? null : $this->settings->predicateFor($final)->value,
                    'description' => $descriptions->get($assignment->id)?->get($card->student_id),
                    'position' => $position + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            $statuses = $daily->get($card->student_id, collect());
            $card->update(['attendance' => [
                'sick' => $statuses->filter(fn (AttendanceStatus $s) => $s === AttendanceStatus::Sick)->count(),
                'permit' => $statuses->filter(fn (AttendanceStatus $s) => $s === AttendanceStatus::Permit)->count(),
                'absent' => $statuses->filter(fn (AttendanceStatus $s) => $s === AttendanceStatus::Absent)->count(),
                'days' => $statuses->count(),
            ]]);
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            ReportCardDetail::query()->insert($chunk);
        }
    }
}
