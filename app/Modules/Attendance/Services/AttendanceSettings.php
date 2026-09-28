<?php

namespace App\Modules\Attendance\Services;

use App\Modules\Attendance\Enums\AttendanceMode;
use App\Modules\Core\Enums\SettingKey;
use App\Modules\Core\Services\SettingsService;
use Carbon\CarbonImmutable;

/**
 * Pengaturan presensi bertipe (mode & batas edit).
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
}
