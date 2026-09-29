<?php

namespace App\Modules\Dashboard\Services;

use App\Models\User;
use App\Modules\Academic\Enums\EnrollmentStatus;
use App\Modules\Academic\Models\ClassEnrollment;
use App\Modules\Activity\Models\Activity;
use App\Modules\Activity\Services\ActivityVisibility;
use App\Modules\Announcement\Models\Announcement;
use App\Modules\Announcement\Services\AnnouncementAudienceResolver;
use App\Modules\Assessment\Models\Grade;
use App\Modules\Attendance\Models\AttendanceRecord;
use App\Modules\ChildCondition\Models\ChildCondition;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Services\FileUrlGenerator;
use App\Modules\Finance\Models\Invoice;
use App\Modules\People\Models\Student;
use App\Modules\Portfolio\Enums\PortfolioVisibility;
use App\Modules\Portfolio\Models\Portfolio;
use App\Support\ActivePeriod;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Ringkasan beranda orang tua untuk anak aktif (mobile-first).
 */
class GuardianOverview
{
    public function __construct(
        private ActivePeriod $period,
        private ActivityVisibility $activities,
        private AnnouncementAudienceResolver $announcements,
        private FileUrlGenerator $urls,
        private RecentChats $chats,
    ) {}

    /**
     * @param  Collection<int, Student>  $children  semua anak (untuk kartu per anak)
     * @return array<string, mixed>
     */
    public function for(User $guardian, Collection $children, ?Student $child, CarbonImmutable $today): array
    {
        return [
            'childCards' => $children->count() > 1 ? $this->childCards($children, $child, $today) : [],
            'invoices' => $child ? $this->dueInvoices($child, $today) : [],
            'recentChats' => $this->chats->for($guardian, '/orang-tua/chat', 3),
            'child' => $child ? $this->child($child) : null,
            'attendanceToday' => $child ? $this->attendanceToday($child, $today) : null,
            'activitiesToday' => $child ? $this->activitiesToday($child, $today) : [],
            'latestGrades' => $child ? $this->latestGrades($child) : [],
            'latestPortfolio' => $child ? $this->latestPortfolio($child) : null,
            'latestCondition' => $child ? $this->latestCondition($child, $guardian) : null,
            'announcements' => $this->latestAnnouncements($guardian),
        ];
    }

    /**
     * @return array{public_id: string, name: string, classroom: string|null, homeroom: string|null, photo_url: string|null}
     */
    private function child(Student $child): array
    {
        $enrollment = ClassEnrollment::query()
            ->where('student_id', $child->id)
            ->where('academic_year_id', $this->period->academicYearId() ?? 0)
            ->where('status', EnrollmentStatus::Active)
            ->with('classroom.homeroomTeacher')
            ->first();

        $child->loadMissing('photo');

        return [
            'public_id' => $child->public_id,
            'name' => $child->name,
            'classroom' => $enrollment?->classroom->name,
            'homeroom' => $enrollment?->classroom->homeroomTeacher?->name,
            'photo_url' => $child->photo ? $this->urls->thumbnailUrl($child->photo) : null,
        ];
    }

    /**
     * Kartu ringkas per anak: rombel, kehadiran hari ini, tunggakan (beberapa query untuk semua anak).
     *
     * @param  Collection<int, Student>  $children
     * @return list<array{public_id: string, name: string, photo_url: string|null, classroom: string|null, attendance: array{status: string, label: string}|null, outstanding: int, active: bool}>
     */
    private function childCards(Collection $children, ?Student $active, CarbonImmutable $today): array
    {
        $ids = $children->modelKeys();

        $classrooms = ClassEnrollment::query()
            ->whereIn('student_id', $ids)
            ->where('academic_year_id', $this->period->academicYearId() ?? 0)
            ->where('status', EnrollmentStatus::Active)
            ->with('classroom:id,name')
            ->get()
            ->mapWithKeys(fn (ClassEnrollment $e) => [$e->student_id => $e->classroom->name]);

        $attendance = AttendanceRecord::query()
            ->whereIn('student_id', $ids)
            ->whereHas('session', fn (Builder $s) => $s->whereOnDate('date', $today->toDateString()))
            ->orderBy('id')
            ->get(['student_id', 'status'])
            ->groupBy('student_id')
            ->map(fn ($records) => $records->sortByDesc(fn (AttendanceRecord $r) => $r->status->severity())->first());

        $outstanding = Invoice::query()
            ->outstanding()
            ->whereIn('student_id', $ids)
            ->groupBy('student_id')
            ->select('student_id', DB::raw('SUM(total - paid_amount) as amount'))
            ->toBase()
            ->pluck('amount', 'student_id');

        return $children->map(function (Student $student) use ($classrooms, $attendance, $outstanding, $active) {
            $record = $attendance->get($student->id);

            return [
                'public_id' => $student->public_id,
                'name' => $student->name,
                'photo_url' => $student->photo ? $this->urls->thumbnailUrl($student->photo) : null,
                'classroom' => $classrooms->get($student->id),
                'attendance' => $record ? ['status' => $record->status->value, 'label' => $record->status->label()] : null,
                'outstanding' => (int) $outstanding->get($student->id, 0),
                'active' => $active?->is($student) ?? false,
            ];
        })->values()->all();
    }

