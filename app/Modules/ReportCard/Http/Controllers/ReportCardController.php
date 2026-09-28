<?php

namespace App\Modules\ReportCard\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Assessment\Services\SemesterPicker;
use App\Modules\Core\Services\FileUrlGenerator;
use App\Modules\ReportCard\Actions\TransitionReportCardAction;
use App\Modules\ReportCard\Enums\ReportCardStatus;
use App\Modules\ReportCard\Models\ReportCard;
use App\Modules\ReportCard\Models\ReportCardExport;
use App\Modules\ReportCard\Services\ReportCardAccess;
use App\Modules\ReportCard\Services\ReportCardPdf;
use App\Modules\ReportCard\Services\ReportCardPresenter;
use App\Modules\ReportCard\Services\SubjectConfirmations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman & aksi raport yang sama untuk semua role (wali kelas, operator, kepala sekolah, orang tua).
 * `$base` (prefix URL portal) diberikan lewat route defaults.
 */
class ReportCardController extends Controller
{
    public function __construct(
        private ReportCardAccess $access,
        private ReportCardPresenter $presenter,
        private SemesterPicker $semesters,
    ) {}

    /**
     * Daftar raport satu rombel (wali kelas / operator / kepala sekolah).
     */
    public function classroom(Request $request, Classroom $classroom, string $base, FileUrlGenerator $urls): Response
    {
        $user = $this->user($request);
        abort_unless($this->access->isHomeroom($user, $classroom) || $this->access->isOperator($user) || $this->access->isPrincipal($user), 403);

        $semester = $this->semesters->selected($request);
        abort_if($semester === null, 404);

        $cards = ReportCard::query()
            ->where('classroom_id', $classroom->id)
            ->where('semester_id', $semester->id)
            ->with('student:id,public_id,nis,name')
            ->get()
            ->sortBy(fn (ReportCard $c) => $c->student->name)
            ->values();

        $exports = $this->access->isOperator($user)
            ? ReportCardExport::query()->where('classroom_id', $classroom->id)->where('semester_id', $semester->id)
                ->where('created_at', '>', now()->subDays(ReportCardExport::KEEP_DAYS))->with('file')->latest()->limit(3)->get()
                ->map(fn (ReportCardExport $e) => [
                    'public_id' => $e->public_id,
                    'status' => $e->status,
                    'total' => $e->total,
                    'error' => $e->error,
                    'created_at' => $e->created_at?->toIso8601String(),
                    'url' => $e->file ? $urls->url($e->file) : null,
                ])->values()
            : [];

        return Inertia::render('ReportCard::Shared/Classroom', [
            'base' => $base,
            'role' => $this->roleOf($user, $classroom),
            'classroom' => ['public_id' => $classroom->public_id, 'name' => $classroom->name],
            'semester' => $semester->public_id,
            'semesters' => $this->semesters->toOptions(),
            'cards' => $cards->map(fn (ReportCard $c) => $this->presenter->summary($c)),
            'counts' => $this->presenter->counts($cards),
            'confirmations' => $this->presenter->confirmations($classroom, $semester),
            'exports' => $exports,
        ]);
    }

    public function show(Request $request, ReportCard $card, string $base): Response
    {
        $this->authorize('view', $card);

        return Inertia::render('ReportCard::Shared/Show', [
            'base' => $base,
            'card' => $this->presenter->detail($card, $this->user($request)),
        ]);
    }

    public function pdf(Request $request, ReportCard $card, ReportCardPdf $pdf): HttpResponse
    {
        $this->authorize('view', $card);

        return response($pdf->render($card), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($request->boolean('unduh') ? 'attachment' : 'inline').'; filename="'.$pdf->filename($card).'"',
        ]);
    }

    public function transition(Request $request, ReportCard $card, TransitionReportCardAction $transition, SubjectConfirmations $confirmations): RedirectResponse
    {
        ['target' => $target, 'note' => $note] = $this->validateTransition($request);
        $user = $this->user($request);

        if ($card->status === ReportCardStatus::Revision && $target === ReportCardStatus::Submitted) {
            abort_unless($this->access->canPerform($user, $card, $target), 403);
            $confirmations->resubmit($card, $user);
        } else {
            $transition->handle($card, $target, $user, $note);
        }

        return back()->with('success', "Raport {$card->student->name}: {$target->label()}.");
    }

    /**
     * Transisi massal satu rombel: raport yang tidak memenuhi syarat dilewati dan dilaporkan.
     */
    public function bulkTransition(Request $request, Classroom $classroom, TransitionReportCardAction $transition): RedirectResponse
    {
        ['target' => $target, 'note' => $note] = $this->validateTransition($request);
        $request->validate(['cards' => ['required', 'array', 'min:1'], 'cards.*' => ['string']]);
        $user = $this->user($request);

        $cards = ReportCard::query()
            ->where('classroom_id', $classroom->id)
            ->whereIn('public_id', $request->input('cards'))
            ->with(['student:id,name', 'classroom'])
            ->get();

        $done = 0;
        $skipped = 0;

        DB::transaction(function () use ($cards, $target, $note, $user, $transition, &$done, &$skipped) {
            foreach ($cards as $card) {
                if (! $card->status->canTransitionTo($target) || ! $this->access->canPerform($user, $card, $target)) {
                    $skipped++;

                    continue;
                }

                $transition->handle($card, $target, $user, $note);
                $done++;
            }
        });

        return back()->with(
            $done > 0 ? 'success' : 'error',
            $done > 0
                ? "{$done} raport: {$target->label()}.".($skipped > 0 ? " {$skipped} dilewati karena statusnya tidak sesuai." : '')
                : 'Tidak ada raport yang bisa diproses pada tahap ini.',
        );
    }

    /**
     * @return array{target: ReportCardStatus, note: string|null}
     */
    private function validateTransition(Request $request): array
    {
        $validated = $request->validate([
            'target' => ['required', Rule::enum(ReportCardStatus::class)],
            'note' => ['nullable', 'string', 'max:1000'],
        ], [], ['target' => 'tahap', 'note' => 'catatan']);

        return ['target' => ReportCardStatus::from($validated['target']), 'note' => $validated['note'] ?? null];
    }

    private function roleOf(User $user, Classroom $classroom): string
    {
        return match (true) {
            $this->access->isOperator($user) => 'operator',
            $this->access->isPrincipal($user) => 'principal',
            default => 'homeroom',
        };
    }

    private function user(Request $request): User
    {
        /** @var User */
        return $request->user();
    }
}
