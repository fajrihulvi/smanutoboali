<?php

namespace App\Modules\Attendance\Enums;

enum TeacherAttendanceStatus: string
{
    case Present = 'HADIR';
    case Late = 'TERLAMBAT';
    case Permit = 'IZIN';
    case Sick = 'SAKIT';
    case Duty = 'DINAS';
    case Absent = 'ALPA';

    public function label(): string
    {
        return match ($this) {
            self::Present => 'Hadir',
            self::Late => 'Terlambat',
            self::Permit => 'Izin',
            self::Sick => 'Sakit',
            self::Duty => 'Dinas luar',
            self::Absent => 'Alpa',
        };
    }

    /**
     * Dihitung hadir untuk persentase (dinas luar = bertugas).
     */
    public function countsAsPresent(): bool
    {
        return in_array($this, [self::Present, self::Late, self::Duty], true);
    }

    /**
     * Status yang berarti guru memang datang ke sekolah (punya jam masuk).
     */
    public function isOnSite(): bool
    {
        return $this === self::Present || $this === self::Late;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $s) => ['value' => $s->value, 'label' => $s->label()], self::cases());
    }
}
