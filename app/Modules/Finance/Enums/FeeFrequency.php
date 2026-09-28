<?php

namespace App\Modules\Finance\Enums;

enum FeeFrequency: string
{
    case Monthly = 'BULANAN';
    case Once = 'SEKALI';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Bulanan',
            self::Once => 'Sekali bayar',
        };
    }
}
