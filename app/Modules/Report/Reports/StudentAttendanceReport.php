<?php

namespace App\Modules\Report\Reports;

use App\Modules\Academic\Enums\EnrollmentStatus;
use App\Modules\Academic\Models\ClassEnrollment;
use App\Modules\Attendance\Enums\AttendanceStatus;
use App\Modules\Attendance\Services\AttendanceRecap;
use App\Modules\Core\Enums\Role;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

/**
 * Rekap presensi siswa per siswa dalam rentang tanggal (status harian terberat per hari).
 */
class StudentAttendanceReport extends Report
{
    public function __construct(private AttendanceRecap $recap) {}

    public function key(): string
    {
        return 'presensi-siswa';
    }

    public function label(): string
    {
        return 'Presensi siswa';
    }

    public function description(): string
    {
        return 'Jumlah hadir, terlambat, sakit, izin, alpa, dan persentase kehadiran per siswa.';
    }

    protected function roles(): array
    {
        return [Role::Principal, Role::Operator, Role::Teacher];
    }

    public function fields(ReportContext $context): array
    {
        return [...$this->dateRangeFields(), $this->classroomField($context)];
    }

    public function rules(ReportContext $context): array
    {
        return [...$this->dateRangeRules(), 'classroom' => $this->classroomRules($context)];
    }

    public function build(ReportContext $context, array $filters): ReportData
    {
        $from = CarbonImmutable::parse($filters['from']);
        $to = CarbonImmutable::parse($filters['to']);
        $classroom = $context->classroom($filters['classroom'] ?? null);
        $classrooms = $classroom ? new Collection([$classroom]) : $context->classrooms();

        $enrollments = ClassEnrollment::query()
            ->whereIn('classroom_id', $classrooms->modelKeys())
            ->where('status', EnrollmentStatus::Active)
            ->with('student:id,nis,name')
            ->get()
            ->sortBy(fn (ClassEnrollment $e) => [
                $classrooms->search(fn ($c) => $c->id === $e->classroom_id),
                $e->student->name,
            ])
            ->values();

        $statuses = $this->recap->dailyStatuses($from, $to, studentIds: $enrollments->pluck('student_id')->all());
        $names = $classrooms->pluck('name', 'id');
        $totals = array_fill(0, 6, 0);
        $rows = [];

        foreach ($enrollments as $i => $enrollment) {
            $summary = $this->recap->summarize($statuses->get($enrollment->student_id, collect()));
            $counts = [
                $summary['counts'][AttendanceStatus::Present->value],
                $summary['counts'][AttendanceStatus::Late->value],
                $summary['counts'][AttendanceStatus::Sick->value],
                $summary['counts'][AttendanceStatus::Permit->value],
                $summary['counts'][AttendanceStatus::Absent->value],
                $summary['days'],
            ];

            foreach ($counts as $k => $v) {
                $totals[$k] += $v;
            }

            $rows[] = [$i + 1, $enrollment->student->nis, $enrollment->student->name, $names->get($enrollment->classroom_id), ...$counts, $summary['percentage']];
        }

        $present = $totals[0] + $totals[1];

        return new ReportData(
            title: 'Rekap Presensi Siswa',
            meta: [
                $this->dateLabel($filters['from'], $filters['to']),
                'Rombel: '.($classroom->name ?? ($context->isTeacher() ? 'semua rombel yang Anda ajar' : 'semua rombel')),
            ],
            columns: ['No', 'NIS', 'Nama', 'Rombel', 'Hadir', 'Terlambat', 'Sakit', 'Izin', 'Alpa', 'Hari tercatat', 'Kehadiran (%)'],
            rows: $rows,
            footer: $rows === [] ? null : [null, null, 'Total', null, ...$totals, $totals[5] > 0 ? round($present / $totals[5] * 100, 1) : null],
            numeric: [0, 4, 5, 6, 7, 8, 9, 10],
            landscape: true,
        );
    }
}
