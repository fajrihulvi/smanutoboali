<?php

namespace App\Modules\School\Enums;

/**
 * Jenjang sekolah — menentukan rentang tingkat rombel.
 */
enum SchoolLevel: string
{
    case Sd = 'SD';
    case Smp = 'SMP';
    case Sma = 'SMA';
    case Smk = 'SMK';

    public function label(): string
    {
        return match ($this) {
            self::Sd => 'SD / MI',
            self::Smp => 'SMP / MTs',
            self::Sma => 'SMA / MA',
            self::Smk => 'SMK',
        };
    }

    /**
     * @return list<int>
     */
    public function gradeRange(): array
    {
        return match ($this) {
            self::Sd => range(1, 6),
            self::Smp => range(7, 9),
            self::Sma, self::Smk => range(10, 12),
        };
    }

    /**
     * Label tingkat: SD "1"…"6", selainnya angka Romawi ("VII", "X").
     */
    public function gradeLabel(int $grade): string
    {
        if ($this === self::Sd) {
            return (string) $grade;
        }

        return [7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'][$grade] ?? (string) $grade;
    }
}
