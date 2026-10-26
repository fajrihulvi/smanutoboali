<?php

namespace App\Modules\Academic\Actions;

use App\Models\User;
use App\Modules\Academic\Jobs\ImportSubjectsJob;
use App\Modules\Academic\Models\SubjectImport;
use App\Modules\Core\Enums\FileDirectory;
use App\Modules\Core\Enums\FileVisibility;
use App\Modules\Core\Services\FileService;
use App\Modules\People\Actions\StartStudentImportAction;
use App\Modules\People\Enums\ImportStatus;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Simpan file import (privat) lalu jalankan ImportSubjectsJob.
 */
class StartSubjectImportAction
{
    public function __construct(private FileService $files) {}

    public function handle(UploadedFile $upload, User $user): SubjectImport
    {
        // File ditolak (MIME palsu) → catatan import ikut dibatalkan.
        $import = DB::transaction(fn () => $this->prepare($upload, $user));

        // Proses langsung setelah respons terkirim (tanpa menunggu cron/worker antrean);
        // job antrean tetap dikirim sebagai cadangan bila proses web terhenti sebelum mulai.
        ImportSubjectsJob::dispatchAfterResponse($import);
        ImportSubjectsJob::dispatch($import);

        return $import;
    }

    private function prepare(UploadedFile $upload, User $user): SubjectImport
    {
        $import = SubjectImport::query()->create([
            'original_name' => $upload->getClientOriginalName(),
            'status' => ImportStatus::Pending,
            'created_by' => $user->getKey(),
        ]);

        $file = $this->files->store(
            $upload,
            FileDirectory::Misc,
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
