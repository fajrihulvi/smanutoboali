<?php

namespace App\Modules\Finance\Enums;

enum InvoiceStatus: string
{
    case Unpaid = 'UNPAID';
    case Pending = 'PENDING';
    case Paid = 'PAID';
    case Failed = 'FAILED';
    case Cancelled = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Belum lunas',
            self::Pending => 'Menunggu verifikasi',
            self::Paid => 'Lunas',
            self::Failed => 'Gagal',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Unpaid => in_array($next, [self::Pending, self::Paid, self::Failed, self::Cancelled, self::Unpaid], true),
            self::Pending => in_array($next, [self::Unpaid, self::Paid, self::Pending], true),
            self::Failed => in_array($next, [self::Unpaid, self::Pending, self::Paid, self::Cancelled], true),
            self::Paid, self::Cancelled => false,
        };
    }

    /**
     * Masih bisa menerima pembayaran.
     */
    public function isPayable(): bool
    {
        return in_array($this, [self::Unpaid, self::Pending, self::Failed], true);
    }

    /**
     * @return array{value: string, label: string}
     */
    public function toOption(): array
    {
        return ['value' => $this->value, 'label' => $this->label()];
    }
}
