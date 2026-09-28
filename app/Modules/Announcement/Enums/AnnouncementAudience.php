<?php

namespace App\Modules\Announcement\Enums;

enum AnnouncementAudience: string
{
    case All = 'SEMUA';
    case Roles = 'PERAN';
    case Classrooms = 'ROMBEL';

    public function label(): string
    {
        return match ($this) {
            self::All => 'Semua warga sekolah',
            self::Roles => 'Peran tertentu',
            self::Classrooms => 'Rombel tertentu',
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::All => 'Kepala sekolah, guru, operator, dan semua orang tua',
            self::Roles => 'Mis. hanya guru, atau hanya orang tua',
            self::Classrooms => 'Orang tua siswa & guru di rombel terpilih',
        };
    }

    /**
     * @return list<array{value: string, label: string, hint: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $a) => ['value' => $a->value, 'label' => $a->label(), 'hint' => $a->hint()], self::cases());
    }
}
