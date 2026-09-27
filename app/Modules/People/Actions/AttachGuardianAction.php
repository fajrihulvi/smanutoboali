<?php

namespace App\Modules\People\Actions;

use App\Modules\Core\Services\AuditLogger;
use App\Modules\People\Enums\GuardianRelationship;
use App\Modules\People\Models\Guardian;
use App\Modules\People\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Hubungkan orang tua ke siswa. Hanya satu orang tua utama per siswa;
 * orang tua pertama otomatis menjadi utama.
 */
class AttachGuardianAction
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @throws ValidationException
     */
    public function handle(
        Student $student,
        Guardian $guardian,
        GuardianRelationship $relationship,
        bool $isPrimary = false,
        bool $audit = true,
    ): void {
        if ($student->guardians()->whereKey($guardian->getKey())->exists()) {
            throw ValidationException::withMessages(['guardian' => "{$guardian->name} sudah terhubung dengan siswa ini."]);
        }

        DB::transaction(function () use ($student, $guardian, $relationship, $isPrimary, $audit) {
            $isPrimary = $isPrimary || ! $student->guardians()->exists();

            if ($isPrimary) {
                $student->guardians()->newPivotStatement()
                    ->where('student_id', $student->getKey())
                    ->update(['is_primary' => false]);
            }

            $student->guardians()->attach($guardian->getKey(), [
                'relationship' => $relationship->value,
                'is_primary' => $isPrimary,
            ]);

            if ($audit) {
                $this->audit->record('people', 'guardian_attached', "Menghubungkan {$guardian->name} ({$relationship->label()}) ke siswa {$student->name}", $student, [
                    'guardian' => $guardian->public_id,
                    'relationship' => $relationship->value,
                    'is_primary' => $isPrimary,
                ]);
            }
        });
    }
}
