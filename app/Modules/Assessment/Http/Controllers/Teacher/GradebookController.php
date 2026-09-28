<?php

namespace App\Modules\Assessment\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Academic\Enums\EnrollmentStatus;
use App\Modules\Academic\Models\TeachingAssignment;
use App\Modules\Assessment\Actions\DeleteGradeComponentAction;
use App\Modules\Assessment\Actions\ImportGradesAction;
use App\Modules\Assessment\Actions\PublishGradeComponentAction;
use App\Modules\Assessment\Actions\SaveDescriptionTemplatesAction;
use App\Modules\Assessment\Actions\SaveGradeAction;
use App\Modules\Assessment\Actions\SaveGradeComponentAction;
use App\Modules\Assessment\Actions\SaveGradeDescriptionAction;
use App\Modules\Assessment\Enums\ComponentType;
use App\Modules\Assessment\Enums\Predicate;
use App\Modules\Assessment\Exports\GradesExport;
use App\Modules\Assessment\Http\Requests\GradeComponentRequest;
use App\Modules\Assessment\Models\GradeComponent;
use App\Modules\Assessment\Services\GradeAccess;
use App\Modules\Assessment\Services\Gradebook;
use App\Modules\Assessment\Services\SemesterPicker;
use App\Modules\People\Models\Student;
use App\Modules\People\Models\Teacher;
use App\Modules\School\Models\Semester;
use App\Support\ActivePeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Buku nilai guru: daftar mapel-rombel yang diampu → spreadsheet nilai per semester.
 */
class GradebookController extends Controller
{
    public function __construct(
        private GradeAccess $access,
        private SemesterPicker $semesters,
    ) {}

    public function index(Request $request, ActivePeriod $period): Response
    {
        $teacher = Teacher::activeForUser($this->user($request));
        $semester = $this->semesters->selected($request);

        $assignments = $teacher && $period->academicYearId()
            ? TeachingAssignment::query()
                ->where('teacher_id', $teacher->id)
                ->where('academic_year_id', $period->academicYearId())
                ->with(['subject:id,name', 'classroom:id,name,grade'])
                ->get()
                ->sortBy(fn (TeachingAssignment $a) => [$a->classroom->grade, $a->classroom->name, $a->subject->name])
                ->values()
            : collect();

        $components = $semester
            ? GradeComponent::query()
                ->where('semester_id', $semester->id)
                ->whereIn('teaching_assignment_id', $assignments->pluck('id'))
                ->get(['teaching_assignment_id', 'weight', 'published_at'])
                ->groupBy('teaching_assignment_id')
            : collect();

        return Inertia::render('Assessment::Teacher/Index', [
            'hasTeacherProfile' => $teacher !== null,
            'semester' => $semester?->public_id,
            'semesters' => $this->semesters->toOptions(),
            'assignments' => $assignments->map(function (TeachingAssignment $a) use ($components) {
                $parts = $components->get($a->id, collect());

                return [
                    'public_id' => $a->public_id,
                    'subject' => $a->subject->name,
                    'classroom' => $a->classroom->name,
                    'components' => $parts->count(),
                    'total_weight' => (int) $parts->sum('weight'),
                    'published' => $parts->whereNotNull('published_at')->count(),
                ];
            }),
        ]);
    }

    public function show(Request $request, TeachingAssignment $assignment): Response
    {
        $this->authorizeManage($request, $assignment);
        $semester = $this->semester($request);

        return Inertia::render('Assessment::Shared/Gradebook', [
            ...Gradebook::make($assignment, $semester)->toProps(readOnly: false),
            'semesters' => $this->semesters->toOptions(),
            'types' => ComponentType::options(),
            'base' => '/guru/akademik/nilai',
        ]);
    }

    public function storeComponent(GradeComponentRequest $request, TeachingAssignment $assignment, SaveGradeComponentAction $save): RedirectResponse
    {
        $this->authorizeManage($request, $assignment);
        $save->handle($assignment, $this->semester($request), $request->payload());

        return back()->with('success', 'Komponen nilai ditambahkan.');
    }

    public function updateComponent(GradeComponentRequest $request, GradeComponent $component, SaveGradeComponentAction $save): RedirectResponse
    {
        $this->authorizeManage($request, $component->teachingAssignment);
        $save->handle($component->teachingAssignment, $component->semester, $request->payload(), $component);

        return back()->with('success', 'Komponen nilai diperbarui.');
    }

    public function destroyComponent(Request $request, GradeComponent $component, DeleteGradeComponentAction $delete): RedirectResponse
    {
        $this->authorizeManage($request, $component->teachingAssignment);
        $delete->handle($component);

        return back()->with('success', "Komponen {$component->name} dihapus.");
    }

    public function publish(Request $request, GradeComponent $component, PublishGradeComponentAction $publish): RedirectResponse
    {
        $this->authorizeManage($request, $component->teachingAssignment);
        $publish->publish($component, $this->user($request));

        return back()->with('success', "Nilai {$component->name} dipublikasikan ke orang tua.");
    }

