<?php

namespace App\Modules\GuestBook\Enums;

/**
 * Kategori tamu.
 */
enum GuestCategory: string
{
    case Parent = 'ORANG_TUA';
    case Student = 'SISWA';
    case Alumni = 'ALUMNI';
    case Teacher = 'GURU';
    case EducationStaff = 'TENAGA_PENDIDIK';
    case Government = 'INSTANSI_PEMERINTAH';
    case Foundation = 'YAYASAN';
    case Partner = 'MITRA_SEKOLAH';
    case Other = 'LAINNYA';

    public function label(): string
    {
        return match ($this) {
            self::Parent => 'Orang Tua',
            self::Student => 'Siswa',
            self::Alumni => 'Alumni',
            self::Teacher => 'Guru',
            self::EducationStaff => 'Tenaga Pendidik',
            self::Government => 'Instansi Pemerintahan',
            self::Foundation => 'Yayasan',
            self::Partner => 'Mitra Sekolah',
            self::Other => 'Lainnya',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $c) => ['value' => $c->value, 'label' => $c->label()], self::cases());
    }
}
