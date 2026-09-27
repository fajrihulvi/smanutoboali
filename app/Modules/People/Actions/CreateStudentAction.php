<?php

namespace App\Modules\People\Actions;

use App\Modules\Core\Enums\FileDirectory;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\People\Models\Student;
use App\Modules\People\Services\PersonPhotoService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class CreateStudentAction
{
    public function __construct(
        private PersonPhotoService $photos,
        private AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data, ?UploadedFile $photo = null, bool $audit = true): Student
    {
        return DB::transaction(function () use ($data, $photo, $audit) {
            $student = Student::query()->create($data);

            if ($photo) {
                $student->forceFill([
                    'photo_file_id' => $this->photos->replace($student, FileDirectory::Students, null, $photo, false),
                ])->save();
            }

            if ($audit) {
                $this->audit->record('people', 'created', "Menambah siswa {$student->name} ({$student->nis})", $student, [
                    'attributes' => array_map(fn (mixed $value) => $value instanceof \BackedEnum ? $value->value : $value, $data),
                ]);
            }

            return $student;
        });
    }
}
