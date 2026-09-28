<?php

namespace App\Modules\Assessment\Enums;

enum ComponentType: string
{
    case Assignment = 'TUGAS';
    case DailyTest = 'UH';
    case MidTerm = 'PTS';
    case FinalTerm = 'PAS';
    case Practice = 'PRAKTIK';

    public function label(): string
    {
        return match ($this) {
            self::Assignment => 'Tugas',
            self::DailyTest => 'Ulangan Harian',
            self::MidTerm => 'PTS',
            self::FinalTerm => 'PAS',
            self::Practice => 'Praktik',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $t) => ['value' => $t->value, 'label' => $t->label()], self::cases());
    }
}
