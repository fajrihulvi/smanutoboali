<?php

namespace App\Modules\GuestBook\Services;

/**
 * Nomor WhatsApp Indonesia dalam bentuk 08xxxxxxxx (dari +62 8…, 62 8…, 8…, berspasi/bertanda hubung).
 */
final class WhatsappNumber
{
    public static function normalize(?string $value): string
    {
        $digits = preg_replace('/\D/', '', (string) $value) ?? '';

        return match (true) {
            str_starts_with($digits, '62') => '0'.substr($digits, 2),
            str_starts_with($digits, '8') => '0'.$digits,
            default => $digits,
        };
    }
}
