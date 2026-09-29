<?php

namespace App\Modules\Ppdb\Actions;

use App\Modules\Ppdb\Enums\ApplicantStatus;
use App\Modules\Ppdb\Enums\SelectionProposal;
use App\Modules\Ppdb\Models\PpdbApplicant;
use Illuminate\Validation\ValidationException;

/**
 * Operator mengisi skor/catatan dan (opsional) mengubah usulan satu pendaftar sebelum diputuskan.
 */
class ScoreApplicantAction
{
    /**
     * @throws ValidationException
     */
    public function handle(PpdbApplicant $applicant, ?float $score, ?string $note, ?SelectionProposal $proposal): PpdbApplicant
    {
        if (! in_array($applicant->status, [ApplicantStatus::Verified, ApplicantStatus::InSelection], true)) {
            throw ValidationException::withMessages(['score' => 'Skor hanya bisa diubah untuk pendaftar terverifikasi yang belum diputuskan.']);
        }

        $applicant->update([
            'score' => $score,
            'selection_note' => $note,
            'proposal' => $proposal,
            'status' => $proposal !== null ? ApplicantStatus::InSelection : $applicant->status,
        ]);

        return $applicant;
    }
}
