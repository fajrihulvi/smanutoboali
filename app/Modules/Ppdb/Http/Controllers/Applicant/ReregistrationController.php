<?php

namespace App\Modules\Ppdb\Http\Controllers\Applicant;

use App\Http\Controllers\Controller;
use App\Modules\People\Enums\GuardianRelationship;
use App\Modules\Ppdb\Actions\SubmitReregistrationAction;
use App\Modules\Ppdb\Http\Resources\ApplicantResource;
use App\Modules\Ppdb\Models\PpdbApplicant;
use App\Modules\Ppdb\Services\ApplicationRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Daftar ulang oleh pendaftar yang diterima: konfirmasi alamat & data orang tua.
 */
class ReregistrationController extends Controller
{
    public function edit(Request $request, PpdbApplicant $applicant): Response
    {
        $this->authorize('reregister', $applicant);
        $applicant->load(['period.academicYear', 'track.requirements', 'guardians', 'documents.file']);

        return Inertia::render('Ppdb::Applicant/Reregister', [
            'applicant' => (new ApplicantResource($applicant))->detail()->resolve($request),
            'relationships' => array_map(fn (GuardianRelationship $r) => ['value' => $r->value, 'label' => $r->label()], GuardianRelationship::cases()),
        ]);
    }

    public function store(Request $request, PpdbApplicant $applicant, SubmitReregistrationAction $submit): RedirectResponse
    {
        $this->authorize('reregister', $applicant);
        $data = $request->validate([
            'address' => ApplicationRules::student(true)['address'],
            ...ApplicationRules::guardians(true),
            'agreement' => ['accepted'],
        ], ['agreement.accepted' => 'Centang pernyataan daftar ulang.'], ApplicationRules::attributes());

        $submit->handle($applicant, ['address' => $data['address'], 'guardians' => $data['guardians']]);

        return redirect('/ppdb/akun/dashboard')->with('success', 'Data daftar ulang terkirim. Sekolah akan mengonfirmasi.');
    }
}
