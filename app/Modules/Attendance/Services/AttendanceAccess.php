<?php

namespace App\Modules\Attendance\Services;

use App\Models\User;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\Schedule;
use App\Modules\Access\Enums\Permission;
use App\Modules\Core\Enums\Role;
use App\Modules\People\Models\Teacher;
use Carbon\CarbonImmutable;

/**
 * Aturan siapa boleh mengisi, mengubah, dan melihat presensi rombel.
 *
 * - Operator (attendance.manage): semua rombel, kapan pun (koreksi).
 * - Guru (attendance.manage): mode harian → wali kelas / pengajar di rombel;
 *   mode per mapel → guru pengampu jadwal itu. Isi/ubah hanya sampai batas edit.
 * - Kepala sekolah: lihat semua. Orang tua: lewat halaman anak (Student::visibleTo).
 */
class AttendanceAccess
{
    public function __construct(private AttendanceSettings $settings) {}

    public function isOperator(User $user): bool
    {
        return $user->can(Permission::AttendanceManage->value) && $user->hasRoleEnum(Role::Operator);
    }

    public function canRecord(User $user, Classroom $classroom, ?Schedule $schedule): bool
    {
        if ($this->isOperator($user)) {
            return true;
        }

        if (! $user->can(Permission::AttendanceManage->value)) {
            return false;
        }

        $teacher = Teacher::activeForUser($user);

        if ($teacher === null) {
            return false;
        }

        return $schedule !== null
            ? $schedule->teachingAssignment->teacher_id === $teacher->id
            : $classroom->isTaughtBy($teacher);
    }

    /**
     * Guru hanya boleh mengisi/mengubah presensi sampai batas edit setelah tanggalnya
     * (mencegah pengisian mundur). Operator selalu boleh.
     */
    public function withinEditWindow(User $user, CarbonImmutable $date): bool
    {
        return $this->isOperator($user) || now()->lessThanOrEqualTo($this->settings->editableUntil($date));
    }

    public function canViewClassroom(User $user, Classroom $classroom): bool
    {
        if ($this->isOperator($user) || $user->hasRoleEnum(Role::Principal)) {
            return true;
        }

        $teacher = Teacher::activeForUser($user);

        return $teacher !== null && $classroom->isTaughtBy($teacher);
    }
}
