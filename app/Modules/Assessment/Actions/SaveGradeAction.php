<?php

namespace App\Modules\Assessment\Actions;

use App\Models\User;
use App\Modules\Assessment\Models\Grade;
use App\Modules\Assessment\Models\GradeComponent;
use App\Modules\People\Models\Student;
use Illuminate\Validation\ValidationException;

/**
 * Simpan satu sel nilai (null = kosongkan). Komponen terpublikasi terkunci.
 * Tidak dicatat per sel di audit log (terlalu banyak); publikasi & koreksi komponen tercatat.
 */
class SaveGradeAction
{
    /**
     * @throws ValidationException
     */
    public function handle(GradeComponent $component, Student $student, ?float $score, User $actor): ?Grade
    {
        if ($component->isPublished()) {
            throw ValidationException::withMessages(['score' => 'Komponen sudah dipublikasikan dan terkunci.']);
        }

        if ($score === null) {
            Grade::query()->where('grade_component_id', $component->id)->where('student_id', $student->id)->delete();

            return null;
        }

        return Grade::query()->updateOrCreate(
            ['grade_component_id' => $component->id, 'student_id' => $student->id],
            ['score' => round($score, 2), 'updated_by' => $actor->id],
        );
    }
}
