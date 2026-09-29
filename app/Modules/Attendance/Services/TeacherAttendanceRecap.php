<?php

namespace App\Modules\Attendance\Services;

use App\Models\User;
use App\Modules\Attendance\Enums\TeacherAttendanceStatus;
use App\Modules\Attendance\Enums\TeacherAttendanceValidation;
use App\Modules\Attendance\Models\TeacherAttendance;
use App\Modules\People\Models\Teacher;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

/**
 * Data tampilan presensi guru: kartu dashboard, daftar harian operator, rekap periode.
 *
 * @phpstan-type Summary array{counts: array<string, int>, days: int, percentage: float|null}
 */
class TeacherAttendanceRecap
{
    public function __construct(private AttendanceSettings $settings) {}

    /**
     * @param  iterable<TeacherAttendanceStatus>  $statuses
     * @return Summary
     */
    public function summarize(iterable $statuses): array
    {
        $counts = array_fill_keys(array_map(fn (TeacherAttendanceStatus $s) => $s->value, TeacherAttendanceStatus::cases()), 0);
        $days = 0;
        $present = 0;

        foreach ($statuses as $status) {
            $counts[$status->value]++;
            $days++;
            $present += $status->countsAsPresent() ? 1 : 0;
        }

        return ['counts' => $counts, 'days' => $days, 'percentage' => $days > 0 ? round($present / $days * 100, 1) : null];
    }

    /**
     * @return array{status: array{value: string, label: string}, check_in: string|null, check_out: string|null, note: string|null, source: string}|null
     */
    public function row(?TeacherAttendance $record): ?array
    {
        return $record ? [
            'status' => ['value' => $record->status->value, 'label' => $record->status->label()],
            'check_in' => $record->check_in_at?->format('H:i'),
            'check_out' => $record->check_out_at?->format('H:i'),
            'note' => $record->note,
            'source' => $record->source,
        ] : null;
    }

    /**
     * Props kartu presensi di dashboard guru.
     *
     * @return array<string, mixed>
     */
    public function card(User $user): array
    {
        $teacher = Teacher::activeForUser($user);
        $today = CarbonImmutable::today();

        if ($teacher === null) {
            return ['hasTeacherProfile' => false];
        }

        $records = TeacherAttendance::query()
            ->where('teacher_id', $teacher->id)
            ->whereDateBetween('date', $today->startOfMonth()->toDateString(), $today->toDateString())
            ->get();

        $todayRecord = $records->first(fn (TeacherAttendance $r) => $r->date->isSameDay($today));
        $onSite = $todayRecord === null || $todayRecord->status->isOnSite();

        return [
            'hasTeacherProfile' => true,
            'date' => $today->toDateString(),
            'today' => $this->row($todayRecord),
            'canCheckIn' => ! $today->isSunday() && $onSite && $todayRecord?->check_in_at === null,
            'canCheckOut' => $todayRecord?->check_in_at !== null && $todayRecord->check_out_at === null,
            'lateAfter' => $this->settings->teacherLateAfter($today)->format('H:i'),
            'needsLocation' => $this->settings->teacherValidation() === TeacherAttendanceValidation::Gps,
            'month' => $this->summarize($records->map(fn (TeacherAttendance $r) => $r->status)),
        ];
    }

    /**
     * Semua guru aktif + catatannya pada satu tanggal (operator).
     *
     * @return list<array<string, mixed>>
     */
    public function daily(CarbonImmutable $date): array
    {
        $records = TeacherAttendance::query()
            ->whereOnDate('date', $date->toDateString())
            ->get()
            ->keyBy('teacher_id');

        return Teacher::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'public_id', 'name', 'nip'])
            ->map(fn (Teacher $teacher) => [
                'teacher' => ['public_id' => $teacher->public_id, 'name' => $teacher->name, 'nip' => $teacher->nip],
                'record' => $this->row($records->get($teacher->id)),
            ])->values()->all();
    }

    /**
     * Ringkasan per guru dalam rentang tanggal (operator & kepala sekolah).
     *
     * @return list<array{teacher: array{public_id: string, name: string, nip: string|null}, summary: Summary}>
     */
    public function period(CarbonImmutable $from, CarbonImmutable $to): array
    {
        /** @var Collection<int, TeacherAttendance> $records */
        $records = TeacherAttendance::query()
            ->whereDateBetween('date', $from->toDateString(), $to->toDateString())
            ->get(['id', 'teacher_id', 'status']);

        $byTeacher = $records->groupBy('teacher_id');

        return Teacher::query()
            ->where(fn ($q) => $q->where('is_active', true)->orWhereIn('id', $byTeacher->keys()))
            ->orderBy('name')
            ->get(['id', 'public_id', 'name', 'nip'])
            ->map(fn (Teacher $teacher) => [
                'teacher' => ['public_id' => $teacher->public_id, 'name' => $teacher->name, 'nip' => $teacher->nip],
                'summary' => $this->summarize($byTeacher->get($teacher->id, collect())->map(fn (TeacherAttendance $r) => $r->status)),
            ])->values()->all();
    }
}
