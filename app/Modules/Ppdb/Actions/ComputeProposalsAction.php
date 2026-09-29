<?php

namespace App\Modules\Ppdb\Actions;

use App\Models\User;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\Ppdb\Enums\ApplicantStatus;
use App\Modules\Ppdb\Enums\SelectionProposal;
use App\Modules\Ppdb\Models\PpdbTrack;
use App\Modules\Ppdb\Services\SelectionRanking;
use Illuminate\Support\Facades\DB;

/**
 * Usulan otomatis per jalur: peringkat teratas (sisa kuota setelah yang sudah diterima) → DITERIMA,
 * sisanya yang punya skor → TIDAK. Pendaftar tanpa skor tidak diusulkan.
 *
 * @return array{accepted: int, rejected: int, unscored: int}
 */
class ComputeProposalsAction
{
    public function __construct(private SelectionRanking $ranking, private AuditLogger $audit) {}

    /**
     * @return array{accepted: int, rejected: int, unscored: int}
     */
    public function handle(PpdbTrack $track, User $actor): array
    {
        $ranked = $this->ranking->forTrack($track);
        $alreadyAccepted = $ranked->filter(fn ($a) => in_array($a->status, [ApplicantStatus::Accepted, ApplicantStatus::ReRegistered], true))->count();
        $open = max(0, $track->quota - $alreadyAccepted);
        $counts = ['accepted' => 0, 'rejected' => 0, 'unscored' => 0];

        DB::transaction(function () use ($ranked, &$open, &$counts) {
            foreach ($ranked as $applicant) {
                if (! in_array($applicant->status, [ApplicantStatus::Verified, ApplicantStatus::InSelection], true)) {
                    continue;
                }

                if ($applicant->score === null) {
                    $counts['unscored']++;

                    continue;
                }

                $proposal = $open > 0 ? SelectionProposal::Accept : SelectionProposal::Reject;
                $open -= $proposal === SelectionProposal::Accept ? 1 : 0;
                $counts[$proposal === SelectionProposal::Accept ? 'accepted' : 'rejected']++;
                $applicant->update(['proposal' => $proposal, 'status' => ApplicantStatus::InSelection]);
            }
        });

        $this->audit->record('ppdb', 'proposed', "Menghitung usulan seleksi jalur {$track->name} ({$counts['accepted']} diterima, {$counts['rejected']} tidak)", $track, causer: $actor);

        return $counts;
    }
}
