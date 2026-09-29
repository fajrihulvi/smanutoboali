<?php

namespace App\Modules\Report\Reports;

use App\Modules\Academic\Enums\EnrollmentStatus;
use App\Modules\Academic\Models\ClassEnrollment;
use App\Modules\Academic\Models\TeachingAssignment;
use App\Modules\Assessment\Services\GradeRecap;
use App\Modules\Assessment\Services\SemesterPicker;
use App\Modules\Core\Enums\Role;
use App\Modules\School\Models\Semester;
use App\Support\ActivePeriod;
use Illuminate\Validation\Rule;

/**
 * Matriks nilai akhir satu rombel per mapel (aturan GradeRecap: kosong bila belum lengkap).
 * Guru: hanya mapel yang diajarnya, kecuali di rombel perwaliannya (semua mapel).
 */
class GradesReport extends Report
{
    public function __construct(private GradeRecap $recap, private SemesterPicker $semesters) {}

    public function key(): string
    {
        return 'nilai';
    }

    public function label(): string
    {
        return 'Nilai akhir';
    }

    public function description(): string
    {
        return 'Nilai akhir per mapel dan rata-rata setiap siswa dalam satu rombel.';
    }

    protected function roles(): array
    {
        return [Role::Principal, Role::Operator, Role::Teacher];
    }

    public function fields(ReportContext $context): array
    {
        $options = $this->semesters->toOptions();

        return [
            [
                'name' => 'semester', 'label' => 'Semester', 'type' => 'select', 'options' => $options, 'required' => true,
                'default' => app(ActivePeriod::class)->semester()->public_id ?? ($options[0]['value'] ?? null),
            ],
            $this->classroomField($context, required: true),
        ];
    }

    public function rules(ReportContext $context): array
    {
        return [
            'semester' => ['required', 'string', Rule::in(array_column($this->semesters->toOptions(), 'value'))],
            'classroom' => $this->classroomRules($context, required: true),
        ];
    }

    public function build(ReportContext $context, array $filters): ReportData
    {
        $semester = Semester::query()->where('public_id', $filters['semester'])->firstOrFail();
        $classroom = $context->classroom($filters['classroom']);
        abort_if($classroom === null, 404);

        $teacher = $context->teacher();
        $allSubjects = ! $context->isTeacher() || $classroom->homeroom_teacher_id === $teacher?->id;

        $assignments = TeachingAssignment::query()
            ->where('classroom_id', $classroom->id)
            ->when(! $allSubjects, fn ($q) => $q->where('teacher_id', $teacher?->id))
            ->with('subject:id,name')
            ->get()
            ->sortBy(fn (TeachingAssignment $a) => $a->subject->name)
            ->values();

        $finals = $this->recap->finals($semester, $assignments);
        $students = ClassEnrollment::query()
            ->where('classroom_id', $classroom->id)
            ->where('status', EnrollmentStatus::Active)
            ->with('student:id,nis,name')
            ->get()
            ->sortBy(fn (ClassEnrollment $e) => $e->student->name)
            ->values();

        $rows = [];

        foreach ($students as $i => $enrollment) {
            $scores = $assignments->map(fn (TeachingAssignment $a) => $finals[$a->id][$enrollment->student_id] ?? null)->all();
            $filled = array_values(array_filter($scores, fn (?int $s) => $s !== null));
            $rows[] = [
                $i + 1,
                $enrollment->student->nis,
                $enrollment->student->name,
                ...$scores,
                $filled === [] ? null : round(array_sum($filled) / count($filled), 1),
            ];
        }

        $columns = ['No', 'NIS', 'Nama', ...$assignments->map(fn (TeachingAssignment $a) => $a->subject->name)->all(), 'Rata-rata'];

        return new ReportData(
            title: 'Rekap Nilai Akhir',
            meta: [
                "Rombel: {$classroom->name}",
                'Semester: '.$semester->label(),
                'Nilai kosong (–) berarti komponen nilai belum lengkap atau bobot belum 100%.',
            ],
            columns: $columns,
            rows: $rows,
            numeric: [0, ...range(3, count($columns) - 1)],
            landscape: $assignments->count() > 5,
        );
    }
}
