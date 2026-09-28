<?php

namespace App\Modules\Chat\Services;

use App\Models\User;
use App\Modules\Access\Enums\Permission;
use App\Modules\Core\Enums\Role;
use App\Modules\People\Models\Student;
use App\Modules\People\Models\Teacher;
use Illuminate\Database\Eloquent\Builder;

/**
 * Siapa boleh chat dengan siapa (VIBE_PLANNING S20). Dihitung per user (gabungan role):
 *
 *  - operator & kepala sekolah ↔ semua user aktif pengguna chat (dua arah)
 *  - guru ↔ guru (sesama staf)
 *  - orang tua ↔ guru yang menjadi wali kelas/mengajar anaknya (enrollment aktif, tahun ajaran aktif)
 *  - orang tua ↔ orang tua: tidak
 *
 * Konteks anak (student) hanya sah bila anak itu milik pihak orang tua dalam percakapan.
 */
class ChatEligibility
{
    /** @var array<int, bool> */
    private array $chatUsers = [];

    /** @var array<int, bool> */
    private array $activeTeachers = [];

    public function canUseChat(User $user): bool
    {
        return $this->chatUsers[$user->id] ??= $user->is_active
            && $user->can(Permission::ChatUse->value)
            && collect([Role::Principal, Role::Operator, Role::Teacher, Role::Guardian])
                ->contains(fn (Role $role) => $user->hasRoleEnum($role));
    }

    public function canChat(User $a, User $b, ?Student $student = null): bool
    {
        if ($a->is($b) || ! $this->canUseChat($a) || ! $this->canUseChat($b)) {
            return false;
        }

        if ($student !== null && ! $this->isGuardianOf($a, $student) && ! $this->isGuardianOf($b, $student)) {
            return false;
        }

        if ($this->isAdmin($a) || $this->isAdmin($b)) {
            return true;
        }

        if ($student === null && $this->isTeacher($a) && $this->isTeacher($b)) {
            return true;
        }

        return $this->guardianTeacherLinked($a, $b, $student) || $this->guardianTeacherLinked($b, $a, $student);
    }

    public function isAdmin(User $user): bool
    {
        return $user->hasRoleEnum(Role::Operator) || $user->hasRoleEnum(Role::Principal);
    }

    /**
     * Guru dengan profil guru aktif.
     */
    public function isTeacher(User $user): bool
    {
        return $this->activeTeachers[$user->id] ??= $user->hasRoleEnum(Role::Teacher) && Teacher::activeForUser($user) !== null;
    }

    public function isGuardianOf(User $user, Student $student): bool
    {
        return $user->hasRoleEnum(Role::Guardian)
            && $student->guardians()->where('guardians.user_id', $user->getKey())->exists();
    }

    /**
     * Guru mengajar/menjadi wali kelas salah satu anak (atau anak tertentu) orang tua ini.
     */
    private function guardianTeacherLinked(User $guardian, User $teacher, ?Student $student): bool
    {
        if (! $guardian->hasRoleEnum(Role::Guardian) || ! $teacher->hasRoleEnum(Role::Teacher)) {
            return false;
        }

        return Student::query()
            ->visibleTo($teacher, Role::Teacher)
            ->whereHas('guardians', fn (Builder $g) => $g->where('guardians.user_id', $guardian->getKey()))
            ->when($student, fn (Builder $q) => $q->whereKey($student?->getKey()))
            ->exists();
    }
}
