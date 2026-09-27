<?php

namespace App\Modules\People\Services;

use App\Models\User;
use App\Modules\People\Enums\Gender;
use App\Modules\People\Enums\StudentStatus;
use App\Modules\People\Models\Student;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Query daftar siswa (paginasi server-side). Selalu lewat Student::visibleTo().
 */
class StudentDirectoryService
{
    public const PER_PAGE = 15;

    /**
     * @return array{search: string|null, status: string|null, gender: string|null}
     */
    public function filters(Request $request): array
    {
        return [
            'search' => $request->string('search')->trim()->value() ?: null,
            'status' => StudentStatus::tryFrom((string) $request->query('status'))?->value,
            'gender' => Gender::tryFrom((string) $request->query('gender'))?->value,
        ];
    }

    /**
     * @param  array{search: string|null, status: string|null, gender: string|null}  $filters
     * @return LengthAwarePaginator<int, Student>
     */
    public function paginate(User $viewer, array $filters): LengthAwarePaginator
    {
        return Student::query()
            ->visibleTo($viewer)
            ->with(['photo', 'guardians:id,public_id,name,user_id'])
            ->search($filters['search'])
            ->when($filters['status'], fn (Builder $q, string $status) => $q->where('status', $status))
            ->when($filters['gender'], fn (Builder $q, string $gender) => $q->where('gender', $gender))
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }
}
