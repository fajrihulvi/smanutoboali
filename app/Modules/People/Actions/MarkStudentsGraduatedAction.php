<?php

namespace App\Modules\People\Actions;

use App\Modules\Core\Services\AuditLogger;
use App\Modules\People\Enums\StudentStatus;
use App\Modules\People\Models\Student;

/**
 * Tandai siswa aktif sebagai LULUS (dipakai fitur Naik Kelas modul Academic).
 */
class MarkStudentsGraduatedAction
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  list<int>  $studentIds
     * @return int jumlah siswa yang diluluskan
     */
    public function handle(array $studentIds): int
    {
        if ($studentIds === []) {
            return 0;
        }

        // AKTIF → LULUS adalah transisi sah (StudentStatus::canTransitionTo).
        $count = 0;

        foreach (array_chunk($studentIds, 500) as $chunk) {
            $count += Student::query()
                ->whereKey($chunk)
                ->where('status', StudentStatus::Active)
                ->update(['status' => StudentStatus::Graduated]);
        }

        $this->audit->record('people', 'graduated', "Meluluskan {$count} siswa", properties: ['count' => $count]);

        return $count;
    }
}
