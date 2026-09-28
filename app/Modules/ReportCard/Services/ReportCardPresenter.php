<?php

namespace App\Modules\ReportCard\Services;

use App\Models\User;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\TeachingAssignment;
use App\Modules\ReportCard\Enums\ReportCardStatus;
use App\Modules\ReportCard\Models\ReportCard;
use App\Modules\ReportCard\Models\ReportCardAchievement;
use App\Modules\ReportCard\Models\ReportCardDetail;
use App\Modules\ReportCard\Models\ReportCardExtracurricular;
use App\Modules\ReportCard\Models\ReportCardLog;
use App\Modules\ReportCard\Models\ReportSubjectConfirmation;
use App\Modules\School\Models\Semester;

/**
 * Bentuk props Inertia untuk halaman raport (tanpa id numerik).
 */
class ReportCardPresenter
{
    public function __construct(private ReportCardAccess $access) {}

    /**
     * @return array{value: string, label: string}
     */
    public function status(ReportCardStatus $status): array
    {
        return ['value' => $status->value, 'label' => $status->label()];
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(ReportCard $card): array
    {
        return [
            'public_id' => $card->public_id,
            'student' => ['public_id' => $card->student->public_id, 'nis' => $card->student->nis, 'name' => $card->student->name],
            'status' => $this->status($card->status),
            'has_note' => filled($card->homeroom_note),
            'published_at' => $card->published_at?->toIso8601String(),
        ];
    }

    /**
     * @param  iterable<ReportCard>  $cards
     * @return array<string, int> status → jumlah
     */
    public function counts(iterable $cards): array
    {
        $counts = array_fill_keys(array_map(fn (ReportCardStatus $s) => $s->value, ReportCardStatus::cases()), 0);

        foreach ($cards as $card) {
            $counts[$card->status->value]++;
        }

        return $counts;
    }

    /**
     * Status konfirmasi mapel satu rombel.
     *
     * @return list<array{assignment: string, subject: string, teacher: string, confirmed_at: string|null}>
     */
    public function confirmations(Classroom $classroom, Semester $semester): array
    {
        $assignments = TeachingAssignment::query()->where('classroom_id', $classroom->id)->with(['subject:id,name', 'teacher:id,name'])->get();
        $confirmed = ReportSubjectConfirmation::query()
            ->where('semester_id', $semester->id)
            ->whereIn('teaching_assignment_id', $assignments->modelKeys())
            ->get()
            ->keyBy('teaching_assignment_id');

        return $assignments
            ->sortBy(fn (TeachingAssignment $a) => $a->subject->name)
            ->map(fn (TeachingAssignment $a) => [
                'assignment' => $a->public_id,
                'subject' => $a->subject->name,
                'teacher' => $a->teacher->name,
                'confirmed_at' => $confirmed->get($a->id)?->confirmed_at->toIso8601String(),
            ])->values()->all();
    }

    /**
     * Detail satu raport + aksi yang tersedia untuk user ini.
     *
     * @return array<string, mixed>
     */
    public function detail(ReportCard $card, User $user): array
    {
        $card->loadMissing([
            'student:id,public_id,nis,nisn,name',
            'classroom.homeroomTeacher:id,name',
            'semester.academicYear',
            'details', 'extracurriculars', 'achievements',
            'logs.actor:id,name',
        ]);

        return [
            ...$this->summary($card),
            'classroom' => ['public_id' => $card->classroom->public_id, 'name' => $card->classroom->name],
            'homeroom' => $card->classroom->homeroomTeacher?->name,
            'period' => "{$card->semester->label()} TA {$card->semester->academicYear->name}",
            'semester' => $card->semester->public_id,
            'homeroom_note' => $card->homeroom_note,
            'attendance' => $card->attendance,
            'details' => $card->details->map(fn (ReportCardDetail $d) => [
                'subject' => $d->subject_name,
                'group' => $d->subject_group,
                'final_score' => $d->final_score,
                'predicate' => $d->predicate,
                'description' => $d->description,
            ])->values(),
            'extracurriculars' => $card->extracurriculars->map(fn (ReportCardExtracurricular $e) => [
                'name' => $e->name, 'predicate' => $e->predicate, 'note' => $e->note,
            ])->values(),
            'achievements' => $card->achievements->map(fn (ReportCardAchievement $a) => [
                'name' => $a->name, 'note' => $a->note,
            ])->values(),
            'logs' => $card->logs->map(fn (ReportCardLog $log) => [
                'from' => $log->from_status ? $this->status($log->from_status) : null,
                'to' => $this->status($log->to_status),
                'actor' => $log->actor->name ?? 'Sistem',
                'note' => $log->note,
                'at' => $log->created_at?->toIso8601String(),
            ])->values(),
            'transitions' => array_map(fn (ReportCardStatus $s) => $this->status($s), $this->access->availableTransitions($user, $card)),
            'canEdit' => $user->can('update', $card),
        ];
    }
}
