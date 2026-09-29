<?php

namespace App\Modules\Dashboard\Services;

use App\Models\User;
use App\Modules\Academic\Enums\EnrollmentStatus;
use App\Modules\Academic\Models\ClassEnrollment;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\Schedule;
use App\Modules\Academic\Models\TeachingAssignment;
use App\Modules\Activity\Models\Activity;
use App\Modules\Activity\Services\ActivityVisibility;
use App\Modules\Assessment\Models\GradeComponent;
use App\Modules\Attendance\Enums\AttendanceMode;
use App\Modules\Attendance\Models\AttendanceSession;
use App\Modules\Attendance\Services\AttendanceSettings;
use App\Modules\Core\Enums\Role;
use App\Modules\People\Models\Teacher;
use App\Modules\ReportCard\Enums\ReportCardStatus;
use App\Modules\ReportCard\Models\ReportCard;
use App\Modules\ReportCard\Models\ReportSubjectConfirmation;
use App\Modules\School\Models\Semester;
use App\Support\ActivePeriod;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Dashboard guru: jadwal & presensi hari ini, nilai belum lengkap, tugas raport, kegiatan & chat.
 * Semua data dibatasi pada jadwal, penugasan, dan rombel milik guru tersebut.
 */
class TeacherDashboard
{
    private const ATTENDANCE_BASE = '/guru/presensi';

