<?php

namespace App\Modules\Attendance\Enums;

enum AttendanceMode: string
{
    case Daily = 'HARIAN';
    case PerSubject = 'PER_MAPEL';

    public function label(): string
    {
        return match ($this) {
            self::Daily => 'Harian (sekali per hari per rombel)',
            self::PerSubject => 'Per mata pelajaran (setiap jadwal)',
        };
    }
}
