<?php

namespace App\Modules\Attendance\Services;

use App\Modules\Attendance\Enums\AttendanceMode;
use App\Modules\Attendance\Enums\TeacherAttendanceValidation;
use App\Modules\Core\Enums\SettingKey;
use App\Modules\Core\Services\SettingsService;
use Carbon\CarbonImmutable;

/**
 * Pengaturan presensi bertipe (siswa: mode & batas edit; guru: jam terlambat & validasi lokasi).
 */
class AttendanceSettings
{
    public function __construct(private SettingsService $settings) {}

    public function mode(): AttendanceMode
    {
        return AttendanceMode::tryFrom((string) $this->settings->get(SettingKey::AttendanceMode)) ?? AttendanceMode::Daily;
    }

    public function editDays(): int
    {
        return max(0, (int) $this->settings->get(SettingKey::AttendanceEditDays));
    }

    /**
     * Batas akhir (inklusif) guru boleh mengubah presensi tanggal ini.
     */
    public function editableUntil(CarbonImmutable $date): CarbonImmutable
    {
        return $date->addDays($this->editDays())->endOfDay();
    }

    /**
     * Jam batas terlambat guru pada tanggal tertentu.
     */
    public function teacherLateAfter(CarbonImmutable $date): CarbonImmutable
    {
        $time = (string) $this->settings->get(SettingKey::TeacherLateAfter);

        return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time) === 1
            ? $date->setTimeFromTimeString($time)
            : $date->setTime(7, 15);
    }

    public function teacherValidation(): TeacherAttendanceValidation
    {
        return TeacherAttendanceValidation::tryFrom((string) $this->settings->get(SettingKey::TeacherAttendanceValidation))
            ?? TeacherAttendanceValidation::None;
    }

    /**
     * @return list<string>
     */
    public function teacherAllowedIps(): array
    {
        $raw = (string) $this->settings->get(SettingKey::TeacherAttendanceIps);

        return array_values(array_filter(array_map('trim', preg_split('/[\s,]+/', $raw) ?: [])));
    }

    /**
     * @return array{lat: float, lng: float, radius: int}|null
     */
    public function schoolGeofence(): ?array
    {
        $lat = $this->settings->get(SettingKey::SchoolLatitude);
        $lng = $this->settings->get(SettingKey::SchoolLongitude);

        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return null;
        }

        return ['lat' => (float) $lat, 'lng' => (float) $lng, 'radius' => max(10, (int) $this->settings->get(SettingKey::TeacherAttendanceRadiusM))];
    }
}
