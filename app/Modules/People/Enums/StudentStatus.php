<?php

namespace App\Modules\People\Enums;

enum StudentStatus: string
{
    case Active = 'AKTIF';
    case Graduated = 'LULUS';
    case Transferred = 'PINDAH';
    case Dropped = 'KELUAR';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Graduated => 'Lulus',
            self::Transferred => 'Pindah',
            self::Dropped => 'Keluar',
        };
    }

    /**
     * AKTIF → LULUS/PINDAH/KELUAR; PINDAH/KELUAR → AKTIF (kembali/koreksi); LULUS final.
     */
    public function canTransitionTo(self $next): bool
    {
        if ($next === $this) {
            return true;
        }

        return match ($this) {
            self::Active => in_array($next, [self::Graduated, self::Transferred, self::Dropped], true),
            self::Transferred, self::Dropped => $next === self::Active,
            self::Graduated => false,
        };
    }
}
