<?php

namespace App\Modules\Ppdb\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Finance\Models\FeeType;
use App\Modules\People\Enums\Gender;
use App\Modules\People\Enums\GuardianRelationship;
use App\Modules\People\Enums\Religion;
use App\Modules\Ppdb\Actions\ConfirmReregistrationAction;
use App\Modules\Ppdb\Actions\ReviewDocumentAction;
use App\Modules\Ppdb\Actions\TransitionApplicantAction;
use App\Modules\Ppdb\Enums\ApplicantStatus;
use App\Modules\Ppdb\Enums\DocumentStatus;
use App\Modules\Ppdb\Http\Resources\ApplicantResource;
use App\Modules\Ppdb\Models\PpdbApplicant;
use App\Modules\Ppdb\Models\PpdbDocument;
use App\Modules\Ppdb\Services\NisSuggestion;
use App\Modules\Ppdb\Services\PeriodPicker;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pendaftar (operator PPDB; kepala sekolah hanya melihat): daftar, verifikasi dokumen,
 * verifikasi/kembalikan, konfirmasi daftar ulang.
 */
class ApplicantController extends Controller
{
    public const PER_PAGE = 20;

    public function index(Request $request, PeriodPicker $picker): Response
    {
        $periods = $picker->all();
        $period = $picker->selected($request, $periods);
        $status = $request->string('status')->value();
        $track = $request->string('jalur')->value();
        $search = $request->string('q')->trim()->value();
        /** @var string $base */
        $base = $request->route()?->defaults['base'] ?? '/operator/ppdb';

        $applicants = PpdbApplicant::query()
            ->where('ppdb_period_id', $period->id ?? 0)
            ->where('status', '!=', ApplicantStatus::Draft)
            ->with(['period.academicYear', 'track.requirements'])
            ->when(ApplicantStatus::tryFrom($status), fn (Builder $q, ApplicantStatus $s) => $q->where('status', $s))
            ->when($status === 'DAFTAR_ULANG', fn (Builder $q) => $q->where('status', ApplicantStatus::Accepted)->whereNotNull('reregistration_submitted_at'))
            ->when($track !== '', fn (Builder $q) => $q->whereHas('track', fn (Builder $t) => $t->where('public_id', $track)))
            ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w->where('name', 'like', "%{$search}%")->orWhere('registration_number', 'like', "%{$search}%")))
            ->orderByRaw("CASE WHEN status = 'SUBMITTED' THEN 0 ELSE 1 END")
            ->orderBy('submitted_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return Inertia::render('Ppdb::Operator/Applicants/Index', [
            'base' => $base,
            'applicants' => ApplicantResource::collection($applicants),
            'periods' => $picker->options($periods),
            'tracks' => $period ? $period->tracks->map(fn ($t) => ['value' => $t->public_id, 'label' => $t->name])->values() : [],
            'statuses' => [
                ...array_map(fn (ApplicantStatus $s) => $s->toOption(), array_filter(ApplicantStatus::cases(), fn ($s) => $s !== ApplicantStatus::Draft)),
                ['value' => 'DAFTAR_ULANG', 'label' => 'Menunggu konfirmasi daftar ulang'],
            ],
            'filters' => array_filter(['periode' => $period?->public_id, 'status' => $status, 'jalur' => $track, 'q' => $search]),
        ]);
    }

    public function show(Request $request, PpdbApplicant $applicant, NisSuggestion $nis): Response
    {
        $this->authorize('view', $applicant);
        abort_if($applicant->status === ApplicantStatus::Draft, 404);
        $applicant->load(['period.academicYear', 'track.requirements', 'guardians', 'documents.file', 'user']);
        /** @var User $user */
        $user = $request->user();
        /** @var string $base */
        $base = $request->route()?->defaults['base'] ?? '/operator/ppdb';

        return Inertia::render('Ppdb::Operator/Applicants/Show', [
            'base' => $base,
            'applicant' => (new ApplicantResource($applicant))->detail()->resolve($request),
            'account' => ['name' => $applicant->user->name, 'email' => $applicant->user->email],
            'canManage' => $user->can('manage', PpdbApplicant::class),
            'options' => [
                'genders' => array_map(fn (Gender $g) => ['value' => $g->value, 'label' => $g->label()], Gender::cases()),
                'religions' => array_map(fn (Religion $r) => ['value' => $r->value, 'label' => $r->label()], Religion::cases()),
                'relationships' => array_map(fn (GuardianRelationship $r) => ['value' => $r->value, 'label' => $r->label()], GuardianRelationship::cases()),
            ],
            'reregistration' => $applicant->status === ApplicantStatus::Accepted && $user->can('manage', PpdbApplicant::class) ? [
                'suggested_nis' => $nis->next(),
                'entry_date' => $applicant->period->academicYear->start_date->toDateString(),
                'published' => $applicant->period->results_published_at !== null,
                'fee_types' => FeeType::query()->where('is_active', true)->orderBy('name')->get()
                    ->map(fn (FeeType $f) => ['value' => $f->public_id, 'label' => $f->name, 'amount' => $f->default_amount])->values(),
            ] : null,
        ]);
    }

    public function reviewDocument(Request $request, PpdbDocument $document, ReviewDocumentAction $review): RedirectResponse
    {
        $this->authorize('manage', PpdbApplicant::class);
        $data = $request->validate([
            'status' => ['required', Rule::in([DocumentStatus::Valid->value, DocumentStatus::Invalid->value])],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $review->handle($document, DocumentStatus::from($data['status']), $data['note'] ?? null, $user);

        return back()->with('success', 'Status dokumen disimpan.');
    }

    public function verify(Request $request, PpdbApplicant $applicant, TransitionApplicantAction $transition): RedirectResponse
    {
        $this->authorize('manage', PpdbApplicant::class);

        /** @var User $user */
        $user = $request->user();
        $transition->verify($applicant, $user);

        return back()->with('success', 'Pendaftar terverifikasi.');
    }

    public function revise(Request $request, PpdbApplicant $applicant, TransitionApplicantAction $transition): RedirectResponse
    {
        $this->authorize('manage', PpdbApplicant::class);
        $data = $request->validate(['note' => ['required', 'string', 'max:2000']], [], ['note' => 'catatan perbaikan']);

        /** @var User $user */
        $user = $request->user();
        $transition->returnForRevision($applicant, $data['note'], $user);

        return back()->with('success', 'Pendaftaran dikembalikan untuk perbaikan.');
    }

    public function confirmReregistration(Request $request, PpdbApplicant $applicant, ConfirmReregistrationAction $confirm): RedirectResponse
    {
        $this->authorize('manage', PpdbApplicant::class);
        $data = $request->validate([
            'nis' => ['required', 'string', 'max:20', 'regex:/^[0-9A-Za-z.\-]+$/'],
            'entry_date' => ['required', 'date_format:Y-m-d'],
            'fee_type' => ['nullable', 'string', Rule::exists(FeeType::class, 'public_id')],
            'fee_amount' => ['nullable', 'required_with:fee_type', 'integer', 'min:0'],
            'fee_due_date' => ['nullable', 'required_with:fee_type', 'date_format:Y-m-d'],
        ], [], ['nis' => 'NIS', 'entry_date' => 'tanggal masuk', 'fee_amount' => 'nominal tagihan', 'fee_due_date' => 'jatuh tempo']);

        /** @var User $user */
        $user = $request->user();
        $fee = filled($data['fee_type'] ?? null) ? [
            'fee_type' => FeeType::query()->where('public_id', $data['fee_type'])->firstOrFail(),
            'amount' => (int) $data['fee_amount'],
            'due_date' => CarbonImmutable::parse($data['fee_due_date']),
        ] : null;

        $student = $confirm->handle($applicant, $data['nis'], CarbonImmutable::parse($data['entry_date']), $user, $fee);

        return back()->with('success', "Daftar ulang selesai. Siswa {$student->name} (NIS {$student->nis}) dibuat.");
    }
}
