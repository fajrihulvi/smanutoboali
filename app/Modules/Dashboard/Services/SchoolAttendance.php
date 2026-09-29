<?php

namespace App\Modules\Dashboard\Services;

use App\Modules\Attendance\Enums\AttendanceStatus;
use App\Modules\Attendance\Services\AttendanceRecap;
use App\Modules\People\Enums\StudentStatus;
use App\Modules\People\Models\Student;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Kehadiran siswa tingkat sekolah berbasis status harian (aturan sama dengan rekap presensi).
 */
class SchoolAttendance
{
    public function __construct(private AttendanceRecap $recap) {}

    /**
     * @return array{recorded: int, unrecorded: int, percentage: float|null, counts: array<string, int>}
     */
    public function today(CarbonImmutable $today): array
    {
        $summary = $this->recap->summarize(
            $this->recap->dailyStatuses($today, $today)->flatMap(fn (Collection $days) => $days->values()),
        );
        $active = Student::query()->where('status', StudentStatus::Active)->count();

        return [
            'recorded' => $summary['days'],
            'unrecorded' => max(0, $active - $summary['days']),
            'percentage' => $summary['percentage'],
            'counts' => $summary['counts'],
        ];
    }

    /**
     * Persentase kehadiran per hari yang punya data, N hari terakhir.
     *
     * @return list<array{date: string, percentage: float, present: int, absent: int, sick: int, permit: int, recorded: int}>
     */
    public function trend(CarbonImmutable $today, int $days = 30): array
    {
        $byDay = [];

        foreach ($this->recap->dailyStatuses($today->subDays($days - 1), $today) as $studentDays) {
            foreach ($studentDays as $date => $status) {
                $byDay[$date][] = $status;
            }
        }

        ksort($byDay);
        $trend = [];

        foreach ($byDay as $date => $statuses) {
            $summary = $this->recap->summarize($statuses);
            $trend[] = [
                'date' => (string) $date,
                'percentage' => (float) $summary['percentage'],
                'present' => $summary['counts'][AttendanceStatus::Present->value] + $summary['counts'][AttendanceStatus::Late->value],
                'absent' => $summary['counts'][AttendanceStatus::Absent->value],
                'sick' => $summary['counts'][AttendanceStatus::Sick->value],
                'permit' => $summary['counts'][AttendanceStatus::Permit->value],
                'recorded' => $summary['days'],
            ];
        }

        return $trend;
    }
}
