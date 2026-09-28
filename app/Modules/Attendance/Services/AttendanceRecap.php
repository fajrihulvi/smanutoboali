<?php

namespace App\Modules\Attendance\Services;

use App\Modules\Academic\Models\Classroom;
use App\Modules\Attendance\Enums\AttendanceStatus;
use App\Modules\Attendance\Models\AttendanceRecord;
use App\Modules\Attendance\Models\AttendanceSession;
use App\Modules\People\Models\Student;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Rekap presensi berbasis STATUS HARIAN per siswa: bila satu hari punya beberapa sesi
 * (mode per mapel), status hari itu = yang paling berat (ALPA > SAKIT > IZIN > TERLAMBAT > HADIR).
 * Persentase kehadiran = hari HADIR/TERLAMBAT ÷ hari tercatat.
 *
 * @phpstan-type Counts array<string, int>
 * @phpstan-type Summary array{counts: Counts, days: int, percentage: float|null}
 */
class AttendanceRecap
{
    /**
     * Status harian (student_id → [Y-m-d → status]) dalam rentang tanggal.
     *
     * @param  list<int>|null  $studentIds
     * @return Collection<int, Collection<string, AttendanceStatus>>
     */
    public function dailyStatuses(CarbonImmutable $from, CarbonImmutable $to, ?int $classroomId = null, ?array $studentIds = null): Collection
    {
        $severity = 'MAX(CASE attendance_records.status'
            .collect(AttendanceStatus::cases())->map(fn (AttendanceStatus $s) => " WHEN '{$s->value}' THEN {$s->severity()}")->implode('')
            .' ELSE 0 END)';

        $rows = AttendanceRecord::query()
            ->join('attendance_sessions', 'attendance_sessions.id', '=', 'attendance_records.attendance_session_id')
            ->whereNull('attendance_sessions.deleted_at')
            ->whereDate('attendance_sessions.date', '>=', $from->toDateString())
            ->whereDate('attendance_sessions.date', '<=', $to->toDateString())
            ->when($classroomId !== null, fn (Builder $q) => $q->where('attendance_sessions.classroom_id', $classroomId))
            ->when($studentIds !== null, fn (Builder $q) => $q->whereIn('attendance_records.student_id', $studentIds))
            ->groupBy('attendance_records.student_id', 'attendance_sessions.date')
            ->select([
                'attendance_records.student_id',
                'attendance_sessions.date as day',
                DB::raw("{$severity} as severity"),
            ])
            ->toBase()
            ->get();

        return $rows
            ->groupBy('student_id')
            ->map(fn (Collection $items) => $items->mapWithKeys(fn (object $row) => [
                substr((string) $row->day, 0, 10) => AttendanceStatus::fromSeverity((int) $row->severity),
            ])->sortKeys());
    }

    /**
     * @param  iterable<AttendanceStatus>  $statuses
     * @return Summary
     */
    public function summarize(iterable $statuses): array
    {
        $counts = array_fill_keys(array_map(fn (AttendanceStatus $s) => $s->value, AttendanceStatus::cases()), 0);
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
     * Matriks bulanan rombel: tanggal yang punya presensi × siswa aktif.
     *
     * @param  Collection<int, Student>  $students
     * @return array{dates: list<string>, rows: list<array{student: array{public_id: string, nis: string, name: string}, days: array<string, string>, summary: Summary}>, summary: Summary}
     */
    public function classroomMonth(Classroom $classroom, Collection $students, CarbonImmutable $month): array
    {
        [$from, $to] = [$month->startOfMonth(), $month->endOfMonth()];
        $statuses = $this->dailyStatuses($from, $to, $classroom->id);

        $dates = AttendanceSession::query()
            ->where('classroom_id', $classroom->id)
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $to->toDateString())
            ->pluck('date')
            ->map(fn (CarbonImmutable $date) => $date->toDateString())
            ->unique()->sort()->values()->all();

        $rows = $students->map(function (Student $student) use ($statuses) {
            $days = $statuses->get($student->id, collect());

            return [
                'student' => ['public_id' => $student->public_id, 'nis' => $student->nis, 'name' => $student->name],
                'days' => $days->map(fn (AttendanceStatus $s) => $s->value)->all(),
                'summary' => $this->summarize($days),
            ];
        })->values()->all();

        return [
            'dates' => $dates,
            'rows' => $rows,
            'summary' => $this->summarize($students->flatMap(fn (Student $s) => $statuses->get($s->id, collect())->values())),
        ];
    }

    /**
     * Kalender bulanan satu siswa, dengan rincian sesi per hari (mapel & catatan).
     *
     * @return array{days: array<string, array{status: string, entries: list<array{status: string, subject: string|null, note: string|null}>}>, summary: Summary}
     */
    public function studentMonth(Student $student, CarbonImmutable $month): array
    {
        [$from, $to] = [$month->startOfMonth(), $month->endOfMonth()];
        $daily = $this->dailyStatuses($from, $to, studentIds: [$student->id])->get($student->id, collect());

        $entries = AttendanceRecord::query()
            ->where('student_id', $student->id)
            ->whereHas('session', fn (Builder $q) => $q
                ->whereDate('date', '>=', $from->toDateString())
                ->whereDate('date', '<=', $to->toDateString()))
            ->with(['session:id,date,schedule_id', 'session.schedule.teachingAssignment.subject:id,name'])
            ->get()
            ->groupBy(fn (AttendanceRecord $r) => $r->session->date->toDateString());

        $days = $daily->map(fn (AttendanceStatus $status, string $day) => [
            'status' => $status->value,
            'entries' => $entries->get($day, collect())->map(fn (AttendanceRecord $r) => [
                'status' => $r->status->value,
                'subject' => $r->session->schedule?->teachingAssignment->subject->name,
                'note' => $r->note,
            ])->values()->all(),
        ])->all();

        return ['days' => $days, 'summary' => $this->summarize($daily)];
    }
}
