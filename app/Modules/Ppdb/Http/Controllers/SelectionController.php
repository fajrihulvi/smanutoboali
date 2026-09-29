<?php

namespace App\Modules\Ppdb\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Ppdb\Actions\ComputeProposalsAction;
use App\Modules\Ppdb\Actions\DecideApplicantAction;
use App\Modules\Ppdb\Actions\PublishResultsAction;
use App\Modules\Ppdb\Actions\ScoreApplicantAction;
use App\Modules\Ppdb\Enums\ApplicantStatus;
use App\Modules\Ppdb\Enums\SelectionProposal;
use App\Modules\Ppdb\Http\Resources\ApplicantResource;
use App\Modules\Ppdb\Models\PpdbApplicant;
use App\Modules\Ppdb\Models\PpdbPeriod;
use App\Modules\Ppdb\Models\PpdbTrack;
use App\Modules\Ppdb\Services\PeriodPicker;
use App\Modules\Ppdb\Services\SelectionRanking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Seleksi per jalur: operator memberi skor & usulan dan mengumumkan hasil;
 * kepala sekolah memutuskan (satuan/massal). Satu halaman, aksi sesuai permission.
 */
class SelectionController extends Controller
{
    public function index(Request $request, PeriodPicker $picker, SelectionRanking $ranking): Response
    {
        $periods = $picker->all();
        $period = $picker->selected($request, $periods);
        $track = $period?->tracks->firstWhere('public_id', $request->string('jalur')->value()) ?? $period?->tracks->first();
        /** @var User $user */
        $user = $request->user();
        /** @var string $base */
        $base = $request->route()?->defaults['base'] ?? '/operator/ppdb/seleksi';

        $applicants = $track ? $ranking->forTrack($track)->load(['period.academicYear', 'track.requirements']) : collect();
        $pendingPublish = $period ? PpdbApplicant::query()->where('ppdb_period_id', $period->id)
            ->whereIn('status', [ApplicantStatus::Accepted, ApplicantStatus::Rejected])->whereNull('result_notified_at')->count() : 0;

        return Inertia::render('Ppdb::Shared/Selection', [
            'base' => $base,
            'periods' => $picker->options($periods),
            'period' => $period ? ['public_id' => $period->public_id, 'name' => $period->name, 'results_published_at' => $period->results_published_at?->toIso8601String()] : null,
            'tracks' => $period ? $period->tracks->map(fn (PpdbTrack $t) => ['value' => $t->public_id, 'label' => $t->name, 'quota' => $t->quota])->values() : [],
            'track' => $track ? ['public_id' => $track->public_id, 'name' => $track->name, 'quota' => $track->quota] : null,
            'applicants' => ApplicantResource::collection($applicants)->resolve($request),
            'pendingPublish' => $pendingPublish,
            'can' => [
                'manage' => $user->can('manage', PpdbApplicant::class),
                'decide' => $user->can('decide', PpdbApplicant::class),
            ],
        ]);
    }

    public function score(Request $request, PpdbApplicant $applicant, ScoreApplicantAction $score): RedirectResponse
    {
        $this->authorize('manage', PpdbApplicant::class);
        $data = $request->validate([
            'score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'note' => ['nullable', 'string', 'max:255'],
            'proposal' => ['nullable', Rule::enum(SelectionProposal::class)],
        ], [], ['score' => 'skor', 'note' => 'catatan', 'proposal' => 'usulan']);

        $score->handle($applicant, isset($data['score']) ? (float) $data['score'] : null, $data['note'] ?? null,
            filled($data['proposal'] ?? null) ? SelectionProposal::from($data['proposal']) : null);

        return back();
    }

    public function propose(Request $request, PpdbTrack $track, ComputeProposalsAction $compute): RedirectResponse
    {
        $this->authorize('manage', PpdbApplicant::class);

        /** @var User $user */
        $user = $request->user();
        $counts = $compute->handle($track, $user);

        return back()->with('success', "Usulan dihitung: {$counts['accepted']} diterima, {$counts['rejected']} tidak".($counts['unscored'] > 0 ? ", {$counts['unscored']} belum ada skor" : '').'.');
    }

    public function publish(Request $request, PpdbPeriod $period, PublishResultsAction $publish): RedirectResponse
    {
        $this->authorize('manage', PpdbApplicant::class);

        /** @var User $user */
        $user = $request->user();
        $count = $publish->handle($period, $user);

        return back()->with('success', "Hasil diumumkan untuk {$count} pendaftar. Notifikasi sedang dikirim.");
    }

    public function decide(Request $request, PpdbApplicant $applicant, DecideApplicantAction $decide): RedirectResponse
    {
        $this->authorize('decide', PpdbApplicant::class);
        $data = $request->validate(['decision' => ['required', Rule::in([ApplicantStatus::Accepted->value, ApplicantStatus::Rejected->value])]]);

        /** @var User $user */
        $user = $request->user();
        $decide->one($applicant, ApplicantStatus::from($data['decision']), $user);

        return back();
    }

    public function approve(Request $request, PpdbTrack $track, DecideApplicantAction $decide): RedirectResponse
    {
        $this->authorize('decide', PpdbApplicant::class);

        /** @var User $user */
        $user = $request->user();
        $count = $decide->approveProposals($track, $user);

        return back()->with('success', "{$count} usulan disetujui.");
    }
}
