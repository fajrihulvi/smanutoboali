<?php

namespace App\Modules\Report\Reports;

use App\Modules\Attendance\Enums\TeacherAttendanceStatus;
use App\Modules\Attendance\Services\TeacherAttendanceRecap;
use App\Modules\Core\Enums\Role;
use Carbon\CarbonImmutable;

/**
 * Rekap presensi guru per guru dalam rentang tanggal.
 */
class TeacherAttendanceReport extends Report
{
    public function __construct(private TeacherAttendanceRecap $recap) {}

    public function key(): string
    {
        return 'presensi-guru';
    }

    public function label(): string
    {
        return 'Presensi guru';
    }

    public function description(): string
    {
        return 'Kehadiran, keterlambatan, izin, sakit, dinas, dan alpa per guru.';
    }

    protected function roles(): array
    {
        return [Role::Principal, Role::Operator];
    }

    public function fields(ReportContext $context): array
    {
        return $this->dateRangeFields();
    }

    public function rules(ReportContext $context): array
    {
        return $this->dateRangeRules();
    }

    public function build(ReportContext $context, array $filters): ReportData
    {
        $statuses = TeacherAttendanceStatus::cases();
        $rows = [];

        foreach ($this->recap->period(CarbonImmutable::parse($filters['from']), CarbonImmutable::parse($filters['to'])) as $i => $row) {
            $rows[] = [
                $i + 1,
                $row['teacher']['nip'],
                $row['teacher']['name'],
                ...array_map(fn (TeacherAttendanceStatus $s) => $row['summary']['counts'][$s->value], $statuses),
                $row['summary']['days'],
                $row['summary']['percentage'],
            ];
        }

        $columns = ['No', 'NIP', 'Nama', ...array_map(fn (TeacherAttendanceStatus $s) => $s->label(), $statuses), 'Hari tercatat', 'Kehadiran (%)'];

        return new ReportData(
            title: 'Rekap Presensi Guru',
            meta: [$this->dateLabel($filters['from'], $filters['to'])],
            columns: $columns,
            rows: $rows,
            numeric: [0, ...range(3, count($columns) - 1)],
            landscape: true,
        );
    }
}
