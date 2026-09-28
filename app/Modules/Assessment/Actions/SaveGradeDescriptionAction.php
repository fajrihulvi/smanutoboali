<?php

namespace App\Modules\Assessment\Actions;

use App\Models\User;
use App\Modules\Academic\Models\TeachingAssignment;
use App\Modules\Assessment\Models\GradeDescription;
use App\Modules\Assessment\Services\Gradebook;
use App\Modules\People\Models\Student;
use App\Modules\School\Models\Semester;

/**
 * Deskripsi capaian per siswa (kosong = hapus) dan pengisian massal dari template.
 */
class SaveGradeDescriptionAction
{
    public function handle(TeachingAssignment $assignment, Semester $semester, Student $student, ?string $description, User $actor): void
    {
        $keys = ['teaching_assignment_id' => $assignment->id, 'semester_id' => $semester->id, 'student_id' => $student->id];

        if ($description === null || trim($description) === '') {
            GradeDescription::query()->where($keys)->delete();

            return;
        }

        GradeDescription::query()->updateOrCreate($keys, ['description' => trim($description), 'updated_by' => $actor->id]);
    }

    /**
     * Isi deskripsi dari template untuk siswa yang sudah punya nilai akhir.
     *
     * @return int jumlah deskripsi yang diisi
     */
    public function fillFromTemplates(Gradebook $book, User $actor, bool $overwrite): int
    {
        $filled = 0;

        foreach ($book->students as $student) {
            $suggested = $book->suggestedDescription($student);

            if ($suggested === null || (! $overwrite && $book->description($student) !== null)) {
                continue;
            }

            $this->handle($book->assignment, $book->semester, $student, $suggested, $actor);
            $filled++;
        }

        return $filled;
    }
}
