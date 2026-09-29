<?php

namespace App\Modules\Ppdb\Actions;

use App\Models\User;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\Ppdb\Enums\ApplicantStatus;
use App\Modules\Ppdb\Enums\DocumentStatus;
use App\Modules\Ppdb\Models\PpdbApplicant;
use App\Modules\Ppdb\Models\PpdbDocument;
use App\Modules\Ppdb\Models\PpdbDocumentRequirement;
use App\Modules\Ppdb\Notifications\ApplicantUpdateNotification;
use Illuminate\Validation\ValidationException;

/**
 * Verifikasi pendaftar atau kembalikan untuk perbaikan (pendaftar diberi tahu).
 */
class TransitionApplicantAction
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @throws ValidationException
     */
    public function verify(PpdbApplicant $applicant, User $actor): PpdbApplicant
    {
        $this->guard($applicant, ApplicantStatus::Verified);
        // Muat ulang: relasi dokumen bisa sudah dimuat sebelum statusnya diperiksa.
        $applicant->load(['track.requirements', 'documents']);

        $valid = $applicant->documents->filter(fn (PpdbDocument $d) => $d->status === DocumentStatus::Valid)->pluck('ppdb_document_requirement_id')->all();
        $missing = $applicant->track->requirements
            ->filter(fn (PpdbDocumentRequirement $r) => $r->is_required && ! in_array($r->id, $valid, true))
            ->pluck('name');

        if ($missing->isNotEmpty()) {
            throw ValidationException::withMessages(['status' => 'Tandai valid dulu dokumen wajib: '.$missing->implode(', ').'.']);
        }

        $applicant->update(['status' => ApplicantStatus::Verified, 'verified_at' => now(), 'revision_note' => null]);
        $this->finish($applicant, $actor, 'verified', 'Memverifikasi', ApplicantUpdateNotification::VERIFIED);

        return $applicant;
    }

    /**
     * @throws ValidationException
     */
    public function returnForRevision(PpdbApplicant $applicant, string $note, User $actor): PpdbApplicant
    {
        $this->guard($applicant, ApplicantStatus::NeedsRevision);

        $applicant->update(['status' => ApplicantStatus::NeedsRevision, 'revision_note' => $note, 'verified_at' => null, 'score' => null, 'proposal' => null]);
        $this->finish($applicant, $actor, 'returned', 'Mengembalikan untuk perbaikan', ApplicantUpdateNotification::REVISION, $note);

        return $applicant;
    }

    /**
     * @throws ValidationException
     */
    private function guard(PpdbApplicant $applicant, ApplicantStatus $next): void
    {
        if (! $applicant->status->canTransitionTo($next)) {
            throw ValidationException::withMessages(['status' => "Pendaftar berstatus {$applicant->status->label()} tidak bisa diubah menjadi {$next->label()}."]);
        }
    }

    private function finish(PpdbApplicant $applicant, User $actor, string $event, string $verb, string $kind, ?string $note = null): void
    {
        $this->audit->record('ppdb', $event, "{$verb} pendaftar {$applicant->registration_number} ({$applicant->name})", $applicant, causer: $actor);
        $applicant->loadMissing('user');
        $applicant->user->notify(new ApplicantUpdateNotification($kind, (string) $applicant->registration_number, (string) $applicant->name, $note));
    }
}
