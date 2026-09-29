<?php

namespace App\Modules\Cms\Enums;

enum ContentStatus: string
{
    case Draft = 'DRAFT';
    case Review = 'REVIEW';
    case Published = 'PUBLISHED';
    case Archived = 'ARCHIVED';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Review => 'Siap ditinjau',
            self::Published => 'Terbit',
            self::Archived => 'Arsip',
        };
    }

    public function canTransitionTo(self $next): bool
    {
        if ($next === $this) {
            return true;
        }

        return match ($this) {
            self::Draft => in_array($next, [self::Review, self::Published, self::Archived], true),
            self::Review => in_array($next, [self::Draft, self::Published], true),
            self::Published => in_array($next, [self::Draft, self::Archived], true),
            self::Archived => in_array($next, [self::Draft, self::Published], true),
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $s) => ['value' => $s->value, 'label' => $s->label()], self::cases());
    }
}
