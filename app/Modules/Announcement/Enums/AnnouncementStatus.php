<?php

namespace App\Modules\Announcement\Enums;

/**
 * Status turunan dari publish_at (tidak disimpan).
 */
enum AnnouncementStatus: string
{
    case Draft = 'DRAF';
    case Scheduled = 'TERJADWAL';
    case Published = 'TERBIT';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Scheduled => 'Terjadwal',
            self::Published => 'Terbit',
        };
    }
}
