<?php

namespace App\Modules\Dashboard\Services;

use App\Models\User;
use App\Modules\Academic\Enums\EnrollmentStatus;
use App\Modules\Access\Enums\Permission;
use App\Modules\Cms\Enums\ContentStatus;
use App\Modules\Cms\Models\WebsiteGallery;
use App\Modules\Cms\Models\WebsitePage;
use App\Modules\Cms\Models\WebsitePost;
use App\Modules\Finance\Services\FinanceReport;
use App\Modules\People\Enums\StudentStatus;
use App\Modules\People\Models\Guardian;
use App\Modules\People\Models\Student;
use App\Modules\People\Models\Teacher;
use App\Modules\Ppdb\Enums\ApplicantStatus;
use App\Modules\Ppdb\Models\PpdbApplicant;
use App\Modules\ReportCard\Enums\ReportCardStatus;
use App\Modules\ReportCard\Models\ReportCard;
use App\Support\ActivePeriod;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * Dashboard operator: jumlah data, data belum lengkap, antrean kerja (PPDB, pembayaran, raport, konten).
 */
class OperatorDashboard
{
    public function __construct(
        private DashboardCache $cache,
        private ActivePeriod $period,
        private FinanceReport $finance,
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
            'counts' => $this->cache->remember('operator.counts', $today, fn () => [
                'students' => Student::query()->where('status', StudentStatus::Active)->count(),
                'teachers' => Teacher::query()->where('is_active', true)->count(),
                'guardians' => Guardian::query()->count(),
            ]),
            'incomplete' => $this->cache->remember("operator.incomplete.{$yearId}", $today, fn () => [
                'students_without_guardian' => Student::query()->where('status', StudentStatus::Active)->doesntHave('guardians')->count(),
                'students_without_classroom' => $yearId === null ? null : Student::query()
                    ->where('status', StudentStatus::Active)
                    ->whereDoesntHave('enrollments', fn (Builder $q) => $q->where('academic_year_id', $yearId)->where('status', EnrollmentStatus::Active))
                    ->count(),
                'guardians_without_account' => Guardian::query()->whereNull('user_id')->count(),
                'teachers_without_account' => Teacher::query()->where('is_active', true)->whereNull('user_id')->count(),
            ]),
            // Antrean kerja: tidak di-cache agar angka langsung berubah setelah diproses.
            'queues' => [
                'ppdb_to_verify' => $user->can(Permission::PpdbManage->value)
                    ? PpdbApplicant::query()->where('status', ApplicantStatus::Submitted)->count() : null,
                'payments_to_verify' => $user->can(Permission::FinanceManage->value)
                    ? $this->finance->summary($today)['pending_verification'] : null,
                'report_cards_to_validate' => $user->can(Permission::ReportCardValidate->value)
                    ? ReportCard::query()->where('semester_id', $semesterId ?? 0)->where('status', ReportCardStatus::HomeroomReviewed)->count() : null,
                'content_to_review' => $user->can(Permission::CmsManage->value) ? $this->contentCounts() : null,
            ],
            'finance' => $user->can(Permission::FinanceManage->value)
                ? $this->cache->remember('operator.finance', $today, fn () => $this->finance->summary($today))
                : null,
            'recentActivity' => $user->can(Permission::AuditLogView->value) ? $this->audit->latest() : [],
        ];
    }

    /**
     * @return array{draft: int, review: int}
     */
    private function contentCounts(): array
    {
        $count = fn (ContentStatus $status) => WebsitePage::query()->where('status', $status)->count()
            + WebsitePost::query()->where('status', $status)->count()
            + WebsiteGallery::query()->where('status', $status)->count();

        return ['draft' => $count(ContentStatus::Draft), 'review' => $count(ContentStatus::Review)];
    }
}
