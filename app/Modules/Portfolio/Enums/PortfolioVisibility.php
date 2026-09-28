<?php

namespace App\Modules\Portfolio\Enums;

enum PortfolioVisibility: string
{
    case Draft = 'DRAFT';
    case Published = 'PUBLISHED';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Published => 'Terbit',
        };
    }
}
