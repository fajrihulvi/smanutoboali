<?php

namespace App\Modules\Core\Contracts;

use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * Sumber usulan status presensi (mis. laporan kondisi anak SAKIT/IZIN). Modul pendaftar
 * memakai tag {@see self::TAG}; modul Attendance hanya menampilkan usulan, tidak menerapkannya.
 */
interface AttendanceSuggestions
{
    public const TAG = 'core.attendance_suggestions';

    /**
     * @param  list<int>  $studentIds
     * @return array<int, array{status: string, source: string, url: string|null}> key = student_id
     */
    public function forStudents(array $studentIds, CarbonImmutable $date, User $viewer): array;
}
