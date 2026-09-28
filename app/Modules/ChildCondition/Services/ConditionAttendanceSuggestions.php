<?php

namespace App\Modules\ChildCondition\Services;

use App\Models\User;
use App\Modules\ChildCondition\Enums\ConditionType;
use App\Modules\ChildCondition\Models\ChildCondition;
use App\Modules\Core\Contracts\AttendanceSuggestions;
use Carbon\CarbonImmutable;

/**
 * Laporan SAKIT/IZIN_KHUSUS yang berlaku pada tanggal presensi → usulan status (tanpa isi laporan).
 * Tautan detail hanya bila guru berhak melihat laporannya.
 */
class ConditionAttendanceSuggestions implements AttendanceSuggestions
{
    public function __construct(private ConditionAccess $access) {}

    public function forStudents(array $studentIds, CarbonImmutable $date, User $viewer): array
    {
        if ($studentIds === []) {
            return [];
        }

        $conditions = ChildCondition::query()
            ->whereIn('student_id', $studentIds)
            ->whereIn('type', [ConditionType::Sick, ConditionType::SpecialPermit])
            ->covering($date)
            ->orderBy('created_at')
            ->get();

        $suggestions = [];

        foreach ($conditions as $condition) {
            $status = $condition->type->attendanceSuggestion();

            if ($status === null) {
                continue;
            }

            $suggestions[$condition->student_id] = [
                'status' => $status->value,
                'source' => 'Laporan orang tua: '.$condition->type->label(),
                'url' => $this->access->canView($viewer, $condition) ? "/guru/kondisi-anak/{$condition->public_id}" : null,
            ];
        }

        return $suggestions;
    }
}
