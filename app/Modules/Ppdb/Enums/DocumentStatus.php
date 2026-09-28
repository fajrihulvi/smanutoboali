<?php

namespace App\Modules\Ppdb\Enums;

enum DocumentStatus: string
{
    case Pending = 'PENDING';
    case Valid = 'VALID';
    case Invalid = 'INVALID';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Belum diperiksa',
            self::Valid => 'Valid',
            self::Invalid => 'Tidak valid',
        };
    }
}
