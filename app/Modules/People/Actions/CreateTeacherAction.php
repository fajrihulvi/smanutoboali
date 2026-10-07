<?php

namespace App\Modules\People\Actions;

use App\Modules\Core\Enums\FileDirectory;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\People\Models\Teacher;
use App\Modules\People\Services\PersonPhotoService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class CreateTeacherAction
{
    public function __construct(
        private PersonPhotoService $photos,
        private LinkPersonAccountAction $linkAccount,
        private AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data  atribut teachers yang tervalidasi
     * @param  bool  $audit  false untuk import massal (dicatat sekali per file)
     * @return array{teacher: Teacher, temporary_password: string|null}
     */
    public function handle(array $data, ?UploadedFile $photo = null, ?string $accountEmail = null, bool $audit = true): array
    {
        return DB::transaction(function () use ($data, $photo, $accountEmail, $audit) {
            $teacher = Teacher::query()->create($data);

            if ($photo) {
                $teacher->forceFill([
                    'photo_file_id' => $this->photos->replace($teacher, FileDirectory::Teachers, null, $photo, false),
                ])->save();
            }

            if ($audit) {
                $this->audit->record('people', 'created', "Menambah guru {$teacher->name}", $teacher, [
                    'attributes' => array_map(fn (mixed $value) => $value instanceof \BackedEnum ? $value->value : $value, $data),
                ]);
            }

            $password = $accountEmail ? $this->linkAccount->handle($teacher, $accountEmail) : null;

            return ['teacher' => $teacher, 'temporary_password' => $password];
        });
    }
}
