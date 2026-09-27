<?php

namespace App\Modules\People\Services;

use App\Modules\People\Enums\EmploymentStatus;
use App\Modules\People\Models\Guardian;
use App\Modules\People\Models\Teacher;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Query daftar guru & orang tua (paginasi server-side + filter).
 */
class PeopleDirectoryService
{
    public const PER_PAGE = 15;

    /**
     * @return array{search: string|null, status: string|null, employment: string|null, account: string|null}
     */
    public function teacherFilters(Request $request): array
    {
        return [
            'search' => $request->string('search')->trim()->value() ?: null,
            'status' => in_array($request->query('status'), ['active', 'inactive'], true) ? (string) $request->query('status') : null,
            'employment' => EmploymentStatus::tryFrom((string) $request->query('employment'))?->value,
            'account' => in_array($request->query('account'), ['yes', 'no'], true) ? (string) $request->query('account') : null,
        ];
    }

    /**
     * @param  array{search: string|null, status: string|null, employment: string|null, account: string|null}  $filters
     * @return LengthAwarePaginator<int, Teacher>
     */
    public function teachers(array $filters): LengthAwarePaginator
    {
        return Teacher::query()
            ->with(['user:id,email,is_active', 'photo'])
            ->search($filters['search'])
            ->when($filters['status'], fn (Builder $q, string $status) => $q->where('is_active', $status === 'active'))
            ->when($filters['employment'], fn (Builder $q, string $status) => $q->where('employment_status', $status))
            ->when($filters['account'], fn (Builder $q, string $account) => $account === 'yes' ? $q->whereNotNull('user_id') : $q->whereNull('user_id'))
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * @return array{search: string|null, account: string|null}
     */
    public function guardianFilters(Request $request): array
    {
        return [
            'search' => $request->string('search')->trim()->value() ?: null,
            'account' => in_array($request->query('account'), ['yes', 'no'], true) ? (string) $request->query('account') : null,
        ];
    }

    /**
     * @param  array{search: string|null, account: string|null}  $filters
     * @return LengthAwarePaginator<int, Guardian>
     */
    public function guardians(array $filters): LengthAwarePaginator
    {
        return Guardian::query()
            ->with('user:id,email,is_active')
            ->search($filters['search'])
            ->when($filters['account'], fn (Builder $q, string $account) => $account === 'yes' ? $q->whereNotNull('user_id') : $q->whereNull('user_id'))
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }
}