    public function __construct(
        private DashboardCache $cache,
        private ActivePeriod $period,
        private AttendanceSettings $attendanceSettings,
        private ActivityVisibility $activities,
        private RecentChats $chats,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function for(User $user, CarbonImmutable $today): array
    {
        $teacher = Teacher::activeForUser($user);
        $yearId = $this->period->academicYearId();
        $semester = $this->period->semester();

        $base = [
            'hasTeacherProfile' => $teacher !== null,
            'recentChats' => $this->chats->for($user, '/guru/chat'),
            'recentActivities' => $this->recentActivities($user),
        ];

        if ($teacher === null || $yearId === null) {
            return [...$base, 'schedule' => [], 'attendancePending' => [], 'studentsCount' => 0, 'gradesIncomplete' => [], 'reportTasks' => null];
        }

        $assignments = TeachingAssignment::query()
            ->where('academic_year_id', $yearId)
            ->where('teacher_id', $teacher->id)
            ->with(['classroom:id,public_id,name,grade', 'subject:id,name'])
            ->get();

        [$schedule, $pending] = $this->today($teacher, $yearId, $today);

        return [
            ...$base,
            'schedule' => $schedule,
            'attendancePending' => $pending,
            'studentsCount' => $this->cache->remember("teacher.{$teacher->id}.students.{$yearId}", $today, fn () => $this->studentsCount($teacher, $yearId)),
            'gradesIncomplete' => $semester
                ? $this->cache->remember("teacher.{$teacher->id}.grades.{$semester->id}", $today, fn () => $this->gradesIncomplete($assignments, $semester))
                : [],
            'reportTasks' => $semester ? $this->reportTasks($teacher, $assignments, $semester, $yearId) : null,
        ];
    }

    /**
     * Jadwal hari ini + presensi yang belum diisi (sesuai mode presensi).
     *
     * @return array{0: list<array<string, mixed>>, 1: list<array<string, mixed>>}
     */
    private function today(Teacher $teacher, int $yearId, CarbonImmutable $today): array
    {
        if ($today->isSunday()) {
            return [[], []];
        }

        $schedules = Schedule::query()
            ->where('academic_year_id', $yearId)
            ->where('day_of_week', $today->dayOfWeekIso)
            ->whereHas('teachingAssignment', fn (Builder $a) => $a->where('teacher_id', $teacher->id))
            ->with(['teachingAssignment.classroom:id,public_id,name', 'teachingAssignment.subject:id,name'])
            ->orderBy('start_time')
            ->get();

        $perSubject = $this->attendanceSettings->mode() === AttendanceMode::PerSubject;
        $classrooms = $perSubject ? new Collection : $this->ownClassrooms($teacher, $yearId);

        $classroomIds = $perSubject
            ? $schedules->map(fn (Schedule $s) => $s->teachingAssignment->classroom_id)->unique()->values()->all()
            : $classrooms->modelKeys();

        $sessions = AttendanceSession::query()
            ->whereIn('classroom_id', $classroomIds)
            ->whereOnDate('date', $today->toDateString())
            ->get(['classroom_id', 'schedule_id'])
            ->map(fn (AttendanceSession $s) => $s->classroom_id.':'.($s->schedule_id ?? ''))
            ->flip();

        $url = fn (Classroom $c, ?Schedule $s) => self::ATTENDANCE_BASE."/rombel/{$c->public_id}?".http_build_query(array_filter([
            'tanggal' => $today->toDateString(),
            'jadwal' => $s?->public_id,
        ]));

        $schedule = $schedules->map(function (Schedule $s) use ($sessions, $perSubject) {
            $classroom = $s->teachingAssignment->classroom;

            return [
                'public_id' => $s->public_id,
                'start' => substr($s->start_time, 0, 5),
                'end' => substr($s->end_time, 0, 5),
                'subject' => $s->teachingAssignment->subject->name,
                'classroom' => $classroom->name,
                'room' => $s->room,
                'recorded' => $sessions->has($classroom->id.':'.($perSubject ? $s->id : '')),
            ];
        })->values()->all();

        $pending = $perSubject
            ? $schedules->reject(fn (Schedule $s) => $sessions->has($s->teachingAssignment->classroom_id.':'.$s->id))
                ->map(fn (Schedule $s) => [
                    'label' => $s->teachingAssignment->classroom->name.' · '.$s->teachingAssignment->subject->name,
                    'hint' => substr($s->start_time, 0, 5).'–'.substr($s->end_time, 0, 5),
                    'url' => $url($s->teachingAssignment->classroom, $s),
                ])->values()->all()
            : $classrooms->reject(fn (Classroom $c) => $sessions->has($c->id.':'))
                ->map(fn (Classroom $c) => [
                    'label' => $c->name,
                    'hint' => 'Presensi harian',
                    'url' => $url($c, null),
                ])->values()->all();

        return [$schedule, $pending];
    }

    /**
     * Rombel yang diwalikan atau diajar (aturan yang sama dengan halaman presensi guru).
     *
     * @return Collection<int, Classroom>
     */
    private function ownClassrooms(Teacher $teacher, int $yearId): Collection
    {
        return Classroom::query()
            ->where('academic_year_id', $yearId)
            ->where(fn (Builder $q) => $q
                ->where('homeroom_teacher_id', $teacher->id)
                ->orWhereHas('teachingAssignments', fn (Builder $a) => $a->where('teacher_id', $teacher->id)))
            ->orderBy('grade')->orderBy('name')
            ->get(['id', 'public_id', 'name']);
    }

    private function studentsCount(Teacher $teacher, int $yearId): int
    {
        return ClassEnrollment::query()
            ->where('status', EnrollmentStatus::Active)
            ->whereIn('classroom_id', $this->ownClassrooms($teacher, $yearId)->modelKeys())
            ->distinct()
            ->count('student_id');
    }

    /**
     * Mapel-rombel yang nilainya belum lengkap di semester aktif.
     *
     * @param  Collection<int, TeachingAssignment>  $assignments
     * @return list<array{label: string, components: int, weight: int, missing: int, url: string}>
     */
    private function gradesIncomplete(Collection $assignments, Semester $semester): array
    {
        $components = GradeComponent::query()
            ->where('semester_id', $semester->id)
            ->whereIn('teaching_assignment_id', $assignments->modelKeys())
            ->withCount('grades')
            ->get(['id', 'teaching_assignment_id', 'weight'])
            ->groupBy('teaching_assignment_id');

        $members = ClassEnrollment::query()
            ->where('status', EnrollmentStatus::Active)
            ->whereIn('classroom_id', $assignments->pluck('classroom_id')->unique())
            ->selectRaw('classroom_id, COUNT(*) as total')
            ->groupBy('classroom_id')
            ->toBase()
            ->pluck('total', 'classroom_id');

        return $assignments->map(function (TeachingAssignment $a) use ($components, $members, $semester) {
            $parts = $components->get($a->id, new Collection);
            $students = (int) $members->get($a->classroom_id, 0);
            $expected = $parts->count() * $students;
            $filled = (int) $parts->sum(fn (GradeComponent $c) => min($students, (int) $c->getAttribute('grades_count')));

            return [
                'label' => $a->classroom->name.' · '.$a->subject->name,
                'components' => $parts->count(),
                'weight' => (int) $parts->sum('weight'),
                'missing' => max(0, $expected - $filled),
                'url' => "/guru/akademik/nilai/{$a->public_id}?semester={$semester->public_id}",
                'students' => $students,
            ];
        })
            ->filter(fn (array $row) => $row['students'] > 0 && ($row['components'] === 0 || $row['weight'] !== 100 || $row['missing'] > 0))
            ->map(fn (array $row) => collect($row)->except('students')->all())
            ->values()
            ->all();
    }

    /**
     * Konfirmasi nilai mapel untuk raport & raport yang menunggu review wali kelas.
     *
     * @param  Collection<int, TeachingAssignment>  $assignments
     * @return array{unconfirmed: list<array{label: string, url: string}>, to_review: int, revision: int, url: string}
     */
    private function reportTasks(Teacher $teacher, Collection $assignments, Semester $semester, int $yearId): array
    {
        $classroomsWithCards = ReportCard::query()->where('semester_id', $semester->id)->distinct()->pluck('classroom_id');
        $confirmed = ReportSubjectConfirmation::query()
            ->where('semester_id', $semester->id)
            ->whereIn('teaching_assignment_id', $assignments->modelKeys())
            ->pluck('teaching_assignment_id')
            ->flip();

        $homeroomIds = Classroom::query()->where('academic_year_id', $yearId)->where('homeroom_teacher_id', $teacher->id)->pluck('id');
        $homeroomCounts = ReportCard::query()
            ->where('semester_id', $semester->id)
            ->whereIn('classroom_id', $homeroomIds)
            ->whereIn('status', [ReportCardStatus::Submitted, ReportCardStatus::Revision])
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->toBase()
            ->pluck('total', 'status');

        return [
            'unconfirmed' => $assignments
                ->filter(fn (TeachingAssignment $a) => $classroomsWithCards->contains($a->classroom_id) && ! $confirmed->has($a->id))
                ->map(fn (TeachingAssignment $a) => [
                    'label' => $a->classroom->name.' · '.$a->subject->name,
                    'url' => "/guru/akademik/nilai/{$a->public_id}?semester={$semester->public_id}",
                ])->values()->all(),
            'to_review' => (int) $homeroomCounts->get(ReportCardStatus::Submitted->value, 0),
            'revision' => (int) $homeroomCounts->get(ReportCardStatus::Revision->value, 0),
            'url' => '/guru/raport',
        ];
    }

    /**
     * @return list<array{public_id: string, title: string, date: string, status: string}>
     */
    private function recentActivities(User $user): array
    {
        return $this->activities->forUser($user, Role::Teacher)
            ->orderByDesc('date')->orderByDesc('id')
            ->limit(3)
            ->get()
            ->map(fn (Activity $a) => [
                'public_id' => $a->public_id,
                'title' => $a->title,
                'date' => $a->date->toDateString(),
                'status' => $a->status->label(),
            ])->values()->all();
    }
}
