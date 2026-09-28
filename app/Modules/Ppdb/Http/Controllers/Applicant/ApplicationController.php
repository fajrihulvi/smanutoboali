<?php

namespace App\Modules\Ppdb\Http\Controllers\Applicant;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Core\Services\FileService;
use App\Modules\People\Enums\Gender;
use App\Modules\People\Enums\GuardianRelationship;
use App\Modules\People\Enums\Religion;
use App\Modules\Ppdb\Actions\SaveApplicationDraftAction;
use App\Modules\Ppdb\Actions\StartApplicationAction;
use App\Modules\Ppdb\Actions\SubmitApplicationAction;
use App\Modules\Ppdb\Actions\UploadDocumentAction;
use App\Modules\Ppdb\Http\Resources\ApplicantResource;
use App\Modules\Ppdb\Models\PpdbApplicant;
use App\Modules\Ppdb\Models\PpdbDocument;
use App\Modules\Ppdb\Models\PpdbDocumentRequirement;
use App\Modules\Ppdb\Models\PpdbTrack;
use App\Modules\Ppdb\Services\ApplicationProof;
use App\Modules\Ppdb\Services\ApplicationRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Formulir pendaftaran multi-step milik calon orang tua (draf otomatis, dokumen, kirim, bukti PDF).
 */
class ApplicationController extends Controller
{
    public function store(Request $request, StartApplicationAction $start): RedirectResponse
    {
        $data = $request->validate(['track' => ['required', 'string', Rule::exists(PpdbTrack::class, 'public_id')->whereNull('deleted_at')]], [], ['track' => 'jalur']);

        /** @var User $user */
        $user = $request->user();
        $applicant = $start->handle($user, PpdbTrack::query()->where('public_id', $data['track'])->firstOrFail());

        return redirect("/ppdb/akun/pendaftaran/{$applicant->public_id}");
    }

    public function edit(Request $request, PpdbApplicant $applicant): Response
    {
        /** @var User $user */
        $user = $request->user();
        $this->authorize('view', $applicant);
        abort_unless($applicant->user_id === $user->id, 403);
        $applicant->load(['period.academicYear', 'track.requirements', 'guardians', 'documents.file']);

        $step = $request->string('langkah')->value();
        $editable = $user->can('update', $applicant);

        return Inertia::render('Ppdb::Applicant/Form', [
            'applicant' => (new ApplicantResource($applicant))->detail()->resolve($request),
            'step' => $editable && in_array($step, ApplicationRules::STEPS, true) ? $step : ($editable ? 'siswa' : 'tinjau'),
            'editable' => $editable,
            'options' => [
                'genders' => array_map(fn (Gender $g) => ['value' => $g->value, 'label' => $g->label()], Gender::cases()),
                'religions' => array_map(fn (Religion $r) => ['value' => $r->value, 'label' => $r->label()], Religion::cases()),
                'relationships' => array_map(fn (GuardianRelationship $r) => ['value' => $r->value, 'label' => $r->label()], GuardianRelationship::cases()),
            ],
        ]);
    }

    /**
     * PUT (JSON) — simpan draf. `complete=1` memvalidasi langkah secara penuh (tombol Lanjut).
     */
    public function update(Request $request, PpdbApplicant $applicant, SaveApplicationDraftAction $save): JsonResponse
    {
        $this->authorize('update', $applicant);
        $complete = $request->boolean('complete');
        $rules = [];

        if ($request->has('student')) {
            $rules += collect(ApplicationRules::student($complete))->mapWithKeys(fn ($r, $k) => ["student.{$k}" => $r])->all();
        }

        if ($request->has('guardians')) {
            $rules += ApplicationRules::guardians($complete);
        }

        $attributes = collect(ApplicationRules::attributes())->flatMap(fn ($label, $key) => [$key => $label, "student.{$key}" => $label])->all();
        $data = $request->validate($rules, [], $attributes);

        $save->handle($applicant, [...(array) ($data['student'] ?? []), ...(array_key_exists('guardians', $data) ? ['guardians' => $data['guardians']] : [])]);

        return response()->json(['saved_at' => now()->toIso8601String()]);
    }

    public function upload(Request $request, PpdbApplicant $applicant, PpdbDocumentRequirement $requirement, UploadDocumentAction $upload): RedirectResponse
    {
        $this->authorize('update', $applicant);
        $request->validate(['file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:'.UploadDocumentAction::MAX_KB]], [], ['file' => 'dokumen']);

        /** @var User $user */
        $user = $request->user();
        /** @var UploadedFile $file */
        $file = $request->file('file');
        $upload->handle($applicant, $requirement, $file, $user);

        return back()->with('success', "{$requirement->name} terunggah.");
    }

    public function removeDocument(PpdbApplicant $applicant, PpdbDocumentRequirement $requirement, FileService $files): RedirectResponse
    {
        $this->authorize('update', $applicant);

        $document = PpdbDocument::query()->where('ppdb_applicant_id', $applicant->id)->where('ppdb_document_requirement_id', $requirement->id)->first();

        if ($document !== null) {
            DB::transaction(function () use ($document, $files) {
                if ($document->file !== null) {
                    $files->delete($document->file);
                }

                $document->delete();
            });
        }

        return back()->with('success', "{$requirement->name} dihapus.");
    }

    public function submit(PpdbApplicant $applicant, SubmitApplicationAction $submit): RedirectResponse
    {
        $this->authorize('update', $applicant);
        $submit->handle($applicant);

        return redirect('/ppdb/akun/dashboard')->with('success', "Pendaftaran terkirim dengan nomor {$applicant->registration_number}.");
    }

    public function destroy(PpdbApplicant $applicant): RedirectResponse
    {
        $this->authorize('delete', $applicant);
        $applicant->delete();

        return redirect('/ppdb/akun/dashboard')->with('success', 'Draf pendaftaran dihapus.');
    }

    public function proof(PpdbApplicant $applicant, ApplicationProof $proof): HttpResponse
    {
        $this->authorize('view', $applicant);
        abort_if($applicant->registration_number === null, 404);

        return $proof->download($applicant);
    }
}
