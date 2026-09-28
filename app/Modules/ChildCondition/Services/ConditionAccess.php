<?php

namespace App\Modules\ChildCondition\Services;

use App\Models\User;
use App\Modules\Academic\Enums\EnrollmentStatus;
use App\Modules\Academic\Models\ClassEnrollment;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Access\Enums\Permission;
use App\Modules\ChildCondition\Models\ChildCondition;
use App\Modules\Core\Enums\Role;
use App\Modules\People\Models\Teacher;
use App\Support\ActivePeriod;
use Illuminate\Database\Eloquent\Builder;

/**
 * Siapa boleh melihat/menanggapi laporan kondisi anak (data sensitif):
 *  - orang tua PELAPOR
 *  - guru tujuan & wali kelas anak (rombel aktif) — juga yang boleh menanggapi
 *  - kepala sekolah (hanya-baca)
 */
class ConditionAccess
{
    /** @var array<int, Teacher|null> */
    private array $teachers = [];

    public function __construct(private ActivePeriod $period) {}

    public function canView(User $user, ChildCondition $condition): bool
    {
        if ($user->hasRoleEnum(Role::Principal)) {
            return true;
        }

        if ($condition->guardian_user_id === $user->getKey() && $user->hasRoleEnum(Role::Guardian)) {
            return true;
        }

        return $this->isHandler($user, $condition);
    }

    public function canRespond(User $user, ChildCondition $condition): bool
    {
        return $user->can(Permission::ChildConditionRespond->value) && $this->isHandler($user, $condition);
    }

    /**
     * Guru tujuan atau wali kelas anak di tahun ajaran aktif.
     */
    public function isHandler(User $user, ChildCondition $condition): bool
    {
        $teacher = $this->teacherFor($user);

        if ($teacher === null) {
            return false;
        }

        return $condition->target_teacher_id === $teacher->id
            || $this->homeroomStudents($teacher)->where('student_id', $condition->student_id)->exists();
    }

    public function teacherFor(User $user): ?Teacher
    {
        if (! $user->hasRoleEnum(Role::Teacher)) {
            return null;
        }

        return array_key_exists($user->id, $this->teachers)
            ? $this->teachers[$user->id]
            : $this->teachers[$user->id] = Teacher::activeForUser($user);
    }

    /**
     * Laporan yang ditangani guru: ditujukan kepadanya atau untuk siswa perwaliannya.
     *
     * @param  Builder<ChildCondition>  $query
     * @return Builder<ChildCondition>
     */
    public function scopeHandledBy(Builder $query, Teacher $teacher): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->where('target_teacher_id', $teacher->id)
            ->orWhereIn('student_id', $this->homeroomStudents($teacher)->select('student_id')));
    }

    /**
     * @return Builder<ClassEnrollment>
     */
    private function homeroomStudents(Teacher $teacher): Builder
    {
        $yearId = $this->period->academicYearId();

        return ClassEnrollment::query()
            ->where('academic_year_id', $yearId)
            ->where('status', EnrollmentStatus::Active)
            ->whereIn('classroom_id', Classroom::query()
                ->select('id')
                ->where('academic_year_id', $yearId)
                ->where('homeroom_teacher_id', $teacher->id));
    }
}
