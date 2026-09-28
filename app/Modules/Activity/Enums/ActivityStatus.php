<?php

namespace App\Modules\Activity\Enums;

enum ActivityStatus: string
{
    case Draft = 'DRAF';
    case Published = 'TERBIT';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Published => 'Terbit',
        };
    }
}
