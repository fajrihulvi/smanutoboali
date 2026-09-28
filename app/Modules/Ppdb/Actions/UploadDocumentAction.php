<?php

namespace App\Modules\Ppdb\Actions;

use App\Models\User;
use App\Modules\Core\Enums\FileDirectory;
use App\Modules\Core\Enums\FileVisibility;
use App\Modules\Core\Services\FileService;
use App\Modules\Ppdb\Enums\DocumentStatus;
use App\Modules\Ppdb\Models\PpdbApplicant;
use App\Modules\Ppdb\Models\PpdbDocument;
use App\Modules\Ppdb\Models\PpdbDocumentRequirement;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Unggah/ganti dokumen satu persyaratan; status kembali "belum diperiksa".
 */
class UploadDocumentAction
{
    public const MAX_KB = 5120;

    public function __construct(private FileService $files) {}

    /**
     * @throws ValidationException
     */
    public function handle(PpdbApplicant $applicant, PpdbDocumentRequirement $requirement, UploadedFile $upload, User $uploader): PpdbDocument
    {
        if ($requirement->ppdb_track_id !== $applicant->ppdb_track_id) {
            throw ValidationException::withMessages(['file' => 'Persyaratan tidak sesuai jalur pendaftaran.']);
        }

        return DB::transaction(function () use ($applicant, $requirement, $upload, $uploader) {
            $document = PpdbDocument::query()->firstOrCreate(
                ['ppdb_applicant_id' => $applicant->id, 'ppdb_document_requirement_id' => $requirement->id],
            );

            $old = $document->file;
            $this->files->store($upload, FileDirectory::Ppdb, FileVisibility::Private, $document,
                allowedMimes: [...FileService::IMAGE_MIMES, ...FileService::DOCUMENT_MIMES], maxKb: self::MAX_KB, uploader: $uploader);

            if ($old !== null) {
                $this->files->delete($old);
            }

            $document->update(['status' => DocumentStatus::Pending, 'note' => null, 'reviewed_by' => null, 'reviewed_at' => null]);

            return $document->load('file');
        });
    }
}
