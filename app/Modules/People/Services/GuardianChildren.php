<?php

namespace App\Modules\People\Services;

use App\Models\User;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Services\FileUrlGenerator;
use App\Modules\People\Models\Student;
use Illuminate\Database\Eloquent\Collection;

/**
 * Anak milik orang tua yang login + anak terpilih untuk StudentSwitcher (`?anak=<public_id>`).
 * Dipakai semua halaman portal orang tua yang menampilkan data per anak.
 */
class GuardianChildren
{
    public const QUERY_KEY = 'anak';

    public function __construct(private FileUrlGenerator $urls) {}

    /**
     * @return Collection<int, Student>
     */
    public function of(User $user): Collection
    {
        return Student::query()
            ->visibleTo($user, Role::Guardian)
            ->with('photo')
            ->orderBy('name')
            ->get();
    }

    /**
     * Anak terpilih; default anak pertama. public_id milik anak orang lain → 403.
     *
     * @param  Collection<int, Student>  $children
     */
    public function selected(Collection $children, ?string $publicId): ?Student
    {
        if ($publicId === null || $publicId === '') {
            return $children->first();
        }

        $child = $children->firstWhere('public_id', $publicId);

        abort_if($child === null, 403);

        return $child;
    }

    /**
     * Props untuk komponen StudentSwitcher. (Bukan `children`: nama itu milik React.)
     *
     * @param  Collection<int, Student>  $children
     * @return array{childOptions: list<array{public_id: string, name: string, photo_url: string|null}>, activeChild: string|null}
     */
    public function switcherProps(Collection $children, ?Student $selected): array
    {
        return [
            'childOptions' => $children->map(fn (Student $child) => [
                'public_id' => $child->public_id,
                'name' => $child->name,
                'photo_url' => $child->photo ? $this->urls->url($child->photo) : null,
            ])->values()->all(),
            'activeChild' => $selected?->public_id,
        ];
    }
}
