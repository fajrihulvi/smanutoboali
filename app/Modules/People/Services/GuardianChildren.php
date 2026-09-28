<?php

namespace App\Modules\People\Services;

use App\Models\User;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Services\FileUrlGenerator;
use App\Modules\People\Models\Student;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\Eloquent\Collection;

/**
 * Anak milik orang tua yang login + anak aktif untuk StudentSwitcher global.
 * Anak aktif disimpan di session (S23); `?anak=<public_id>` tetap didukung dan ikut
 * memperbarui session. Dipakai semua halaman portal orang tua yang menampilkan data per anak.
 */
class GuardianChildren
{
    public const QUERY_KEY = 'anak';

    public const SESSION_KEY = 'guardian.active_child';

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
     * Anak terpilih: `?anak=` (disimpan ke session) → anak aktif di session → anak pertama.
     * public_id milik anak orang lain → 403.
     *
     * @param  Collection<int, Student>  $children
     */
    public function selected(Collection $children, ?string $publicId): ?Student
    {
        if ($publicId !== null && $publicId !== '') {
            $child = $children->firstWhere('public_id', $publicId);

            abort_if($child === null, 403);
            $this->remember($child);

            return $child;
        }

        $remembered = $this->session()?->get(self::SESSION_KEY);

        return ($remembered ? $children->firstWhere('public_id', $remembered) : null) ?? $children->first();
    }

    public function remember(Student $child): void
    {
        $this->session()?->put(self::SESSION_KEY, $child->public_id);
    }

    private function session(): ?Session
    {
        $request = request();

        return $request->hasSession() ? $request->session() : null;
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
