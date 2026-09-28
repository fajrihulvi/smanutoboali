<?php

namespace App\Modules\Activity\Services;

use App\Models\User;
use App\Modules\Academic\Enums\EnrollmentStatus;
use App\Modules\Academic\Models\ClassEnrollment;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Activity\Enums\ActivityStatus;
use App\Modules\Activity\Enums\ActivityType;
use App\Modules\Activity\Models\Activity;
use App\Modules\Core\Enums\Role;
use App\Modules\People\Models\Student;
use App\Modules\People\Models\Teacher;
use Illuminate\Database\Eloquent\Builder;

/**
 * Aturan tunggal "kegiatan mana yang terlihat" untuk feed, kalender, dan policy.
 */
class ActivityVisibility
{
    /**
     * Kegiatan terbit yang menyangkut siswa-siswa ini: SEKOLAH, rombel aktif mereka, atau mereka sebagai peserta.
     *
     * @param  list<int>  $studentIds
     * @return Builder<Activity>
     */
    public function publishedForStudents(array $studentIds): Builder
    {
        $classroomIds = ClassEnrollment::query()
            ->whereIn('student_id', $studentIds)
            ->where('status', EnrollmentStatus::Active)
            ->pluck('classroom_id')->unique()->values()->all();

        return Activity::query()
            ->where('status', ActivityStatus::Published)
            ->where(fn (Builder $q) => $q
                ->where('type', ActivityType::School)
                ->orWhereHas('participants', fn (Builder $p) => $p
                    ->whereIn('classroom_id', $classroomIds)
                    ->orWhereIn('student_id', $studentIds)));
    }

    /**
     * Kegiatan yang terlihat oleh user sesuai role aktifnya.
     *
     * @return Builder<Activity>
     */
    public function forUser(User $user, Role $role): Builder
    {
        return match ($role) {
            Role::Operator => Activity::query(),
            Role::Principal => Activity::query()->where(fn (Builder $q) => $q
                ->where('status', ActivityStatus::Published)
                ->orWhere('created_by', $user->id)),
            Role::Guardian => $this->publishedForStudents(Student::query()->visibleTo($user, Role::Guardian)->pluck('students.id')->all()),
            Role::Teacher => $this->forTeacher($user),
            default => Activity::query()->whereRaw('1 = 0'),
        };
    }

    /**
     * Rombel tahun aktif yang boleh dipilih guru (diajar/diwalikan); operator: semua.
     *
     * @return Builder<Classroom>
     */
    public function classroomsFor(User $user, Role $role, int $yearId): Builder
    {
        $query = Classroom::query()->where('academic_year_id', $yearId);

        if ($role === Role::Operator) {
            return $query;
        }

        $teacher = Teacher::activeForUser($user);

        if ($teacher === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(fn (Builder $q) => $q
            ->where('homeroom_teacher_id', $teacher->id)
            ->orWhereHas('teachingAssignments', fn (Builder $a) => $a->where('teacher_id', $teacher->id)));
    }

    /**
     * @return Builder<Activity>
     */
    private function forTeacher(User $user): Builder
    {
        $studentIds = Student::query()->visibleTo($user, Role::Teacher)->pluck('students.id')->all();

        return Activity::query()->where(fn (Builder $q) => $q
            ->where('created_by', $user->id)
            ->orWhereIn('id', $this->publishedForStudents($studentIds)->select('activities.id'))
            ->orWhere(fn (Builder $s) => $s->where('status', ActivityStatus::Published)->where('type', ActivityType::School)));
    }
}
