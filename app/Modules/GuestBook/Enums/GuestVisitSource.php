<?php

namespace App\Modules\GuestBook\Enums;

/**
 * Asal catatan tamu: diisi sendiri lewat website atau dicatat operator.
 */
enum GuestVisitSource: string
{
    case Website = 'WEBSITE';
    case Operator = 'OPERATOR';

    public function label(): string
    {
        return match ($this) {
            self::Website => 'Diisi tamu',
            self::Operator => 'Dicatat operator',
        };
    }
}
