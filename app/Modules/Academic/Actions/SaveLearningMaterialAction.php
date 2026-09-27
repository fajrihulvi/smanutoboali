<?php

namespace App\Modules\Academic\Actions;

use App\Models\User;
use App\Modules\Academic\Enums\MaterialType;
use App\Modules\Academic\Models\LearningMaterial;
use App\Modules\Academic\Models\TeachingAssignment;
use App\Modules\Core\Enums\FileDirectory;
use App\Modules\Core\Enums\FileVisibility;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\Core\Services\FileService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Buat/ubah materi: tipe FILE menyimpan satu file privat (menggantikan yang lama),
 * tipe LINK menyimpan satu URL. Berganti tipe membuang isian tipe sebelumnya.
 */
class SaveLearningMaterialAction
{
    public function __construct(
        private FileService $files,
        private AuditLogger $audit,
    ) {}

    /**
     * @param  array{teaching_assignment: TeachingAssignment, title: string, description: string|null, type: MaterialType, url: string|null}  $data
     *
     * @throws ValidationException
     */
    public function handle(array $data, User $actor, ?UploadedFile $upload = null, ?LearningMaterial $material = null): LearningMaterial
    {
        $type = $data['type'];

        $hasFile = $material !== null && $material->file()->exists();

        if ($type === MaterialType::File && $upload === null && ! $hasFile) {
            throw ValidationException::withMessages(['file' => 'Pilih file materi.']);
        }

        if ($type === MaterialType::Link && blank($data['url'])) {
            throw ValidationException::withMessages(['url' => 'Isi tautan materi.']);
        }

        return DB::transaction(function () use ($data, $actor, $upload, $material, $type) {
            $material ??= new LearningMaterial(['created_by' => $actor->getKey()]);
            $isNew = ! $material->exists;
            $assignment = $data['teaching_assignment'];

            $material->fill([
                'academic_year_id' => $assignment->academic_year_id,
                'teaching_assignment_id' => $assignment->id,
                'title' => $data['title'],
                'description' => $data['description'],
                'type' => $type,
                'url' => $type === MaterialType::Link ? $data['url'] : null,
            ])->save();

            $old = $material->file()->first();

            if ($type === MaterialType::Link && $old !== null) {
                $this->files->delete($old);
            }

            if ($type === MaterialType::File && $upload !== null) {
                $this->files->store(
                    $upload,
                    FileDirectory::Materials,
                    FileVisibility::Private,
                    $material,
                    [...FileService::DOCUMENT_MIMES, ...FileService::OFFICE_MIMES, ...FileService::IMAGE_MIMES],
                    uploader: $actor,
                );

                if ($old !== null) {
                    $this->files->delete($old);
                }
            }

            $this->audit->record(
                'academic',
                $isNew ? 'material_created' : 'material_updated',
                ($isNew ? 'Menambah' : 'Mengubah')." materi \"{$material->title}\"",
                $material,
            );

            return $material;
        });
    }
}
