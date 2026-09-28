<?php

namespace App\Modules\ChildCondition\Services;

use App\Models\User;
use App\Modules\ChildCondition\Enums\ConditionStatus;
use App\Modules\ChildCondition\Models\ChildCondition;
use App\Modules\People\Models\Student;
use App\Modules\People\Models\Teacher;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Query daftar laporan per role. Laporan baru (SUBMITTED) selalu di atas.
 */
class ConditionQuery
{
    public const PER_PAGE = 15;

    public function __construct(private ConditionAccess $access) {}

    /**
     * Laporan buatan orang tua ini (opsional per anak).
     *
     * @return LengthAwarePaginator<int, ChildCondition>
     */
    public function forGuardian(User $user, ?Student $child): LengthAwarePaginator
    {
        return $this->base()
            ->where('guardian_user_id', $user->id)
            ->when($child, fn (Builder $q) => $q->where('student_id', $child?->id))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * Inbox guru: aktif (belum selesai) atau selesai, cari nama siswa.
     *
     * @return LengthAwarePaginator<int, ChildCondition>
     */
    public function forTeacher(Teacher $teacher, bool $closed, ?string $search): LengthAwarePaginator
    {
        return $this->access->scopeHandledBy($this->filtered($closed, $search), $teacher)
            ->orderByRaw("CASE WHEN status = 'SUBMITTED' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * @return LengthAwarePaginator<int, ChildCondition>
     */
    public function forPrincipal(bool $closed, ?string $search): LengthAwarePaginator
    {
        return $this->filtered($closed, $search)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * Laporan yang berlaku hari ini untuk guru (widget dashboard).
     *
     * @return Collection<int, ChildCondition>
     */
    public function todayForTeacher(Teacher $teacher, CarbonImmutable $today, int $limit = 8): Collection
    {
        return $this->access->scopeHandledBy($this->base(), $teacher)
            ->covering($today)
            ->orderByRaw("CASE WHEN status = 'SUBMITTED' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function newCountForTeacher(Teacher $teacher): int
    {
        return $this->access->scopeHandledBy(ChildCondition::query(), $teacher)
            ->where('status', ConditionStatus::Submitted)
            ->count();
    }

    /**
     * @return Builder<ChildCondition>
     */
    private function filtered(bool $closed, ?string $search): Builder
    {
        return $this->base()
            ->when($closed, fn (Builder $q) => $q->where('status', ConditionStatus::Closed), fn (Builder $q) => $q->where('status', '!=', ConditionStatus::Closed))
            ->when(filled($search), fn (Builder $q) => $q->whereHas('student', fn (Builder $s) => $s->search($search)));
    }

    /**
     * @return Builder<ChildCondition>
     */
    private function base(): Builder
    {
        return ChildCondition::query()->with(['student', 'reporter', 'targetTeacher']);
    }
}
