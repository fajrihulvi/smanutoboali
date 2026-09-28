<?php

namespace App\Modules\ChildCondition\Enums;

use App\Modules\Attendance\Enums\AttendanceStatus;

enum ConditionType: string
{
    case Sick = 'SAKIT';
    case HealthComplaint = 'KELUHAN_KESEHATAN';
    case Emotional = 'EMOSIONAL';
    case SpecialPermit = 'IZIN_KHUSUS';
    case FoodAllergy = 'ALERGI_MAKANAN';
    case Other = 'LAINNYA';

    public function label(): string
    {
        return match ($this) {
            self::Sick => 'Sakit',
            self::HealthComplaint => 'Keluhan kesehatan',
            self::Emotional => 'Kondisi emosional',
            self::SpecialPermit => 'Izin khusus',
            self::FoodAllergy => 'Alergi makanan',
            self::Other => 'Lainnya',
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::Sick => 'Anak sakit dan tidak masuk sekolah',
            self::HealthComplaint => 'Masuk sekolah, tetapi ada keluhan (pusing, batuk, dll.)',
            self::Emotional => 'Sedang sedih, cemas, atau ada masalah di rumah',
            self::SpecialPermit => 'Tidak masuk karena keperluan keluarga/acara',
            self::FoodAllergy => 'Alergi atau pantangan makanan',
            self::Other => 'Informasi lain untuk guru',
        };
    }

    /**
     * Usulan status presensi pada tanggal berlaku (tidak diterapkan otomatis).
     */
    public function attendanceSuggestion(): ?AttendanceStatus
    {
        return match ($this) {
            self::Sick => AttendanceStatus::Sick,
            self::SpecialPermit => AttendanceStatus::Permit,
            default => null,
        };
    }

    /**
     * @return list<array{value: string, label: string, hint: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $type) => ['value' => $type->value, 'label' => $type->label(), 'hint' => $type->hint()], self::cases());
    }
}
