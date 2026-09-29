<?php

namespace App\Modules\Report\Reports;

use App\Modules\Access\Enums\Permission;
use App\Modules\Core\Enums\Role;
use App\Modules\Finance\Services\FinanceReport;
use Carbon\CarbonImmutable;

/**
 * Tunggakan per siswa (tagihan belum lunas), terbesar dulu.
 */
class OutstandingReport extends Report
{
    public function __construct(private FinanceReport $finance) {}

    public function key(): string
    {
        return 'tunggakan';
    }

    public function label(): string
    {
        return 'Tunggakan';
    }

    public function description(): string
    {
        return 'Siswa yang masih memiliki tagihan belum lunas beserta jumlah dan jatuh tempo terlama.';
    }

    protected function roles(): array
    {
        return [Role::Principal, Role::Operator];
    }

    protected function permissions(): array
    {
        return [Permission::FinanceView, Permission::FinanceManage];
    }

    public function fields(ReportContext $context): array
    {
        return [$this->classroomField($context)];
    }

    public function rules(ReportContext $context): array
    {
        return ['classroom' => $this->classroomRules($context)];
    }

    public function build(ReportContext $context, array $filters): ReportData
    {
        $classroom = $context->classroom($filters['classroom'] ?? null);
        $data = $this->finance->allOutstandingStudents($classroom?->public_id);

        $rows = array_map(fn (array $r, int $i) => [
            $i + 1,
            $r['nis'],
            $r['name'],
            $r['classroom'],
            $r['invoices'],
            $r['amount'],
            $r['oldest_due'] ? CarbonImmutable::parse($r['oldest_due'])->format('d/m/Y') : null,
        ], $data, array_keys($data));

        return new ReportData(
            title: 'Laporan Tunggakan',
            meta: [
                'Per '.CarbonImmutable::today()->translatedFormat('j F Y'),
                'Rombel: '.($classroom->name ?? 'semua rombel'),
            ],
            columns: ['No', 'NIS', 'Nama', 'Rombel', 'Jumlah tagihan', 'Tunggakan', 'Jatuh tempo terlama'],
            rows: $rows,
            footer: $rows === [] ? null : [null, null, 'Total', null, array_sum(array_column($data, 'invoices')), array_sum(array_column($data, 'amount')), null],
            numeric: [0, 4],
            currency: [5],
        );
    }
}
