<?php

namespace App\Modules\Cms\Enums;

enum PostType: string
{
    case News = 'BERITA';
    case Agenda = 'AGENDA';

    public function label(): string
    {
        return match ($this) {
            self::News => 'Berita',
            self::Agenda => 'Agenda',
        };
    }

    /**
     * Segmen URL (panel CMS & website publik).
     */
    public function segment(): string
    {
        return match ($this) {
            self::News => 'berita',
            self::Agenda => 'agenda',
        };
    }

    public static function fromSegment(string $segment): self
    {
        return $segment === 'agenda' ? self::Agenda : self::News;
    }
}
