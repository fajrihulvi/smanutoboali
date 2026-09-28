<?php

namespace App\Modules\Finance\Enums;

enum PaymentMethod: string
{
    case Manual = 'MANUAL';
    case Gateway = 'GATEWAY';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::Gateway => 'Pembayaran online',
        };
    }
}
