<?php

namespace App\Modules\Attendance\Services;

use App\Models\User;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\Schedule;
use App\Modules\Attendance\Enums\AttendanceMode;
use App\Modules\Attendance\Enums\AttendanceStatus;
use App\Modules\Attendance\Models\AttendanceRecord;
use App\Modules\Attendance\Models\AttendanceSession;
use App\Modules\People\Models\Student;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Props halaman presensi yang dipakai bersama guru, operator, dan kepala sekolah.
 * `$base` = prefix URL portal, mis. "/guru/presensi".
 */
class AttendancePages
{
    public function __construct(
        private AttendanceSettings $settings,
        private AttendanceAccess $access,
        private AttendanceRoster $roster,
        private AttendanceRecap $recap,
    ) {}

    /**
     * ?tanggal=Y-m-d, default hari ini; tanggal tidak valid → hari ini.
     */
    public function date(Request $request): CarbonImmutable
    {
        $value = $request->string('tanggal')->value();

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1
            ? CarbonImmutable::parse($value)->startOfDay()
            : CarbonImmutable::today();
    }

    /**
     * ?bulan=Y-m, default bulan ini.
     */
    public function month(Request $request): CarbonImmutable
    {
        $value = $request->string('bulan')->value();

        return preg_match('/^\d{4}-\d{2}$/', $value) === 1
            ? CarbonImmutable::parse($value.'-01')->startOfMonth()
            : CarbonImmutable::today()->startOfMonth();
    }

    /**
     * Daftar yang perlu dipresensi pada satu tanggal: per rombel (harian) atau per jadwal (per mapel).
     *
     * @param  EloquentCollection<int, Classroom>  $classrooms  mode harian
     * @param  EloquentCollection<int, Schedule>  $schedules  mode per mapel (hari sesuai tanggal)
     * @return array<string, mixed>
     */
    public function index(string $base, CarbonImmutable $date, EloquentCollection $classrooms, EloquentCollection $schedules): array
    {
        $mode = $this->settings->mode();
        $classroomIds = $mode === AttendanceMode::Daily
            ? $classrooms->modelKeys()
            : $schedules->map(fn (Schedule $s) => $s->teachingAssignment->classroom_id)->unique()->values()->all();

        $sessions = AttendanceSession::query()
            ->whereIn('classroom_id', $classroomIds)
            ->whereDate('date', $date->toDateString())
            ->with('records:id,attendance_session_id,status')
            ->get()
            ->keyBy(fn (AttendanceSession $s) => $s->classroom_id.':'.($s->schedule_id ?? ''));

        $item = function (Classroom $classroom, ?Schedule $schedule) use ($base, $date, $sessions) {
            $session = $sessions->get($classroom->id.':'.($schedule->id ?? ''));

            return [
                'key' => $classroom->public_id.':'.($schedule->public_id ?? ''),
                'classroom' => ['public_id' => $classroom->public_id, 'name' => $classroom->name],
                'schedule' => $schedule ? $this->scheduleProps($schedule) : null,
                'recorded' => $session !== null,
                'counts' => $session ? $this->recap->summarize($session->records->map(fn (AttendanceRecord $r) => $r->status))['counts'] : null,
                'url' => "{$base}/rombel/{$classroom->public_id}?".http_build_query(array_filter([
                    'tanggal' => $date->toDateString(),
                    'jadwal' => $schedule?->public_id,
                ])),
            ];
        };

        $items = $mode === AttendanceMode::Daily
            ? $classrooms->map(fn (Classroom $c) => $item($c, null))
            : $schedules->map(fn (Schedule $s) => $item($s->teachingAssignment->classroom, $s));

        return [
            'mode' => ['value' => $mode->value, 'label' => $mode->label()],
            'date' => $date->toDateString(),
            'isSunday' => $date->isSunday(),
            'items' => $items->values()->all(),
        ];
    }

