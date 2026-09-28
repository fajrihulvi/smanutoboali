<?php

namespace App\Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\Schedule;
use App\Modules\Attendance\Actions\RecordAttendanceAction;
use App\Modules\Attendance\Http\Requests\RecordAttendanceRequest;
use App\Modules\Attendance\Services\AttendanceAccess;
use App\Modules\Attendance\Services\AttendancePages;
use App\Modules\People\Models\Student;
use App\Support\ActivePeriod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Alur isi presensi bersama guru & operator: daftar per tanggal → isi → simpan, rekap rombel, kalender siswa.
 * Turunan menentukan prefix URL dan cakupan rombel/jadwal.
 */
abstract class RecordsAttendanceController extends Controller
{
    public function __construct(
        protected AttendancePages $pages,
        protected AttendanceAccess $access,
        protected ActivePeriod $period,
    ) {}

    abstract protected function base(): string;

    /**
     * Rombel yang tampil di daftar mode harian.
     *
     * @return Collection<int, Classroom>
     */
    abstract protected function classrooms(User $user, int $yearId): Collection;

    /**
     * Jadwal (hari = $weekday) yang tampil di daftar mode per mapel.
     *
     * @return Collection<int, Schedule>
     */
    abstract protected function schedules(User $user, int $yearId, int $weekday): Collection;

    /**
     * @return array<string, mixed>
     */
    protected function extraIndexProps(User $user): array
    {
        return [];
    }

    public function index(Request $request): Response
    {
        $user = $this->user($request);
        $date = $this->pages->date($request);
        $yearId = $this->period->academicYearId();

        $classrooms = $yearId ? $this->classrooms($user, $yearId) : new Collection;
        $schedules = $yearId && ! $date->isSunday() ? $this->schedules($user, $yearId, $date->dayOfWeekIso) : new Collection;

        return Inertia::render('Attendance::Shared/Index', [
            ...$this->pages->index($this->base(), $date, $classrooms, $schedules),
            ...$this->extraIndexProps($user),
            'base' => $this->base(),
            'hasActiveYear' => $yearId !== null,
            'recapClassrooms' => $classrooms->map(fn (Classroom $c) => ['value' => $c->public_id, 'label' => $c->name])->values(),
        ]);
    }

    public function show(Request $request, Classroom $classroom): Response
    {
        $user = $this->user($request);
        $schedule = $this->schedule($request->string('jadwal')->value());

        abort_unless($this->access->canRecord($user, $classroom, $schedule), 403);

        return Inertia::render('Attendance::Shared/Take', $this->pages->take($this->base(), $user, $classroom, $this->pages->date($request), $schedule));
    }

    public function update(RecordAttendanceRequest $request, Classroom $classroom, RecordAttendanceAction $record): RedirectResponse
    {
        $user = $this->user($request);
        $schedule = $request->schedule();

        abort_unless($this->access->canRecord($user, $classroom, $schedule), 403);

        $record->handle($classroom, $request->attendanceDate(), $schedule, $request->entries(), $user);

        return back()->with('success', 'Presensi tersimpan.');
    }

    public function recap(Request $request, Classroom $classroom): Response
    {
        abort_unless($this->access->canViewClassroom($this->user($request), $classroom), 403);

        return Inertia::render('Attendance::Shared/ClassroomRecap', $this->pages->classroomRecap($this->base(), $classroom, $this->pages->month($request)));
    }

    public function student(Request $request, Student $student): Response
    {
        $this->authorize('view', $student);

        return Inertia::render('Attendance::Shared/StudentCalendar', $this->pages->studentCalendar($this->base(), $student, $this->pages->month($request)));
    }

    protected function user(Request $request): User
    {
        /** @var User */
        return $request->user();
    }

    private function schedule(string $publicId): ?Schedule
    {
        return $publicId !== ''
            ? Schedule::query()->where('public_id', $publicId)->with(['teachingAssignment.subject:id,name', 'teachingAssignment.teacher:id,name'])->firstOrFail()
            : null;
    }
}
