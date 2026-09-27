<?php

namespace App\Modules\Academic\Enums;

enum MaterialType: string
{
    case File = 'FILE';
    case Link = 'LINK';

    public function label(): string
    {
        return match ($this) {
            self::File => 'File',
            self::Link => 'Tautan',
        };
    }
}
