<?php

namespace App\Modules\People\Actions;

use App\Models\User;
use App\Modules\Core\Enums\FileDirectory;
use App\Modules\Core\Enums\FileVisibility;
use App\Modules\Core\Services\FileService;
use App\Modules\People\Enums\ImportStatus;
use App\Modules\People\Jobs\ImportStudentsJob;
use App\Modules\People\Models\StudentImport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Simpan file import (privat) lalu antrekan ImportStudentsJob.
 */
class StartStudentImportAction
{
    /**
     * finfo mengenali .xlsx sebagai spreadsheet atau zip (tergantung versi libmagic),
     * dan .csv sebagai text/csv atau text/plain.
     *
     * @var list<string>
     */
    public const ALLOWED_MIMES = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/zip',
        'text/csv',
        'text/plain',
    ];

    public function __construct(private FileService $files) {}

    public function handle(UploadedFile $upload, User $user): StudentImport
    {
        // File ditolak (MIME palsu) → catatan import ikut dibatalkan.
        $import = DB::transaction(fn () => $this->prepare($upload, $user));

        ImportStudentsJob::dispatch($import);

        return $import;
    }

    private function prepare(UploadedFile $upload, User $user): StudentImport
    {
        $import = StudentImport::query()->create([
            'original_name' => $upload->getClientOriginalName(),
            'status' => ImportStatus::Pending,
            'created_by' => $user->getKey(),
        ]);

        $file = $this->files->store(
            $upload,
            FileDirectory::Students,
            FileVisibility::Private,
            $import,
            allowedMimes: self::ALLOWED_MIMES,
            maxKb: 10240,
            uploader: $user,
        );

        $import->forceFill(['file_id' => $file->id])->save();

        return $import;
    }
}
