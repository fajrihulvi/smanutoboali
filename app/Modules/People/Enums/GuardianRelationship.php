<?php

namespace App\Modules\People\Enums;

enum GuardianRelationship: string
{
    case Father = 'AYAH';
    case Mother = 'IBU';
    case Guardian = 'WALI';

    public function label(): string
    {
        return match ($this) {
            self::Father => 'Ayah',
            self::Mother => 'Ibu',
            self::Guardian => 'Wali',
        };
    }
}
