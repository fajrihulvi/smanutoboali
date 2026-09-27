<?php

namespace App\Modules\Core\Enums;

/**
 * Kunci pengaturan sekolah beserta nilai default.
 */
enum SettingKey: string
{
    case SchoolName = 'school_name';
    case SchoolLogo = 'school_logo';
    case SchoolAddress = 'school_address';
    case SchoolPhone = 'school_phone';
    case SchoolEmail = 'school_email';
    case Timezone = 'timezone';
    case UploadMaxSizeKb = 'upload_max_size_kb';

    public function default(): mixed
    {
        return match ($this) {
            self::SchoolName => 'Sekolah Skoolia',
            self::SchoolLogo, self::SchoolAddress, self::SchoolPhone, self::SchoolEmail => null,
            self::Timezone => 'Asia/Jakarta',
            self::UploadMaxSizeKb => (int) config('skoolia.upload.default_max_kb', 5120),
        };
    }

    /**
     * Zona waktu Indonesia yang boleh dipilih.
     *
     * @return array<string, string>
     */
    public static function timezones(): array
    {
        return [
            'Asia/Jakarta' => 'WIB — Asia/Jakarta',
            'Asia/Makassar' => 'WITA — Asia/Makassar',
            'Asia/Jayapura' => 'WIT — Asia/Jayapura',
        ];
    }
}
