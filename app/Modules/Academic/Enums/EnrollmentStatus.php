<?php

namespace App\Modules\Academic\Enums;

enum EnrollmentStatus: string
{
    case Active = 'AKTIF';
    case Moved = 'PINDAH';
    case Completed = 'SELESAI';
    case Left = 'KELUAR';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Moved => 'Pindah rombel',
            self::Completed => 'Selesai',
            self::Left => 'Keluar',
        };
    }

    /**
     * Hanya enrollment aktif yang bisa berakhir; riwayat tidak diubah lagi.
     */
    public function canTransitionTo(self $next): bool
    {
        return $this === self::Active && $next !== self::Active;
    }
}
