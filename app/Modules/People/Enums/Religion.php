<?php

namespace App\Modules\People\Enums;

enum Religion: string
{
    case Islam = 'ISLAM';
    case Kristen = 'KRISTEN';
    case Katolik = 'KATOLIK';
    case Hindu = 'HINDU';
    case Buddha = 'BUDDHA';
    case Konghucu = 'KONGHUCU';

    public function label(): string
    {
        return match ($this) {
            self::Islam => 'Islam',
            self::Kristen => 'Kristen',
            self::Katolik => 'Katolik',
            self::Hindu => 'Hindu',
            self::Buddha => 'Buddha',
            self::Konghucu => 'Konghucu',
        };
    }

    /**
     * Terima teks bebas dari Excel ("islam", "Kristen Protestan", "Budha").
     */
    public static function fromLoose(?string $value): ?self
    {
        $value = strtoupper(trim((string) $value));

        return match (true) {
            $value === '' => null,
            str_contains($value, 'ISLAM') => self::Islam,
            str_contains($value, 'KATOLIK') => self::Katolik,
            str_contains($value, 'KRISTEN') || str_contains($value, 'PROTESTAN') => self::Kristen,
            str_contains($value, 'HINDU') => self::Hindu,
            str_contains($value, 'BUD') => self::Buddha,
            str_contains($value, 'KONG') || str_contains($value, 'KHONG') => self::Konghucu,
            default => null,
        };
    }
}
