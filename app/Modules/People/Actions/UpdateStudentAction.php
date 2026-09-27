<?php

namespace App\Modules\People\Actions;

use App\Modules\Core\Enums\FileDirectory;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\People\Enums\StudentStatus;
use App\Modules\People\Models\Student;
use App\Modules\People\Services\PersonPhotoService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateStudentAction
{
    public function __construct(
        private PersonPhotoService $photos,
        private AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function handle(Student $student, array $data, ?UploadedFile $photo = null, bool $removePhoto = false): Student
    {
        if (isset($data['status'])) {
            $next = $data['status'] instanceof StudentStatus ? $data['status'] : StudentStatus::from((string) $data['status']);

            if (! $student->status->canTransitionTo($next)) {
                throw ValidationException::withMessages([
                    'status' => "Status {$student->status->label()} tidak dapat diubah menjadi {$next->label()}.",
                ]);
            }
        }

        return DB::transaction(function () use ($student, $data, $photo, $removePhoto) {
            $student->fill($data);
            $student->photo_file_id = $this->photos->replace(
                $student,
                FileDirectory::Students,
                $student->photo_file_id,
                $photo,
                $removePhoto,
            );

            $dirty = $student->getDirty();
            $original = array_intersect_key($student->getOriginal(), $dirty);
            $student->save();

            if ($dirty !== []) {
                $plain = fn (array $values) => array_map(
                    fn (mixed $value) => match (true) {
                        $value instanceof \BackedEnum => $value->value,
                        $value instanceof \DateTimeInterface => $value->format('Y-m-d'),
                        default => $value,
                    },
                    $values,
                );

                $this->audit->record('people', 'updated', "Mengubah data siswa {$student->name}", $student, [
                    'old' => $plain($original),
                    'attributes' => $plain($dirty),
                ]);
            }

            return $student;
        });
    }
}