    public function unpublish(Request $request, GradeComponent $component, PublishGradeComponentAction $publish): RedirectResponse
    {
        $this->authorizeManage($request, $component->teachingAssignment);
        $publish->unpublish($component);

        return back()->with('success', "Publikasi {$component->name} dibatalkan; nilai bisa diubah kembali.");
    }

    /**
     * Simpan satu sel (JSON) → nilai akhir & predikat siswa terbaru.
     */
    public function saveCell(Request $request, GradeComponent $component, SaveGradeAction $save): JsonResponse
    {
        $assignment = $component->teachingAssignment;
        $this->authorizeManage($request, $assignment);

        $validated = $request->validate([
            'student' => ['required', 'string'],
            'score' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ], [], ['score' => 'nilai']);

        $student = $this->student($assignment, $validated['student']);
        $save->handle($component, $student, isset($validated['score']) ? (float) $validated['score'] : null, $this->user($request));

        $book = Gradebook::make($assignment, $component->semester);

        return response()->json([
            'final' => $book->finalScore($student),
            'predicate' => $book->predicate($student)?->value,
            'suggested' => $book->suggestedDescription($student),
        ]);
    }

    public function saveDescription(Request $request, TeachingAssignment $assignment, SaveGradeDescriptionAction $save): JsonResponse
    {
        $this->authorizeManage($request, $assignment);

        $validated = $request->validate([
            'student' => ['required', 'string'],
            'description' => ['nullable', 'string', 'max:1000'],
        ], [], ['description' => 'deskripsi']);

        $save->handle($assignment, $this->semester($request), $this->student($assignment, $validated['student']), $validated['description'] ?? null, $this->user($request));

        return response()->json(['saved' => true]);
    }

    public function fillDescriptions(Request $request, TeachingAssignment $assignment, SaveGradeDescriptionAction $save): RedirectResponse
    {
        $this->authorizeManage($request, $assignment);

        $filled = $save->fillFromTemplates(Gradebook::make($assignment, $this->semester($request)), $this->user($request), $request->boolean('overwrite'));

        return back()->with('success', $filled > 0 ? "{$filled} deskripsi diisi dari template." : 'Tidak ada deskripsi yang perlu diisi (nilai akhir belum lengkap atau deskripsi sudah ada).');
    }

    public function saveTemplates(Request $request, TeachingAssignment $assignment, SaveDescriptionTemplatesAction $save): RedirectResponse
    {
        $this->authorizeManage($request, $assignment);

        $validated = $request->validate(
            collect(Predicate::cases())->mapWithKeys(fn (Predicate $p) => ["templates.{$p->value}" => ['required', 'string', 'max:500']])->all(),
            [],
            collect(Predicate::cases())->mapWithKeys(fn (Predicate $p) => ["templates.{$p->value}" => "template predikat {$p->value}"])->all(),
        );

        $save->handle($assignment, $validated['templates']);

        return back()->with('success', 'Template deskripsi disimpan.');
    }

    public function export(Request $request, TeachingAssignment $assignment): BinaryFileResponse
    {
        $this->authorizeManage($request, $assignment);
        $book = Gradebook::make($assignment->load(['subject:id,name', 'classroom:id,name']), $this->semester($request));

        $name = str("nilai {$assignment->subject->name} {$assignment->classroom->name} {$book->semester->label()}")->slug().'.xlsx';

        return Excel::download(new GradesExport($book), $name);
    }

    public function import(Request $request, TeachingAssignment $assignment, ImportGradesAction $import): RedirectResponse
    {
        $this->authorizeManage($request, $assignment);

        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120']], [], ['file' => 'file']);

        /** @var UploadedFile $file */
        $file = $request->file('file');
        $result = $import->handle(Gradebook::make($assignment, $this->semester($request)), $file, $this->user($request));

        return back()
            ->with('success', "{$result['updated']} nilai diimpor.")
            ->with('error', $result['warnings'] === [] ? null : implode(' ', array_slice($result['warnings'], 0, 5)).(count($result['warnings']) > 5 ? ' …' : ''));
    }

    private function authorizeManage(Request $request, TeachingAssignment $assignment): void
    {
        abort_unless($this->access->canManage($this->user($request), $assignment), 403);
    }

    private function semester(Request $request): Semester
    {
        $semester = $this->semesters->selected($request);

        abort_if($semester === null, 404);

        return $semester;
    }

    private function student(TeachingAssignment $assignment, string $publicId): Student
    {
        $student = Student::query()
            ->where('public_id', $publicId)
            ->whereHas('enrollments', fn ($e) => $e->where('classroom_id', $assignment->classroom_id)->where('status', EnrollmentStatus::Active))
            ->first();

        abort_if($student === null, 422, 'Siswa bukan anggota aktif rombel ini.');

        return $student;
    }

    private function user(Request $request): User
    {
        /** @var User */
        return $request->user();
    }
}
