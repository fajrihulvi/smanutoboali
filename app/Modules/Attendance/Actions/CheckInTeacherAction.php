<?php

namespace App\Modules\Attendance\Actions;

use App\Models\User;
use App\Modules\Attendance\Enums\TeacherAttendanceStatus;
use App\Modules\Attendance\Models\TeacherAttendance;
use App\Modules\Attendance\Services\AttendanceSettings;
use App\Modules\People\Models\Teacher;
use App\Support\ActivePeriod;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Presensi masuk mandiri guru (hari ini, sekali). HADIR atau TERLAMBAT sesuai jam batas.
 * Lokasi sudah divalidasi pemanggil (TeacherLocationGuard); $meta disimpan untuk audit.
 */
class CheckInTeacherAction
{
    public function __construct(
        private AttendanceSettings $settings,
        private ActivePeriod $period,
    ) {}

    /**
     * @param  array<string, mixed>  $meta
     *
     * @throws ValidationException
     */
    public function handle(Teacher $teacher, User $actor, array $meta): TeacherAttendance
    {
        $now = CarbonImmutable::now();
        $today = $now->startOfDay();

        if ($today->isSunday()) {
            throw ValidationException::withMessages(['attendance' => 'Hari Minggu tidak ada presensi.']);
        }

        return DB::transaction(function () use ($teacher, $actor, $meta, $now, $today) {
            $existing = TeacherAttendance::query()
                ->where('teacher_id', $teacher->id)
                ->whereDate('date', $today->toDateString())
                ->lockForUpdate()
                ->first();

            if ($existing?->check_in_at !== null) {
                throw ValidationException::withMessages(['attendance' => 'Anda sudah presensi masuk hari ini.']);
            }

            if ($existing !== null && ! $existing->status->isOnSite()) {
                throw ValidationException::withMessages([
                    'attendance' => "Hari ini Anda tercatat {$existing->status->label()}. Hubungi operator bila keliru.",
                ]);
            }

            $status = $now->isAfter($this->settings->teacherLateAfter($today))
                ? TeacherAttendanceStatus::Late
                : TeacherAttendanceStatus::Present;

            return TeacherAttendance::query()->create([
                'academic_year_id' => $this->period->academicYearId(),
                'teacher_id' => $teacher->id,
                'date' => $today->toDateString(),
                'check_in_at' => $now,
                'status' => $status,
                'source' => TeacherAttendance::SOURCE_SELF,
                'check_in_meta' => $meta,
                'recorded_by' => $actor->id,
            ]);
        });
    }
}
