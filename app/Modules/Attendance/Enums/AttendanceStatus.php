<?php

namespace App\Modules\Attendance\Enums;

enum AttendanceStatus: string
{
    case Present = 'HADIR';
    case Sick = 'SAKIT';
    case Permit = 'IZIN';
    case Absent = 'ALPA';
    case Late = 'TERLAMBAT';

    public function label(): string
    {
        return match ($this) {
            self::Present => 'Hadir',
            self::Sick => 'Sakit',
            self::Permit => 'Izin',
            self::Absent => 'Alpa',
            self::Late => 'Terlambat',
        };
    }

    /**
     * Dihitung sebagai "hadir" untuk persentase kehadiran.
     */
    public function countsAsPresent(): bool
    {
        return $this === self::Present || $this === self::Late;
    }

    /**
     * Orang tua diberi tahu bila anak berstatus ini.
     */
    public function notifiesGuardian(): bool
    {
        return $this === self::Absent || $this === self::Late;
    }

    /**
     * Prioritas saat meringkas beberapa sesi dalam satu hari (makin besar makin menentukan).
     */
    public function severity(): int
    {
        return match ($this) {
            self::Present => 0,
            self::Late => 1,
            self::Permit => 2,
            self::Sick => 3,
            self::Absent => 4,
        };
    }

    public static function fromSeverity(int $severity): self
    {
        foreach (self::cases() as $status) {
            if ($status->severity() === $severity) {
                return $status;
            }
        }

        return self::Present;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $status) => ['value' => $status->value, 'label' => $status->label()], self::cases());
    }
}
