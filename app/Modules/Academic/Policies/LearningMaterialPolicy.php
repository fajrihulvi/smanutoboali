<?php

namespace App\Modules\Academic\Policies;

use App\Models\User;
use App\Modules\Academic\Enums\EnrollmentStatus;
use App\Modules\Academic\Models\LearningMaterial;
use App\Modules\Academic\Models\TeachingAssignment;
use App\Modules\Access\Enums\Permission;
use App\Modules\Core\Enums\Role;
use App\Modules\People\Models\Student;
use App\Modules\People\Models\Teacher;
use Illuminate\Database\Eloquent\Builder;

/**
 * Materi dikelola guru pengampu. Dibaca oleh: guru pengampu, operator, kepala sekolah,
 * dan orang tua yang anaknya aktif di rombel materi tersebut. Dipakai juga oleh FilePolicy
 * untuk mengunduh file materi.
 */
class LearningMaterialPolicy
{
    public function view(User $user, LearningMaterial $material): bool
    {
        if ($user->hasRoleEnum(Role::Operator) || $user->hasRoleEnum(Role::Principal)) {
            return true;
        }

        $assignment = $material->teachingAssignment;

        if ($this->teaches($user, $assignment)) {
            return true;
        }

        return $user->hasRoleEnum(Role::Guardian)
            && Student::query()
                ->visibleTo($user, Role::Guardian)
                ->whereHas('enrollments', fn (Builder $e) => $e
                    ->where('classroom_id', $assignment->classroom_id)
                    ->where('status', EnrollmentStatus::Active))
                ->exists();
    }

    /**
     * Guru dengan permission akademik yang punya minimal satu penugasan.
     */
    public function create(User $user): bool
    {
        return $user->can(Permission::AcademicManage->value) && Teacher::activeForUser($user) !== null;
    }

    public function update(User $user, LearningMaterial $material): bool
    {
        return $user->can(Permission::AcademicManage->value) && $this->teaches($user, $material->teachingAssignment);
    }

    public function delete(User $user, LearningMaterial $material): bool
    {
        return $this->update($user, $material);
    }

    /**
     * Guru pengampu penugasan ini (untuk memilih penugasan saat membuat materi).
     */
    public function teaches(User $user, TeachingAssignment $assignment): bool
    {
        $teacher = Teacher::activeForUser($user);

        return $teacher !== null && $assignment->teacher_id === $teacher->getKey();
    }
}
