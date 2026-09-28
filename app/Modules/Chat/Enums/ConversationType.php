<?php

namespace App\Modules\Chat\Enums;

enum ConversationType: string
{
    case Direct = 'DIRECT';

    public function label(): string
    {
        return match ($this) {
            self::Direct => 'Pesan langsung',
        };
    }
}
