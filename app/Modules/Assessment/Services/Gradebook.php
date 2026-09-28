<?php

namespace App\Modules\Assessment\Services;

use App\Modules\Academic\Enums\EnrollmentStatus;
use App\Modules\Academic\Models\TeachingAssignment;
use App\Modules\Assessment\Enums\Predicate;
use App\Modules\Assessment\Models\Grade;
use App\Modules\Assessment\Models\GradeComponent;
use App\Modules\Assessment\Models\GradeDescription;
use App\Modules\Assessment\Models\GradeDescriptionTemplate;
use App\Modules\People\Models\Student;
use App\Modules\School\Models\Semester;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Buku nilai satu mapel-rombel di satu semester.
 *
 * Nilai akhir = Σ(nilai × bobot) ÷ 100, dibulatkan ke bilangan bulat — hanya bila total bobot = 100
 * dan semua komponen siswa terisi; selain itu null.
 */
class Gradebook
{
    /** @var Collection<int, GradeComponent> */
    public Collection $components;

    /** @var Collection<int, Student> */
    public Collection $students;

    /** @var array<int, array<int, float>> component_id → student_id → nilai */
    private array $scores = [];

    /** @var array<int, string> student_id → deskripsi */
    private array $descriptions = [];

    /** @var array<string, string> predikat → template */
    private array $templates = [];

    public function __construct(
        public TeachingAssignment $assignment,
        public Semester $semester,
        private AssessmentSettings $settings,
    ) {
        $this->components = GradeComponent::query()->for($assignment->id, $semester->id)->get();

        $this->students = Student::query()
            ->whereHas('enrollments', fn (Builder $e) => $e
                ->where('classroom_id', $assignment->classroom_id)
                ->where('status', EnrollmentStatus::Active))
            ->orderBy('name')
            ->get(['id', 'public_id', 'nis', 'name']);

        Grade::query()
            ->whereIn('grade_component_id', $this->components->modelKeys())
            ->get(['grade_component_id', 'student_id', 'score'])
            ->each(function (Grade $grade) {
                $this->scores[$grade->grade_component_id][$grade->student_id] = $grade->value();
            });

        $this->descriptions = GradeDescription::query()
            ->where('teaching_assignment_id', $assignment->id)
            ->where('semester_id', $semester->id)
            ->pluck('description', 'student_id')
            ->all();

        $this->templates = self::templatesFor($assignment);
    }

    public static function make(TeachingAssignment $assignment, Semester $semester): self
    {
        return new self($assignment, $semester, app(AssessmentSettings::class));
    }

    /**
     * Template per predikat; yang belum disimpan memakai template bawaan.
     *
     * @return array<string, string>
     */
    public static function templatesFor(TeachingAssignment $assignment): array
    {
        $stored = GradeDescriptionTemplate::query()
            ->where('teaching_assignment_id', $assignment->id)
            ->get()
            ->mapWithKeys(fn (GradeDescriptionTemplate $t) => [$t->predicate->value => $t->template]);

        $templates = [];

        foreach (Predicate::cases() as $predicate) {
            $templates[$predicate->value] = $stored->get($predicate->value, $predicate->defaultTemplate());
        }

        return $templates;
    }

    public function totalWeight(): int
    {
        return (int) $this->components->sum('weight');
    }

    public function score(GradeComponent $component, Student $student): ?float
    {
        return $this->scores[$component->id][$student->id] ?? null;
    }

    public function finalScore(Student $student): ?int
    {
        if ($this->components->isEmpty() || $this->totalWeight() !== 100) {
            return null;
        }

        $sum = 0.0;

        foreach ($this->components as $component) {
            $score = $this->score($component, $student);

            if ($score === null) {
                return null;
            }

            $sum += $score * $component->weight;
        }

        return (int) round($sum / 100, 0, PHP_ROUND_HALF_UP);
    }

    public function predicate(Student $student): ?Predicate
    {
        $final = $this->finalScore($student);

        return $final === null ? null : $this->settings->predicateFor($final);
    }

    public function description(Student $student): ?string
    {
        return $this->descriptions[$student->id] ?? null;
    }

    /**
     * Deskripsi dari template sesuai predikat siswa (null bila nilai akhir belum ada).
     */
    public function suggestedDescription(Student $student): ?string
    {
        $predicate = $this->predicate($student);

        if ($predicate === null) {
            return null;
        }

        return strtr($this->templates[$predicate->value], [
            '{nama}' => $student->name,
            '{mapel}' => $this->assignment->subject->name,
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function templates(): array
    {
        return $this->templates;
    }

    /**
     * Props halaman buku nilai.
     *
     * @return array<string, mixed>
     */
    public function toProps(bool $readOnly): array
    {
        $assignment = $this->assignment->loadMissing(['subject:id,name', 'classroom:id,public_id,name', 'teacher:id,name']);

        return [
            'assignment' => [
                'public_id' => $assignment->public_id,
                'subject' => $assignment->subject->name,
                'classroom' => $assignment->classroom->name,
                'teacher' => $assignment->teacher->name,
            ],
            'semester' => ['public_id' => $this->semester->public_id, 'label' => $this->semester->label()],
            'components' => $this->components->map(fn (GradeComponent $c) => [
                'public_id' => $c->public_id,
                'type' => $c->type->value,
                'name' => $c->name,
                'weight' => $c->weight,
                'published_at' => $c->published_at?->toIso8601String(),
            ])->values(),
            'students' => $this->students->map(fn (Student $student) => [
                'public_id' => $student->public_id,
                'nis' => $student->nis,
                'name' => $student->name,
                'scores' => $this->components->mapWithKeys(fn (GradeComponent $c) => [$c->public_id => $this->score($c, $student)]),
                'final' => $this->finalScore($student),
                'predicate' => $this->predicate($student)?->value,
                'description' => $this->description($student),
                'suggested' => $this->suggestedDescription($student),
            ])->values(),
            'totalWeight' => $this->totalWeight(),
            'kkm' => $this->settings->kkm(),
            'templates' => $this->templates(),
            'readOnly' => $readOnly,
        ];
    }
}
