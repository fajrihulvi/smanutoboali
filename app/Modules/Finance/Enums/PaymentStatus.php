<?php

namespace App\Modules\Finance\Enums;

enum PaymentStatus: string
{
    case Pending = 'PENDING';
    case Confirmed = 'CONFIRMED';
    case Rejected = 'REJECTED';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu verifikasi',
            self::Confirmed => 'Terkonfirmasi',
            self::Rejected => 'Ditolak',
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return $this === self::Pending && $next !== self::Pending;
    }
}
