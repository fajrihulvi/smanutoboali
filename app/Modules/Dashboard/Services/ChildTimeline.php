<?php

namespace App\Modules\Dashboard\Services;

use App\Models\User;
use App\Modules\Activity\Models\Activity;
use App\Modules\Activity\Services\ActivityVisibility;
use App\Modules\Assessment\Models\Grade;
use App\Modules\Attendance\Enums\AttendanceStatus;
use App\Modules\Attendance\Models\AttendanceRecord;
use App\Modules\ChildCondition\Models\ChildCondition;
use App\Modules\People\Models\Student;
use App\Modules\Portfolio\Enums\PortfolioVisibility;
use App\Modules\Portfolio\Models\Portfolio;
use App\Modules\ReportCard\Enums\ReportCardStatus;
use App\Modules\ReportCard\Models\ReportCard;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * Linimasa anak (portal orang tua): presensi selain hadir, nilai terbit, kegiatan, portofolio,
 * raport, dan laporan kondisi anak milik orang tua ini — satu bulan, terbaru di atas.
 *
 * @phpstan-type Entry array{key: string, kind: string, at: string, title: string, detail: string|null, url: string|null, tone: string}
 */
class ChildTimeline
{
    public function __construct(private ActivityVisibility $activities) {}

    /**
     * @return list<Entry>
     */
    public function month(Student $child, User $guardian, CarbonImmutable $month): array
    {
        $from = $month->startOfMonth();
        $to = $month->endOfMonth();

        $entries = [
            ...$this->attendance($child, $from, $to),
            ...$this->grades($child, $from, $to),
            ...$this->activityEntries($child, $from, $to),
            ...$this->portfolios($child, $from, $to),
            ...$this->reportCards($child, $from, $to),
            ...$this->conditions($child, $guardian, $from, $to),
        ];

        usort($entries, fn (array $a, array $b) => strcmp($b['at'], $a['at']) ?: strcmp($a['key'], $b['key']));

        return $entries;
    }

    /**
     * @return list<Entry>
     */
    private function attendance(Student $child, CarbonImmutable $from, CarbonImmutable $to): array
    {
        return AttendanceRecord::query()
            ->where('student_id', $child->id)
            ->where('status', '!=', AttendanceStatus::Present)
            ->whereHas('session', fn (Builder $s) => $s->whereDate('date', '>=', $from->toDateString())->whereDate('date', '<=', $to->toDateString()))
            ->with('session.schedule.teachingAssignment.subject')
            ->get()
            ->map(fn (AttendanceRecord $r) => [
                'key' => 'attendance:'.$r->id,
                'kind' => 'attendance',
                'at' => $r->session->date->setTime(7, 0)->toIso8601String(),
                'title' => 'Presensi: '.$r->status->label(),
                'detail' => trim(implode(' · ', array_filter([
                    $r->session->schedule?->teachingAssignment?->subject?->name,
                    $r->note,
                ]))) ?: null,
                'url' => '/orang-tua/presensi',
                'tone' => $r->status === AttendanceStatus::Absent ? 'danger' : 'warning',
            ])->values()->all();
    }