    /**
     * Tagihan belum lunas anak aktif, jatuh tempo terdekat dulu.
     *
     * @return list<array{public_id: string, title: string, remaining: int, due_date: string, overdue: bool}>
     */
    private function dueInvoices(Student $child, CarbonImmutable $today): array
    {
        return Invoice::query()
            ->outstanding()
            ->where('student_id', $child->id)
            ->with('feeType:id,name')
            ->orderBy('due_date')
            ->limit(3)
            ->get()
            ->map(fn (Invoice $i) => [
                'public_id' => $i->public_id,
                'title' => $i->feeType->name.' '.$i->period_label,
                'remaining' => $i->remaining(),
                'due_date' => $i->due_date->toDateString(),
                'overdue' => $i->isOverdue($today),
            ])->values()->all();
    }

    /**
     * Status presensi harian hari ini (sesi tanpa jadwal); null = belum diisi.
     *
     * @return array{status: string, label: string}|null
     */
    private function attendanceToday(Student $child, CarbonImmutable $today): ?array
    {
        $record = AttendanceRecord::query()
            ->where('student_id', $child->id)
            ->whereHas('session', fn (Builder $s) => $s->whereOnDate('date', $today->toDateString()))
            ->orderBy('id')
            ->first();

        return $record ? ['status' => $record->status->value, 'label' => $record->status->label()] : null;
    }

    /**
     * @return list<array{public_id: string, title: string, time: string|null, location: string|null}>
     */
    private function activitiesToday(Student $child, CarbonImmutable $today): array
    {
        return $this->activities->publishedForStudents([$child->id])
            ->whereOnDate('date', $today->toDateString())
            ->orderBy('start_time')
            ->limit(3)
            ->get()
            ->map(fn (Activity $a) => [
                'public_id' => $a->public_id,
                'title' => $a->title,
                'time' => $a->start_time ? substr($a->start_time, 0, 5) : null,
                'location' => $a->location,
            ])->values()->all();
    }

    /**
     * @return list<array{subject: string, component: string, score: string, published_at: string|null}>
     */
    private function latestGrades(Student $child): array
    {
        return Grade::query()
            ->where('student_id', $child->id)
            ->whereHas('component', fn (Builder $c) => $c->whereNotNull('published_at'))
            ->with('component.teachingAssignment.subject')
            ->get()
            ->sortByDesc(fn (Grade $g) => $g->component->published_at?->getTimestamp() ?? 0)
            ->take(3)
            ->map(fn (Grade $g) => [
                'subject' => $g->component->teachingAssignment->subject->name,
                'component' => $g->component->name,
                'score' => rtrim(rtrim(number_format($g->value(), 2, ',', ''), '0'), ','),
                'published_at' => $g->component->published_at?->toIso8601String(),
            ])->values()->all();
    }

    /**
     * @return array{title: string, category: string, date: string, thumbnail: string|null}|null
     */
    private function latestPortfolio(Student $child): ?array
    {
        $portfolio = Portfolio::query()
            ->where('visibility', PortfolioVisibility::Published)
            ->whereHas('students', fn (Builder $s) => $s->whereKey($child->id))
            ->with(['category', 'files'])
            ->orderByDesc('date')->orderByDesc('id')
            ->first();

        if ($portfolio === null) {
            return null;
        }

        $image = $portfolio->files->first(fn ($f) => $f->isImage());

        return [
            'title' => $portfolio->title,
            'category' => $portfolio->category->name,
            'date' => $portfolio->date->toDateString(),
            'thumbnail' => $image ? $this->urls->thumbnailUrl($image) : null,
        ];
    }

    /**
     * @return array{public_id: string, type: string, status: string, created_at: string}|null
     */
    private function latestCondition(Student $child, User $guardian): ?array
    {
        $condition = ChildCondition::query()
            ->where('student_id', $child->id)
            ->where('guardian_user_id', $guardian->id)
            ->latest('id')
            ->first();

        return $condition ? [
            'public_id' => $condition->public_id,
            'type' => $condition->type->label(),
            'status' => $condition->status->label(),
            'created_at' => $condition->created_at->toIso8601String(),
        ] : null;
    }

    /**
     * @return list<array{public_id: string, title: string, pinned: bool, publish_at: string|null}>
     */
    private function latestAnnouncements(User $guardian): array
    {
        return $this->announcements->scopeFor(Announcement::query(), $guardian, Role::Guardian)
            ->feedOrder()
            ->limit(3)
            ->get()
            ->map(fn (Announcement $a) => [
                'public_id' => $a->public_id,
                'title' => $a->title,
                'pinned' => $a->pinned,
                'publish_at' => $a->publish_at?->toIso8601String(),
            ])->values()->all();
    }
}
