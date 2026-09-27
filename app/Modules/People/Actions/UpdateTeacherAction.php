<?php

namespace App\Modules\People\Actions;

use App\Modules\Core\Enums\FileDirectory;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\People\Models\Teacher;
use App\Modules\People\Services\PersonPhotoService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Ubah data guru. Dipakai operator (semua kolom) dan guru sendiri (kolom terbatas).
 */
class UpdateTeacherAction
{
    public function __construct(
        private PersonPhotoService $photos,
        private AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Teacher $teacher, array $data, ?UploadedFile $photo = null, bool $removePhoto = false): Teacher
    {
        return DB::transaction(function () use ($teacher, $data, $photo, $removePhoto) {
            $teacher->fill($data);
            $teacher->photo_file_id = $this->photos->replace(
                $teacher,
                FileDirectory::Teachers,
                $teacher->photo_file_id,
                $photo,
                $removePhoto,
            );

            $dirty = $teacher->getDirty();
            $original = array_intersect_key($teacher->getOriginal(), $dirty);
            $teacher->save();

            if ($dirty !== []) {
                $plain = fn (array $values) => array_map(fn (mixed $value) => $value instanceof \BackedEnum ? $value->value : $value, $values);

                $this->audit->record('people', 'updated', "Mengubah data guru {$teacher->name}", $teacher, [
                    'old' => $plain($original),
                    'attributes' => $plain($dirty),
                ]);
            }

            return $teacher;
        });
    }
}