    /**
     * Nilai terbit; komponen bernama sama yang terbit di hari yang sama (mis. "UH 1" untuk
     * semua mapel) digabung menjadi satu entri agar linimasa tidak penuh.
     *
     * @return list<Entry>
     */
    private function grades(Student $child, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $grades = Grade::query()
            ->where('student_id', $child->id)
            ->whereHas('component', fn (Builder $c) => $c->whereBetween('published_at', [$from, $to]))
            ->with('component.teachingAssignment.subject')
            ->get();

        $score = fn (Grade $g) => rtrim(rtrim(number_format($g->value(), 2, ',', ''), '0'), ',');

        return $grades
            ->groupBy(fn (Grade $g) => ($g->component->published_at?->toDateString() ?? '').'|'.$g->component->name)
            ->map(function ($group) use ($score, $from) {
                /** @var Grade $first */
                $first = $group->first();
                $at = $group->map(fn (Grade $g) => $g->component->published_at)->filter()->max() ?? $from;
                $single = $group->count() === 1;

                return [
                    'key' => 'grade:'.$first->id,
                    'kind' => 'grade',
                    'at' => $at->toIso8601String(),
                    'title' => $single
                        ? 'Nilai '.$first->component->name.' · '.$first->component->teachingAssignment->subject->name
                        : 'Nilai '.$first->component->name.' terbit ('.$group->count().' mapel)',
                    'detail' => $single
                        ? 'Nilai '.$score($first)
                        : $group->sortBy(fn (Grade $g) => $g->component->teachingAssignment->subject->name)
                            ->map(fn (Grade $g) => $g->component->teachingAssignment->subject->name.' '.$score($g))
                            ->implode(' · '),
                    'url' => '/orang-tua/nilai',
                    'tone' => 'brand',
                ];
            })->values()->all();
    }

    /**
     * @return list<Entry>
     */
    private function activityEntries(Student $child, CarbonImmutable $from, CarbonImmutable $to): array
    {
        return $this->activities->publishedForStudents([$child->id])
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $to->toDateString())
            ->get()
            ->map(fn (Activity $a) => [
                'key' => 'activity:'.$a->id,
                'kind' => 'activity',
                'at' => $a->date->setTimeFromTimeString($a->start_time ?? '08:00')->toIso8601String(),
                'title' => $a->title,
                'detail' => $a->location,
                'url' => '/orang-tua/kegiatan',
                'tone' => 'info',
            ])->values()->all();
    }

    /**
     * @return list<Entry>
     */
    private function portfolios(Student $child, CarbonImmutable $from, CarbonImmutable $to): array
    {
        return Portfolio::query()
            ->where('visibility', PortfolioVisibility::Published)
            ->whereHas('students', fn (Builder $s) => $s->whereKey($child->id))
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $to->toDateString())
            ->with('category')
            ->get()
            ->map(fn (Portfolio $p) => [
                'key' => 'portfolio:'.$p->id,
                'kind' => 'portfolio',
                'at' => $p->date->setTime(9, 0)->toIso8601String(),
                'title' => $p->title,
                'detail' => $p->category->name,
                'url' => '/orang-tua/portofolio',
                'tone' => 'success',
            ])->values()->all();
    }

    /**
     * @return list<Entry>
     */
    private function reportCards(Student $child, CarbonImmutable $from, CarbonImmutable $to): array
    {
        return ReportCard::query()
            ->where('student_id', $child->id)
            ->where('status', ReportCardStatus::Published)
            ->whereBetween('published_at', [$from, $to])
            ->with('semester.academicYear')
            ->get()
            ->map(fn (ReportCard $c) => [
                'key' => 'report_card:'.$c->id,
                'kind' => 'report_card',
                'at' => $c->published_at?->toIso8601String() ?? $from->toIso8601String(),
                'title' => 'Raport terbit',
                'detail' => 'Semester '.$c->semester->type->label().' '.$c->semester->academicYear->name,
                'url' => '/orang-tua/raport/'.$c->public_id,
                'tone' => 'brand',
            ])->values()->all();
    }

    /**
     * Hanya laporan milik orang tua yang sedang login (data sensitif).
     *
     * @return list<Entry>
     */
    private function conditions(Student $child, User $guardian, CarbonImmutable $from, CarbonImmutable $to): array
    {
        return ChildCondition::query()
            ->where('student_id', $child->id)
            ->where('guardian_user_id', $guardian->id)
            ->whereBetween('created_at', [$from, $to])
            ->get()
            ->map(fn (ChildCondition $c) => [
                'key' => 'condition:'.$c->id,
                'kind' => 'condition',
                'at' => $c->created_at->toIso8601String(),
                'title' => 'Laporan kondisi: '.$c->type->label(),
                'detail' => $c->status->label(),
                'url' => '/orang-tua/kondisi-anak/'.$c->public_id,
                'tone' => 'warning',
            ])->values()->all();
    }
}
