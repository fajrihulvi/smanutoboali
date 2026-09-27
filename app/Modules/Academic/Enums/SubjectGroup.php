<?php

namespace App\Modules\Academic\Enums;

/**
 * Kelompok mata pelajaran (struktur Kurikulum Merdeka).
 */
enum SubjectGroup: string
{
    case General = 'UMUM';
    case Elective = 'PILIHAN';
    case LocalContent = 'MUATAN_LOKAL';

    public function label(): string
    {
        return match ($this) {
            self::General => 'Umum',
            self::Elective => 'Pilihan',
            self::LocalContent => 'Muatan Lokal',
        };
    }
}
