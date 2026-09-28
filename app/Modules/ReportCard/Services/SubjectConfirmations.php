<?php

namespace App\Modules\ReportCard\Services;

use App\Models\User;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\TeachingAssignment;
use App\Modules\Assessment\Services\Gradebook;
use App\Modules\People\Models\Student;
use App\Modules\ReportCard\Actions\TransitionReportCardAction;
use App\Modules\ReportCard\Enums\ReportCardStatus;
use App\Modules\ReportCard\Models\ReportCard;
use App\Modules\ReportCard\Models\ReportSubjectConfirmation;
use App\Modules\School\Models\Semester;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Konfirmasi mapel oleh guru pengampu. Konfirmasi terakhir di satu rombel memicu
 * DRAFT → SUBMITTED (transisi sistem) untuk seluruh raport rombel itu.
 */
class SubjectConfirmations
{
    public function __construct(
        private ReportCardGenerator $generator,
        private TransitionReportCardAction $transition,
    ) {}

    /**
     * Kesiapan mapel: siswa tanpa nilai akhir / tanpa deskripsi.
     *
     * @return array{students: int, missing_final: int, missing_description: int, ready: bool}
     */
    public function readiness(TeachingAssignment $assignment, Semester $semester): array
    {
        $book = Gradebook::make($assignment->loadMissing('subject'), $semester);
        $missingFinal = $book->students->filter(fn (Student $s) => $book->finalScore($s) === null)->count();
        $missingDescription = $book->students->filter(fn (Student $s) => blank($book->description($s)))->count();

        return [
            'students' => $book->students->count(),
            'missing_final' => $missingFinal,
            'missing_description' => $missingDescription,
            'ready' => $book->students->isNotEmpty() && $missingFinal === 0 && $missingDescription === 0,
        ];
    }

    public function isConfirmed(TeachingAssignment $assignment, Semester $semester): bool
    {
        return ReportSubjectConfirmation::query()
            ->where('teaching_assignment_id', $assignment->id)
            ->where('semester_id', $semester->id)
            ->exists();
    }

    /**
     * @throws ValidationException
     */
    public function confirm(TeachingAssignment $assignment, Semester $semester, User $actor): void
    {
        $readiness = $this->readiness($assignment, $semester);

        if (! $readiness['ready']) {
            throw ValidationException::withMessages(['confirmation' => sprintf(
                'Belum bisa dikonfirmasi: %d siswa belum punya nilai akhir, %d belum punya deskripsi.',
                $readiness['missing_final'],
                $readiness['missing_description'],
            )]);
        }

        ReportSubjectConfirmation::query()->updateOrCreate(
            ['teaching_assignment_id' => $assignment->id, 'semester_id' => $semester->id],
            ['confirmed_by' => $actor->id, 'confirmed_at' => now()],
        );

        $this->submitIfReady($assignment->loadMissing('classroom')->classroom, $semester);
    }

    /**
     * @throws ValidationException
     */
    public function revoke(TeachingAssignment $assignment, Semester $semester): void
    {
        $locked = ReportCard::query()
            ->where('classroom_id', $assignment->classroom_id)
            ->where('semester_id', $semester->id)
            ->whereNotIn('status', [ReportCardStatus::Draft, ReportCardStatus::Revision])
            ->exists();

        if ($locked) {
            throw ValidationException::withMessages([
                'confirmation' => 'Raport rombel ini sudah diajukan. Minta wali kelas mengembalikannya ke revisi terlebih dahulu.',
            ]);
        }

        ReportSubjectConfirmation::query()
            ->where('teaching_assignment_id', $assignment->id)
            ->where('semester_id', $semester->id)
            ->delete();
    }

    public function allConfirmed(Classroom $classroom, Semester $semester): bool
    {
        $assignmentIds = TeachingAssignment::query()->where('classroom_id', $classroom->id)->pluck('id');

        return $assignmentIds->isNotEmpty()
            && ReportSubjectConfirmation::query()
                ->where('semester_id', $semester->id)
                ->whereIn('teaching_assignment_id', $assignmentIds)
                ->count() === $assignmentIds->count();
    }

    /**
     * Bila semua mapel terkonfirmasi: segarkan & ajukan semua raport DRAFT rombel (transisi sistem).
     *
     * @return int jumlah raport yang diajukan
     */
    public function submitIfReady(Classroom $classroom, Semester $semester): int
    {
        if (! $this->allConfirmed($classroom, $semester)) {
            return 0;
        }

        return DB::transaction(function () use ($classroom, $semester) {
            $drafts = ReportCard::query()
                ->where('classroom_id', $classroom->id)
                ->where('semester_id', $semester->id)
                ->where('status', ReportCardStatus::Draft)
                ->with('student:id,name')
                ->get();

            $this->generator->refresh($classroom, $semester, $drafts);

            foreach ($drafts as $card) {
                $this->transition->handle($card, ReportCardStatus::Submitted, null, 'Semua mata pelajaran telah dikonfirmasi guru.');
            }

            return $drafts->count();
        });
    }

    /**
     * Wali kelas mengajukan ulang raport REVISION (butuh konfirmasi mapel lengkap).
     *
     * @throws ValidationException
     */
    public function resubmit(ReportCard $card, User $actor): void
    {
        $card->loadMissing(['classroom', 'semester']);

        if (! $this->allConfirmed($card->classroom, $card->semester)) {
            throw ValidationException::withMessages(['status' => 'Belum semua guru mapel mengonfirmasi nilainya.']);
        }

        DB::transaction(function () use ($card, $actor) {
            $this->generator->refresh($card->classroom, $card->semester, new Collection([$card]));
            $this->transition->handle($card, ReportCardStatus::Submitted, $actor, 'Diajukan ulang setelah revisi.');
        });
    }
}
