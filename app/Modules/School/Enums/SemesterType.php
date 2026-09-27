<?php

namespace App\Modules\School\Enums;

enum SemesterType: string
{
    case Ganjil = 'GANJIL';
    case Genap = 'GENAP';

    public function label(): string
    {
        return match ($this) {
            self::Ganjil => 'Ganjil',
            self::Genap => 'Genap',
        };
    }
}
