<?php

namespace App\Modules\Finance\Enums;

enum DiscountKind: string
{
    case Percent = 'PERSEN';
    case Amount = 'NOMINAL';

    public function label(): string
    {
        return match ($this) {
            self::Percent => 'Persen',
            self::Amount => 'Nominal',
        };
    }

    /**
     * Potongan (rupiah) untuk nominal dasar; tidak pernah melebihi dasar.
     */
    public function apply(int $base, int $value): int
    {
        $cut = match ($this) {
            self::Percent => intdiv($base * min($value, 100), 100),
            self::Amount => $value,
        };

        return min($base, max(0, $cut));
    }
}
