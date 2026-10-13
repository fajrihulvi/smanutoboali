<?php

namespace App\Modules\Academic\Enums;

/**
 * Kelompok mata pelajaran (struktur Kurikulum Merdeka + kepesantrenan).
 * Urutan case = urutan kelompok di raport.
 */
enum SubjectGroup: string
{
    case General = 'UMUM';
    case Elective = 'PILIHAN';
    case LocalContent = 'MUATAN_LOKAL';
    case Pesantren = 'KEPESANTRENAN';

    public function label(): string
    {
        return match ($this) {
            self::General => 'Umum',
            self::Elective => 'Pilihan',
            self::LocalContent => 'Muatan Lokal',
            self::Pesantren => 'Kepesantrenan',
        };
    }
}
