<?php

namespace App\Modules\People\Actions;

use App\Modules\Core\Services\AuditLogger;
use App\Modules\People\Models\Guardian;
use App\Modules\People\Models\Student;
use Illuminate\Support\Facades\DB;

/**
 * Lepaskan orang tua dari siswa. Bila yang dilepas orang tua utama, orang tua
 * lain yang tersisa (paling lama terhubung) menjadi utama.
 */
class DetachGuardianAction
{
    public function __construct(private AuditLogger $audit) {}

    public function handle(Student $student, Guardian $guardian): void
    {
        DB::transaction(function () use ($student, $guardian) {
            $wasPrimary = (bool) $student->guardians()->whereKey($guardian->getKey())->value('guardian_student.is_primary');

            $student->guardians()->detach($guardian->getKey());

            if ($wasPrimary) {
                $next = $student->guardians()->orderBy('guardian_student.created_at')->first();

                if ($next) {
                    $student->guardians()->updateExistingPivot($next->getKey(), ['is_primary' => true]);
                }
            }

            $this->audit->record('people', 'guardian_detached', "Melepas {$guardian->name} dari siswa {$student->name}", $student, [
                'guardian' => $guardian->public_id,
            ]);
        });
    }
}
