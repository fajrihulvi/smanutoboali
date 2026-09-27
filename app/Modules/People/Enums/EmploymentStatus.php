<?php

namespace App\Modules\People\Enums;

enum EmploymentStatus: string
{
    case Pns = 'PNS';
    case Pppk = 'PPPK';
    case Gty = 'GTY';
    case Gtt = 'GTT';
    case Honorer = 'HONORER';

    public function label(): string
    {
        return match ($this) {
            self::Pns => 'PNS',
            self::Pppk => 'PPPK',
            self::Gty => 'Guru Tetap Yayasan',
            self::Gtt => 'Guru Tidak Tetap',
            self::Honorer => 'Honorer',
        };
    }
}
