<?php

namespace App\Modules\Report\Reports;

use App\Modules\Access\Enums\Permission;
use App\Modules\Core\Enums\Role;
use App\Modules\Ppdb\Enums\ApplicantStatus;
use App\Modules\Ppdb\Models\PpdbApplicant;
use App\Modules\Ppdb\Models\PpdbPeriod;
use App\Modules\Ppdb\Models\PpdbTrack;
use Illuminate\Validation\Rule;

/**
 * Daftar pendaftar PPDB satu periode (draf tidak ikut).
 */
class PpdbReport extends Report
{
    public function key(): string
    {
        return 'ppdb';
    }

    public function label(): string
    {
        return 'Pendaftar PPDB';
    }

    public function description(): string
    {
        return 'Nomor pendaftaran, jalur, status, skor, dan usulan seleksi setiap pendaftar.';
    }

    protected function roles(): array
    {
        return [Role::Principal, Role::Operator];
    }

    protected function permissions(): array
    {
        return [Permission::PpdbManage, Permission::PpdbDecide];
    }

    public function fields(ReportContext $context): array
    {
        $periods = PpdbPeriod::query()->orderByDesc('opens_at')->get(['id', 'public_id', 'name']);
        $tracks = PpdbTrack::query()->whereIn('ppdb_period_id', $periods->modelKeys())->orderBy('position')->get(['public_id', 'name', 'ppdb_period_id']);
        $periodNames = $periods->pluck('name', 'id');

        return [
            [
                'name' => 'period', 'label' => 'Periode', 'type' => 'select', 'required' => true,
                'options' => $periods->map(fn (PpdbPeriod $p) => ['value' => $p->public_id, 'label' => $p->name])->values()->all(),
                'default' => $periods->first()?->public_id,
            ],
            [
                'name' => 'track', 'label' => 'Jalur', 'type' => 'select', 'required' => false, 'default' => null, 'placeholder' => 'Semua jalur',
                'options' => $tracks->map(fn (PpdbTrack $t) => ['value' => $t->public_id, 'label' => $t->name.' · '.$periodNames->get($t->ppdb_period_id)])->values()->all(),
            ],
            [
                'name' => 'status', 'label' => 'Status', 'type' => 'select', 'required' => false, 'default' => null, 'placeholder' => 'Semua status',
                'options' => collect(ApplicantStatus::cases())->reject(fn (ApplicantStatus $s) => $s === ApplicantStatus::Draft)
                    ->map(fn (ApplicantStatus $s) => ['value' => $s->value, 'label' => $s->label()])->values()->all(),
            ],
        ];
    }

    public function rules(ReportContext $context): array
    {
        return [
            'period' => ['required', 'string', Rule::exists('ppdb_periods', 'public_id')],
            'track' => ['nullable', 'string', Rule::exists('ppdb_tracks', 'public_id')],
            'status' => ['nullable', Rule::enum(ApplicantStatus::class)],
        ];
    }

    public function build(ReportContext $context, array $filters): ReportData
    {
        $period = PpdbPeriod::query()->where('public_id', $filters['period'])->firstOrFail();
        $track = ($filters['track'] ?? null) ? PpdbTrack::query()->where('public_id', $filters['track'])->where('ppdb_period_id', $period->id)->first() : null;
        $status = ApplicantStatus::tryFrom((string) ($filters['status'] ?? ''));

        $applicants = PpdbApplicant::query()
            ->where('ppdb_period_id', $period->id)
            ->where('status', '!=', ApplicantStatus::Draft)
            ->when($track, fn ($q) => $q->where('ppdb_track_id', $track?->id))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->with('track:id,name')
            ->orderBy('registration_number')
            ->get();

        $rows = $applicants->values()->map(fn (PpdbApplicant $a, int $i) => [
            $i + 1,
            $a->registration_number,
            $a->name,
            $a->track->name,
            $a->status->label(),
            $a->score === null ? null : (float) $a->score,
            $a->proposal?->label(),
            $a->submitted_at?->format('d/m/Y H.i'),
        ])->all();

        return new ReportData(
            title: 'Daftar Pendaftar PPDB',
            meta: [
                "Periode: {$period->name}",
                $track ? "Jalur: {$track->name}" : 'Semua jalur',
                ...($status ? ['Status: '.$status->label()] : []),
            ],
            columns: ['No', 'No. pendaftaran', 'Nama', 'Jalur', 'Status', 'Skor', 'Usulan', 'Dikirim'],
            rows: $rows,
            numeric: [0, 5],
            landscape: true,
        );
    }
}
