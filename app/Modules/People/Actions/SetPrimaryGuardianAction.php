<?php

namespace App\Modules\People\Actions;

use App\Modules\Core\Services\AuditLogger;
use App\Modules\People\Models\Guardian;
use App\Modules\People\Models\Student;
use Illuminate\Support\Facades\DB;

class SetPrimaryGuardianAction
{
    public function __construct(private AuditLogger $audit) {}

    public function handle(Student $student, Guardian $guardian): void
    {
        DB::transaction(function () use ($student, $guardian) {
            $student->guardians()->newPivotStatement()
                ->where('student_id', $student->getKey())
                ->update(['is_primary' => false]);

            $student->guardians()->updateExistingPivot($guardian->getKey(), ['is_primary' => true]);

            $this->audit->record('people', 'guardian_primary', "Menjadikan {$guardian->name} orang tua utama {$student->name}", $student, [
                'guardian' => $guardian->public_id,
            ]);
        });
    }
}
