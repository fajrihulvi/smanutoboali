<?php

namespace App\Modules\Activity\Enums;

enum ActivityType: string
{
    case Daily = 'HARIAN';
    case Classroom = 'KELAS';
    case School = 'SEKOLAH';

    public function label(): string
    {
        return match ($this) {
            self::Daily => 'Harian',
            self::Classroom => 'Kelas',
            self::School => 'Sekolah',
        };
    }

    /**
     * SEKOLAH berlaku untuk semua siswa; jenis lain butuh daftar peserta.
     */
    public function needsParticipants(): bool
    {
        return $this !== self::School;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $t) => ['value' => $t->value, 'label' => $t->label()], self::cases());
    }
}
