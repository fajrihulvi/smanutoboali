<?php

namespace App\Modules\Access\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class UserDirectoryService
{
    public const PER_PAGE = 15;

    /**
     * @param  array{search?: string|null, role?: string|null, status?: string|null}  $filters
     * @return LengthAwarePaginator<int, User>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        return User::query()
            ->with('roles:id,name')
            ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                $query->where(fn (Builder $q) => $q
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"));
            })
            ->when($filters['role'] ?? null, fn (Builder $query, string $role) => $query->role($role))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('is_active', $status === 'active'))
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }
}
