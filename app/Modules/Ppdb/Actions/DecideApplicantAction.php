<?php

namespace App\Modules\Ppdb\Actions;

use App\Models\User;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\Ppdb\Enums\ApplicantStatus;
use App\Modules\Ppdb\Models\PpdbApplicant;
use App\Modules\Ppdb\Models\PpdbTrack;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Kepala sekolah memutuskan: satuan (terima/tolak) atau massal (setujui semua usulan satu jalur).
 * Pendaftar belum diberi tahu sampai hasil diumumkan operator.
 */
class DecideApplicantAction
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @throws ValidationException
     */
    public function one(PpdbApplicant $applicant, ApplicantStatus $decision, User $principal): PpdbApplicant
    {
        if (! in_array($decision, [ApplicantStatus::Accepted, ApplicantStatus::Rejected], true) || ! $applicant->status->canTransitionTo($decision)) {
            throw ValidationException::withMessages(['decision' => 'Pendaftar ini belum masuk tahap seleksi atau sudah diputuskan.']);
        }

        $applicant->update(['status' => $decision, 'decided_at' => now(), 'decided_by' => $principal->id]);
        $this->audit->record('ppdb', 'decided', "Memutuskan {$applicant->registration_number} ({$applicant->name}): {$decision->label()}", $applicant, causer: $principal);

        return $applicant;
    }

    /**
     * Setujui semua usulan pada jalur. Mengembalikan jumlah yang diputuskan.
     */
    public function approveProposals(PpdbTrack $track, User $principal): int
    {
        $applicants = PpdbApplicant::query()
            ->where('ppdb_track_id', $track->id)
            ->where('status', ApplicantStatus::InSelection)
            ->whereNotNull('proposal')
            ->get();

        DB::transaction(function () use ($applicants, $principal) {
            foreach ($applicants as $applicant) {
                /** @var PpdbApplicant $applicant */
                $applicant->update(['status' => $applicant->proposal?->decision(), 'decided_at' => now(), 'decided_by' => $principal->id]);
            }
        });

        $this->audit->record('ppdb', 'decided_bulk', "Menyetujui {$applicants->count()} usulan seleksi jalur {$track->name}", $track, causer: $principal);

        return $applicants->count();
    }
}
