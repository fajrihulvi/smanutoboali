<?php

namespace App\Modules\ReportCard\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\TeachingAssignment;
use App\Modules\Assessment\Services\GradeAccess;
use App\Modules\Assessment\Services\SemesterPicker;
use App\Modules\People\Models\Teacher;
use App\Modules\ReportCard\Actions\UpdateReportCardContentAction;
use App\Modules\ReportCard\Http\Requests\ReportCardContentRequest;
use App\Modules\ReportCard\Models\ReportCard;
use App\Modules\ReportCard\Models\ReportSubjectConfirmation;
use App\Modules\ReportCard\Services\ReportCardAccess;
use App\Modules\ReportCard\Services\ReportCardGenerator;
use App\Modules\ReportCard\Services\ReportCardPresenter;
use App\Modules\ReportCard\Services\SubjectConfirmations;
use App\Modules\School\Models\Semester;
use App\Support\ActivePeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Raport di portal guru: konfirmasi mapel yang diampu + (bila wali kelas) kelola raport rombel.
 */
class ReportCardController extends Controller
{
    public function __construct(
        private SemesterPicker $semesters,
        private SubjectConfirmations $confirmations,
        private ReportCardAccess $access,
    ) {}

    public function index(Request $request, ActivePeriod $period, ReportCardPresenter $presenter): Response
    {
        $user = $this->user($request);
        $teacher = Teacher::activeForUser($user);
        $semester = $this->semesters->selected($request);
        $yearId = $period->academicYearId() ?? 0;

        $assignments = $teacher
            ? TeachingAssignment::query()->where('teacher_id', $teacher->id)->where('academic_year_id', $yearId)
                ->with(['subject:id,name', 'classroom:id,public_id,name,grade'])->get()
                ->sortBy(fn (TeachingAssignment $a) => [$a->classroom->grade, $a->classroom->name, $a->subject->name])->values()
            : collect();

        $confirmed = $semester
            ? ReportSubjectConfirmation::query()->where('semester_id', $semester->id)->whereIn('teaching_assignment_id', $assignments->pluck('id'))
                ->pluck('confirmed_at', 'teaching_assignment_id')
            : collect();

        $homeroomClasses = $teacher
            ? Classroom::query()->where('academic_year_id', $yearId)->where('homeroom_teacher_id', $teacher->id)->get()
            : collect();

        return Inertia::render('ReportCard::Teacher/Index', [
            'hasTeacherProfile' => $teacher !== null,
            'semester' => $semester?->public_id,
            'semesters' => $this->semesters->toOptions(),
            'subjects' => $semester ? $assignments->map(fn (TeachingAssignment $a) => [
                'assignment' => $a->public_id,
                'subject' => $a->subject->name,
                'classroom' => $a->classroom->name,
                'confirmed_at' => $confirmed->get($a->id)?->toIso8601String(),
                'readiness' => $this->confirmations->readiness($a, $semester),
            ])->values() : [],
            'homeroom' => $semester ? $homeroomClasses->map(function (Classroom $c) use ($semester, $presenter) {
                $cards = ReportCard::query()->where('classroom_id', $c->id)->where('semester_id', $semester->id)->get();
                $confirmations = $presenter->confirmations($c, $semester);

                return [
                    'public_id' => $c->public_id,
                    'name' => $c->name,
                    'counts' => $presenter->counts($cards),
                    'total' => $cards->count(),
                    'confirmed' => count(array_filter($confirmations, fn (array $row) => $row['confirmed_at'] !== null)),
                    'subjects' => count($confirmations),
                ];
            })->values() : [],
        ]);
    }

    public function confirm(Request $request, TeachingAssignment $assignment, GradeAccess $grades): RedirectResponse
    {
        abort_unless($grades->canManage($this->user($request), $assignment), 403);
        $this->confirmations->confirm($assignment, $this->semester($request), $this->user($request));

        return back()->with('success', 'Mapel dikonfirmasi siap masuk raport.');
    }

    public function revoke(Request $request, TeachingAssignment $assignment, GradeAccess $grades): RedirectResponse
    {
        abort_unless($grades->canManage($this->user($request), $assignment), 403);
        $this->confirmations->revoke($assignment, $this->semester($request));

        return back()->with('success', 'Konfirmasi mapel dibatalkan.');
    }

    public function generate(Request $request, Classroom $classroom, ReportCardGenerator $generator): RedirectResponse
    {
        abort_unless($this->access->isHomeroom($this->user($request), $classroom), 403);
        $semester = $this->semester($request);

        $cards = $generator->generate($classroom, $semester, $this->user($request));
        $submitted = $this->confirmations->submitIfReady($classroom, $semester);

        return back()->with('success', "{$cards->count()} raport dibuat/diperbarui.".($submitted > 0 ? " {$submitted} langsung diajukan karena semua mapel sudah dikonfirmasi." : ''));
    }

    public function updateContent(ReportCardContentRequest $request, ReportCard $card, UpdateReportCardContentAction $update): RedirectResponse
    {
        /** @var array{homeroom_note?: string|null, extracurriculars?: list<array{name: string, predicate?: string|null, note?: string|null}>, achievements?: list<array{name: string, note?: string|null}>} $data */
        $data = $request->validated();
        $update->handle($card->loadMissing('student:id,name'), $data);

        return back()->with('success', 'Isi raport disimpan.');
    }

    private function semester(Request $request): Semester
    {
        $semester = $this->semesters->selected($request);
        abort_if($semester === null, 404);

        return $semester;
    }

    private function user(Request $request): User
    {
        /** @var User */
        return $request->user();
    }
}
