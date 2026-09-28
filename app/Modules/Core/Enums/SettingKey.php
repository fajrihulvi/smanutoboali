<?php

namespace App\Modules\Core\Enums;

/**
 * Kunci pengaturan sistem beserta nilai default.
 * Identitas sekolah ada di tabel `schools` (modul School).
 */
enum SettingKey: string
{
    case Timezone = 'timezone';
    case UploadMaxSizeKb = 'upload_max_size_kb';
    /** HARIAN | PER_MAPEL — lihat Attendance\Enums\AttendanceMode. */
    case AttendanceMode = 'attendance_mode';
    /** Guru boleh mengubah presensi sampai N hari setelah tanggalnya (0 = hari yang sama). */
    case AttendanceEditDays = 'attendance_edit_days';

    public function default(): mixed
    {
        return match ($this) {
            self::Timezone => 'Asia/Jakarta',
            self::UploadMaxSizeKb => (int) config('skoolia.upload.default_max_kb', 5120),
            self::AttendanceMode => 'HARIAN',
            self::AttendanceEditDays => 0,
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
