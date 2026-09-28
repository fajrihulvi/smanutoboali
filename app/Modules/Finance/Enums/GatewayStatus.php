<?php

namespace App\Modules\Finance\Enums;

/**
 * Status satu percobaan pembayaran online.
 */
enum GatewayStatus: string
{
    case Created = 'CREATED';
    case Pending = 'PENDING';
    case Settled = 'SETTLED';
    case Failed = 'FAILED';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Belum dibayar',
            self::Pending => 'Menunggu pembayaran',
            self::Settled => 'Berhasil',
            self::Failed => 'Gagal/kedaluwarsa',
        };
    }

    public function isFinal(): bool
    {
        return $this === self::Settled || $this === self::Failed;
    }

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Created => $next !== self::Created,
            self::Pending => in_array($next, [self::Settled, self::Failed, self::Pending], true),
            // Midtrans bisa mengirim settlement setelah expire untuk VA yang tetap dibayar.
            self::Failed => $next === self::Settled,
            self::Settled => false,
        };
    }

    /**
     * Peta transaction_status Midtrans → status internal (null = abaikan).
     */
    public static function fromMidtrans(string $transactionStatus, ?string $fraudStatus): ?self
    {
        return match ($transactionStatus) {
            'capture' => $fraudStatus === null || $fraudStatus === 'accept' ? self::Settled : self::Pending,
            'settlement' => self::Settled,
            'pending' => self::Pending,
            'deny', 'cancel', 'expire', 'failure' => self::Failed,
            default => null,
        };
    }
}
