<?php

namespace App\Modules\Announcement\Services;

use App\Models\User;
use App\Modules\Academic\Enums\EnrollmentStatus;
use App\Modules\Academic\Models\ClassEnrollment;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\TeachingAssignment;
use App\Modules\Access\Enums\Permission;
use App\Modules\Announcement\Enums\AnnouncementAudience;
use App\Modules\Announcement\Models\Announcement;
use App\Modules\Core\Enums\Role;
use App\Modules\People\Models\Guardian;
use App\Modules\People\Models\Teacher;
use App\Support\ActivePeriod;
use Illuminate\Database\Eloquent\Builder;

/**
 * Siapa penerima pengumuman:
 *  - SEMUA  : semua user aktif ber-role kepsek/guru/operator/orang tua
 *  - PERAN  : role terpilih
 *  - ROMBEL : orang tua siswa aktif rombel + wali kelas & guru mapel rombel
 * Pengelola (announcement.manage) selalu bisa melihat semuanya.
 */
class AnnouncementAudienceResolver
{
    /** Role yang bisa menjadi audiens pengumuman. */
    public const READER_ROLES = [Role::Principal, Role::Teacher, Role::Operator, Role::Guardian];

    public function __construct(private ActivePeriod $period) {}

    /**
     * Pengumuman TERBIT yang ditujukan ke user sebagai role tertentu.
     *
     * @param  Builder<Announcement>  $query
     * @return Builder<Announcement>
     */
    public function scopeFor(Builder $query, User $user, Role $as): Builder
    {
        $classroomIds = $this->classroomIdsFor($user, $as);

        return $query->published()->where(fn (Builder $q) => $q
            ->where('audience', AnnouncementAudience::All)
            ->orWhere(fn (Builder $r) => $r->where('audience', AnnouncementAudience::Roles)->whereJsonContains('roles', $as->value))
            ->orWhere(fn (Builder $c) => $c->where('audience', AnnouncementAudience::Classrooms)
                ->whereHas('classrooms', fn (Builder $k) => $k->whereIn('classrooms.id', $classroomIds))));
    }

    public function canView(User $user, Announcement $announcement): bool
    {
        if ($user->can(Permission::AnnouncementManage->value)) {
            return true;
        }

        foreach ($user->coreRoles() as $role) {
            if (in_array($role, self::READER_ROLES, true)
                && $this->scopeFor(Announcement::query(), $user, $role)->whereKey($announcement->id)->exists()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Penerima notifikasi (user aktif, tanpa pembuat).
     *
     * @return Builder<User>
     */
    public function recipients(Announcement $announcement): Builder
    {
        $query = User::query()->where('is_active', true)->when($announcement->created_by, fn (Builder $q, int $id) => $q->whereKeyNot($id));
        $roleNames = fn (array $roles) => array_map(fn (Role $r) => $r->value, $roles);

        return match ($announcement->audience) {
            AnnouncementAudience::All => $query->whereHas('roles', fn (Builder $r) => $r->whereIn('name', $roleNames(self::READER_ROLES))),
            AnnouncementAudience::Roles => $query->whereHas('roles', fn (Builder $r) => $r->whereIn('name', $roleNames($announcement->targetRoles()))),
            AnnouncementAudience::Classrooms => $this->classroomRecipients($query, $announcement),
        };
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    private function classroomRecipients(Builder $query, Announcement $announcement): Builder
    {
        $classroomIds = $announcement->classrooms()->pluck('classrooms.id')->all();

        $guardianUsers = Guardian::query()
            ->whereNotNull('user_id')
            ->whereHas('students.enrollments', fn (Builder $e) => $e
                ->whereIn('classroom_id', $classroomIds)
                ->where('status', EnrollmentStatus::Active))
            ->select('user_id');

        $teacherIds = TeachingAssignment::query()->whereIn('classroom_id', $classroomIds)->pluck('teacher_id')
            ->merge(Classroom::query()->whereIn('id', $classroomIds)->whereNotNull('homeroom_teacher_id')->pluck('homeroom_teacher_id'))
            ->unique()->all();
        $teacherUsers = Teacher::query()->whereIn('id', $teacherIds)->where('is_active', true)->whereNotNull('user_id')->select('user_id');

        return $query->where(fn (Builder $q) => $q
            ->where(fn (Builder $g) => $g->whereIn('id', $guardianUsers)->whereHas('roles', fn (Builder $r) => $r->where('name', Role::Guardian->value)))
            ->orWhere(fn (Builder $t) => $t->whereIn('id', $teacherUsers)->whereHas('roles', fn (Builder $r) => $r->where('name', Role::Teacher->value))));
    }

    /**
     * Rombel aktif milik user sebagai role: orang tua → rombel anak; guru → rombel yang diwalikan/diajar.
     *
     * @return list<int>
     */
    private function classroomIdsFor(User $user, Role $as): array
    {
        $yearId = $this->period->academicYearId() ?? 0;

        return match ($as) {
            Role::Guardian => ClassEnrollment::query()
                ->where('academic_year_id', $yearId)
                ->where('status', EnrollmentStatus::Active)
                ->whereHas('student.guardians', fn (Builder $g) => $g->where('guardians.user_id', $user->id))
                ->pluck('classroom_id')->unique()->values()->all(),
            Role::Teacher => ($teacher = Teacher::activeForUser($user))
                ? Classroom::query()
                    ->where('academic_year_id', $yearId)
                    ->where(fn (Builder $q) => $q
                        ->where('homeroom_teacher_id', $teacher->id)
                        ->orWhereHas('teachingAssignments', fn (Builder $a) => $a->where('teacher_id', $teacher->id)))
                    ->pluck('id')->all()
                : [],
            default => [],
        };
    }
}
