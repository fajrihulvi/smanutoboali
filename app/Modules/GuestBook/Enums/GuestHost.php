<?php

namespace App\Modules\GuestBook\Enums;

/**
 * Pihak sekolah yang ingin ditemui tamu ("bertemu dengan").
 */
enum GuestHost: string
{
    case Principal = 'KEPALA_SEKOLAH';
    case VicePrincipal = 'WAKIL_KEPALA_SEKOLAH';
    case Administration = 'TATA_USAHA';
    case Operator = 'OPERATOR_SEKOLAH';
    case Teacher = 'GURU';
    case HomeroomTeacher = 'WALI_KELAS';
    case Treasurer = 'BENDAHARA';
    case AdministrationStaff = 'STAF_TU';

    public function label(): string
    {
        return match ($this) {
            self::Principal => 'Kepala Sekolah',
            self::VicePrincipal => 'Wakil Kepala Sekolah',
            self::Administration => 'Tata Usaha',
            self::Operator => 'Operator Sekolah',
            self::Teacher => 'Guru',
            self::HomeroomTeacher => 'Wali Kelas',
            self::Treasurer => 'Bendahara',
            self::AdministrationStaff => 'Staf TU',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $h) => ['value' => $h->value, 'label' => $h->label()], self::cases());
    }
}
