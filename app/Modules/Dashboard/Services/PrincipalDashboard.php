<?php

namespace App\Modules\Dashboard\Services;

use App\Models\User;
use App\Modules\Academic\Models\TeachingAssignment;
use App\Modules\Access\Enums\Permission;
use App\Modules\Activity\Enums\ActivityStatus;
use App\Modules\Activity\Models\Activity;
use App\Modules\Assessment\Services\GradeRecap;
use App\Modules\Finance\Enums\InvoiceStatus;
use App\Modules\Finance\Models\Invoice;
use App\Modules\People\Enums\StudentStatus;
use App\Modules\People\Models\Student;
use App\Modules\People\Models\Teacher;
use App\Modules\Ppdb\Enums\ApplicantStatus;
use App\Modules\Ppdb\Models\PpdbApplicant;
use App\Modules\Ppdb\Models\PpdbPeriod;
use App\Modules\ReportCard\Enums\ReportCardStatus;
use App\Modules\ReportCard\Models\ReportCard;
use App\Support\ActivePeriod;
use Carbon\CarbonImmutable;

/**
 * Dashboard kepala sekolah: KPI sekolah, tren kehadiran, distribusi nilai, raport menunggu persetujuan.
 */
class PrincipalDashboard
{
    public function __construct(
        private DashboardCache $cache,
        private SchoolAttendance $attendance,
        private ActivePeriod $period,
        private GradeRecap $grades,
        private AuditFeed $audit,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function for(User $user, CarbonImmutable $today): array
    {
        $yearId = $this->period->academicYearId();
        $semesterId = $this->period->semesterId();

        return [
            'kpi' => $this->cache->remember("principal.kpi.{$yearId}", $today, fn () => [
                'students' => Student::query()->where('status', StudentStatus::Active)->count(),
                'teachers' => Teacher::query()->where('is_active', true)->count(),
                'attendance' => $this->attendance->today($today),
                'ppdb' => $this->ppdb(),
                'finance' => $this->billing($yearId),
            ]),
            'attendanceTrend' => $this->cache->remember('principal.trend', $today, fn () => $this->attendance->trend($today)),
            'grades' => $this->cache->remember("principal.grades.{$semesterId}", $today, fn () => $this->gradeDistribution()),
            'reportCards' => $this->cache->remember("principal.report-cards.{$semesterId}", $today, fn () => $this->reportCards($semesterId)),
            'activitiesToday' => $this->cache->remember('principal.activities', $today, fn () => Activity::query()
                ->where('status', ActivityStatus::Published)
                ->whereDate('date', $today->toDateString())
                ->orderBy('start_time')
                ->limit(5)
                ->get(['public_id', 'title', 'start_time', 'location'])
                ->map(fn (Activity $a) => [
                    'public_id' => $a->public_id,
                    'title' => $a->title,
                    'time' => $a->start_time ? substr($a->start_time, 0, 5) : null,
                    'location' => $a->location,
                ])->values()->all()),
            'recentActivity' => $user->can(Permission::AuditLogView->value) ? $this->audit->latest() : [],
        ];
    }

    /**
     * @return array{period: string, applicants: int, verified: int, accepted: int, quota: int}|null
     */
    private function ppdb(): ?array
    {
        $period = PpdbPeriod::query()->where('is_active', true)->withSum('tracks', 'quota')->orderByDesc('opens_at')->first();

        if ($period === null) {
            return null;
        }

        $counts = PpdbApplicant::query()
            ->where('ppdb_period_id', $period->id)
            ->where('status', '!=', ApplicantStatus::Draft)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->toBase()
            ->pluck('total', 'status');

        $sum = fn (ApplicantStatus ...$statuses) => (int) collect($statuses)->sum(fn (ApplicantStatus $s) => $counts->get($s->value, 0));

        return [
            'period' => $period->name,
            'applicants' => (int) $counts->sum(),
            'verified' => $sum(ApplicantStatus::Verified, ApplicantStatus::InSelection, ApplicantStatus::Accepted, ApplicantStatus::Rejected, ApplicantStatus::ReRegistered),
            'accepted' => $sum(ApplicantStatus::Accepted, ApplicantStatus::ReRegistered),
            'quota' => (int) $period->getAttribute('tracks_sum_quota'),
        ];
    }

    /**
     * @return array{billed: int, paid: int, percentage: float|null}
     */
    private function billing(?int $yearId): array
    {
        $row = Invoice::query()
            ->where('academic_year_id', $yearId ?? 0)
            ->where('status', '!=', InvoiceStatus::Cancelled)
            ->selectRaw('COALESCE(SUM(total), 0) as billed, COALESCE(SUM(paid_amount), 0) as paid')
            ->toBase()
            ->first();

        $billed = (int) ($row->billed ?? 0);
        $paid = (int) ($row->paid ?? 0);

        return ['billed' => $billed, 'paid' => $paid, 'percentage' => $billed > 0 ? round($paid / $billed * 100, 1) : null];
    }

    /**
     * @return array{semester: string|null, students: int, graded: int, average: float|null, below_kkm: int, distribution: array<string, int>}|null
     */
    private function gradeDistribution(): ?array
    {
        $semester = $this->period->semester();

        if ($semester === null) {
            return null;
        }

        $assignments = TeachingAssignment::query()->where('academic_year_id', $semester->academic_year_id)->get(['id', 'classroom_id']);
        $finals = [];

        foreach ($this->grades->finals($semester, $assignments) as $perStudent) {
            array_push($finals, ...array_values($perStudent));
        }

        return ['semester' => $semester->type->label(), ...$this->grades->summarize($finals)];
    }

    /**
     * @return array{awaiting: int, published: int, total: int}
     */
    private function reportCards(?int $semesterId): array
    {
        $counts = ReportCard::query()
            ->where('semester_id', $semesterId ?? 0)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->toBase()
            ->pluck('total', 'status');

        return [
            'awaiting' => (int) $counts->get(ReportCardStatus::Validated->value, 0),
            'published' => (int) $counts->get(ReportCardStatus::Published->value, 0),
            'total' => (int) $counts->sum(),
        ];
    }
}
