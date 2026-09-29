<?php

namespace App\Modules\Ppdb\Actions;

use App\Models\User;
use App\Modules\Ppdb\Enums\ApplicantStatus;
use App\Modules\Ppdb\Enums\DocumentStatus;
use App\Modules\Ppdb\Models\PpdbDocument;
use Illuminate\Validation\ValidationException;

/**
 * Operator menandai satu dokumen valid / tidak valid (dengan catatan).
 */
class ReviewDocumentAction
{
    /**
     * @throws ValidationException
     */
    public function handle(PpdbDocument $document, DocumentStatus $status, ?string $note, User $actor): PpdbDocument
    {
        $document->loadMissing('applicant');

        if (! in_array($document->applicant->status, [ApplicantStatus::Submitted, ApplicantStatus::Verified], true)) {
            throw ValidationException::withMessages(['document' => 'Dokumen hanya bisa diperiksa saat pendaftaran menunggu verifikasi.']);
        }

        if ($status === DocumentStatus::Invalid && blank($note)) {
            throw ValidationException::withMessages(['note' => 'Tulis alasan dokumen tidak valid.']);
        }

        $document->update(['status' => $status, 'note' => $status === DocumentStatus::Invalid ? $note : null, 'reviewed_by' => $actor->id, 'reviewed_at' => now()]);

        return $document;
    }
}
