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
    /** Presensi masuk guru setelah jam ini (HH:MM) = TERLAMBAT. */
    case TeacherLateAfter = 'teacher_late_after';
    /** NONAKTIF | IP | GPS — lihat Attendance\Enums\TeacherAttendanceValidation. */
    case TeacherAttendanceValidation = 'teacher_attendance_validation';
    /** Daftar IP/CIDR jaringan sekolah, dipisah koma/baris baru. */
    case TeacherAttendanceIps = 'teacher_attendance_ips';
    case SchoolLatitude = 'school_latitude';
    case SchoolLongitude = 'school_longitude';
    /** Radius GPS (meter) dari titik sekolah. */
    case TeacherAttendanceRadiusM = 'teacher_attendance_radius_m';
    /** Kriteria ketuntasan minimal (0–100). */
    case GradeKkm = 'grade_kkm';
    /** Batas bawah predikat A/B/C (nilai akhir); di bawah C = D. */
    case GradePredicateA = 'grade_predicate_a';
    case GradePredicateB = 'grade_predicate_b';
    case GradePredicateC = 'grade_predicate_c';

    public function default(): mixed
    {
        return match ($this) {
            self::Timezone => 'Asia/Jakarta',
            self::UploadMaxSizeKb => (int) config('skoolia.upload.default_max_kb', 5120),
            self::AttendanceMode => 'HARIAN',
            self::AttendanceEditDays => 0,
            self::TeacherLateAfter => '07:15',
            self::TeacherAttendanceValidation => 'NONAKTIF',
            self::TeacherAttendanceIps => '',
            self::SchoolLatitude, self::SchoolLongitude => null,
            self::TeacherAttendanceRadiusM => 200,
            self::GradeKkm => 75,
            self::GradePredicateA => 90,
            self::GradePredicateB => 80,
            self::GradePredicateC => 70,
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