    /**
     * Halaman isi presensi.
     *
     * @return array<string, mixed>
     */
    public function take(string $base, User $user, Classroom $classroom, CarbonImmutable $date, ?Schedule $schedule): array
    {
        $session = $this->roster->session($classroom, $date, $schedule)?->load(['records', 'recorder:id,name', 'editor:id,name']);
        $records = $session?->records->keyBy('student_id') ?? collect();

        return [
            'base' => $base,
            'classroom' => ['public_id' => $classroom->public_id, 'name' => $classroom->name],
            'date' => $date->toDateString(),
            'schedule' => $schedule ? $this->scheduleProps($schedule) : null,
            'students' => $this->roster->students($classroom)->map(fn (Student $student) => [
                'public_id' => $student->public_id,
                'nis' => $student->nis,
                'name' => $student->name,
                'gender' => $student->gender->value,
                'status' => $records->get($student->id)?->status->value,
                'note' => $records->get($student->id)?->note,
            ])->values(),
            'statuses' => AttendanceStatus::options(),
            'session' => $session ? [
                'recorded_by' => $session->recorder?->name,
                'updated_by' => $session->editor?->name,
                'updated_at' => $session->updated_at?->toIso8601String(),
            ] : null,
            'editable' => $this->access->withinEditWindow($user, $date),
            'editableUntil' => $this->access->isOperator($user) ? null : $this->settings->editableUntil($date)->toIso8601String(),
        ];
    }

    /**
     * Rekap bulanan rombel.
     *
     * @return array<string, mixed>
     */
    public function classroomRecap(string $base, Classroom $classroom, CarbonImmutable $month): array
    {
        return [
            'base' => $base,
            'classroom' => ['public_id' => $classroom->public_id, 'name' => $classroom->name],
            'month' => $month->format('Y-m'),
            'recap' => $this->recap->classroomMonth($classroom, $this->roster->students($classroom), $month),
            'statuses' => AttendanceStatus::options(),
        ];
    }

    /**
     * Kalender kehadiran satu siswa.
     *
     * @return array<string, mixed>
     */
    public function studentCalendar(string $base, Student $student, CarbonImmutable $month): array
    {
        return [
            'base' => $base,
            'student' => ['public_id' => $student->public_id, 'nis' => $student->nis, 'name' => $student->name],
            'month' => $month->format('Y-m'),
            'calendar' => $this->recap->studentMonth($student, $month),
            'statuses' => AttendanceStatus::options(),
        ];
    }

    /**
     * @return array{public_id: string, subject: string, start_time: string, end_time: string, teacher: string}
     */
    public function scheduleProps(Schedule $schedule): array
    {
        $assignment = $schedule->teachingAssignment;

        return [
            'public_id' => $schedule->public_id,
            'subject' => $assignment->subject->name,
            'start_time' => $schedule->startLabel(),
            'end_time' => $schedule->endLabel(),
            'teacher' => $assignment->teacher->name,
        ];
    }

    /**
     * Statistik periode per rombel (kepala sekolah).
     *
     * @param  EloquentCollection<int, Classroom>  $classrooms  dengan relasi activeEnrollments
     * @return list<array<string, mixed>>
     */
    public function classroomStats(EloquentCollection $classrooms, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $statuses = $this->recap->dailyStatuses($from, $to);

        return $classrooms->map(function (Classroom $classroom) use ($statuses) {
            $studentIds = $classroom->activeEnrollments->pluck('student_id');

            return [
                'public_id' => $classroom->public_id,
                'name' => $classroom->name,
                'students' => $studentIds->count(),
                'summary' => $this->recap->summarize(
                    $studentIds->flatMap(fn (int $id) => $statuses->get($id, collect())->values()),
                ),
            ];
        })->values()->all();
    }

    /**
     * Statistik periode per siswa di satu rombel (drill-down kepala sekolah).
     *
     * @return list<array<string, mixed>>
     */
    public function studentStats(Classroom $classroom, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $students = $this->roster->students($classroom);
        $statuses = $this->recap->dailyStatuses($from, $to, studentIds: $students->modelKeys());

        return $students->map(fn (Student $student) => [
            'student' => ['public_id' => $student->public_id, 'nis' => $student->nis, 'name' => $student->name],
            'summary' => $this->recap->summarize($statuses->get($student->id, new Collection)),
        ])->values()->all();
    }
}
