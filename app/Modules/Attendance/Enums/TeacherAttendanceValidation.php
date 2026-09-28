<?php

namespace App\Modules\Attendance\Enums;

enum TeacherAttendanceValidation: string
{
    case None = 'NONAKTIF';
    case Ip = 'IP';
    case Gps = 'GPS';

    public function label(): string
    {
        return match ($this) {
            self::None => 'Nonaktif (presensi dari mana saja)',
            self::Ip => 'Jaringan sekolah (daftar IP)',
            self::Gps => 'Lokasi GPS (radius dari sekolah)',
        };
    }
}
