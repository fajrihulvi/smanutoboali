<?php

namespace App\Modules\Ppdb\Services;

use App\Modules\Ppdb\Models\PpdbApplicant;
use App\Modules\School\Models\School;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * Bukti pendaftaran PPDB (PDF A4).
 */
class ApplicationProof
{
    public function download(PpdbApplicant $applicant): Response
    {
        $applicant->loadMissing(['period.academicYear', 'track.requirements', 'guardians', 'documents']);

        return Pdf::loadView('ppdb::proof', [
            'applicant' => $applicant,
            'school' => School::current(),
            'uploaded' => $applicant->documents->pluck('ppdb_document_requirement_id')->all(),
        ])->setPaper('a4')->download(Str::slug('bukti pendaftaran '.$applicant->registration_number).'.pdf');
    }
}
