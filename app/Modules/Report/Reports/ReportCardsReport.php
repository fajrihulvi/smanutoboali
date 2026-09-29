<?php

namespace App\Modules\Report\Reports;

use App\Modules\Assessment\Services\SemesterPicker;
use App\Modules\Core\Enums\Role;
use App\Modules\ReportCard\Enums\ReportCardStatus;
use App\Modules\ReportCard\Models\ReportCard;
use App\Modules\School\Models\Semester;
use App\Support\ActivePeriod;
use Illuminate\Validation\Rule;

/**
 * Status raport per siswa (untuk memantau progres approval & penerbitan).
 */
class ReportCardsReport extends Report
{
    public function __construct(private SemesterPicker $semesters) {}

    public function key(): string
    {
        return 'raport';
    }

    public function label(): string
    {
        return 'Status raport';
    }

    public function description(): string
    {
        return 'Tahap raport setiap siswa: draf, diajukan, divalidasi, disetujui, terbit.';
    }

    protected function roles(): array
    {
        return [Role::Principal, Role::Operator];
    }

    public function fields(ReportContext $context): array
    {
        $options = $this->semesters->toOptions();

        return [
            [
                'name' => 'semester', 'label' => 'Semester', 'type' => 'select', 'options' => $options, 'required' => true,
                'default' => app(ActivePeriod::class)->semester()->public_id ?? ($options[0]['value'] ?? null),
            ],
            $this->classroomField($context),
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ReportCardStatus::options(), 'required' => false, 'default' => null, 'placeholder' => 'Semua status'],
        ];
    }

    public function rules(ReportContext $context): array
    {
        return [
            'semester' => ['required', 'string', Rule::in(array_column($this->semesters->toOptions(), 'value'))],
            'classroom' => $this->classroomRules($context),
            'status' => ['nullable', Rule::enum(ReportCardStatus::class)],
        ];
    }

    public function build(ReportContext $context, array $filters): ReportData
    {
        $semester = Semester::query()->where('public_id', $filters['semester'])->firstOrFail();
        $classroom = $context->classroom($filters['classroom'] ?? null);
        $status = ReportCardStatus::tryFrom((string) ($filters['status'] ?? ''));

        $cards = ReportCard::query()
            ->where('semester_id', $semester->id)
            ->when($classroom, fn ($q) => $q->where('classroom_id', $classroom?->id))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->with(['student:id,nis,name', 'classroom:id,name,grade'])
            ->get()
            ->sortBy(fn (ReportCard $c) => [$c->classroom->grade, $c->classroom->name, $c->student->name])
            ->values();

        $rows = $cards->map(fn (ReportCard $c, int $i) => [
            $i + 1,
            $c->student->nis,
            $c->student->name,
            $c->classroom->name,
            $c->status->label(),
            $c->published_at?->translatedFormat('j M Y'),
        ])->all();

        return new ReportData(
            title: 'Status Raport',
            meta: [
                'Semester: '.$semester->label(),
                'Rombel: '.($classroom->name ?? 'semua rombel'),
                ...($status ? ['Status: '.$status->label()] : []),
            ],
            columns: ['No', 'NIS', 'Nama', 'Rombel', 'Status', 'Tanggal terbit'],
            rows: $rows,
            numeric: [0],
        );
    }
}
