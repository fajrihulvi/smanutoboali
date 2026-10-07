<?php

namespace App\Modules\People\Actions;

use App\Models\User;
use App\Modules\Core\Enums\FileDirectory;
use App\Modules\Core\Enums\FileVisibility;
use App\Modules\Core\Services\FileService;
use App\Modules\People\Enums\ImportStatus;
use App\Modules\People\Jobs\ImportTeachersJob;
use App\Modules\People\Models\TeacherImport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Simpan file import (privat) lalu antrekan ImportTeachersJob.
 */
class StartTeacherImportAction
{
    public function __construct(private FileService $files) {}

    public function handle(UploadedFile $upload, User $user): TeacherImport
    {
        // File ditolak (MIME palsu) → catatan import ikut dibatalkan.
        $import = DB::transaction(fn () => $this->prepare($upload, $user));

        ImportTeachersJob::dispatch($import);

        return $import;
    }

    private function prepare(UploadedFile $upload, User $user): TeacherImport
    {
        $import = TeacherImport::query()->create([
            'original_name' => $upload->getClientOriginalName(),
            'status' => ImportStatus::Pending,
            'created_by' => $user->getKey(),
        ]);

        $file = $this->files->store(
            $upload,
            FileDirectory::Teachers,
            FileVisibility::Private,
            $import,
            allowedMimes: StartStudentImportAction::ALLOWED_MIMES,
            maxKb: 10240,
            uploader: $user,
        );

        $import->forceFill(['file_id' => $file->id])->save();

        return $import;
    }
}
