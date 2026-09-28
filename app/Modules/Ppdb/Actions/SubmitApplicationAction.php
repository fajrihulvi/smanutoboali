<?php

namespace App\Modules\Ppdb\Actions;

use App\Models\User;
use App\Modules\Access\Enums\Permission;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\Ppdb\Enums\ApplicantStatus;
use App\Modules\Ppdb\Models\PpdbApplicant;
use App\Modules\Ppdb\Models\PpdbPeriod;
use App\Modules\Ppdb\Notifications\ApplicationSubmittedNotification;
use App\Modules\Ppdb\Services\ApplicationRules;
use App\Modules\Ppdb\Services\RegistrationNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Kirim pendaftaran (pertama kali atau setelah perbaikan): cek kelengkapan, beri nomor
 * pendaftaran (sekali), status SUBMITTED, operator PPDB diberi tahu.
 */
class SubmitApplicationAction
{
    public function __construct(private ApplicationRules $rules, private RegistrationNumber $numbers, private AuditLogger $audit) {}

    /**
     * @throws ValidationException
     */
    public function handle(PpdbApplicant $applicant): PpdbApplicant
    {
        if (! $applicant->status->canTransitionTo(ApplicantStatus::Submitted)) {
            throw ValidationException::withMessages(['status' => "Pendaftaran berstatus {$applicant->status->label()} tidak bisa dikirim."]);
        }

        $applicant->loadMissing('period');

        if ($applicant->status === ApplicantStatus::Draft && ! $applicant->period->isOpen()) {
            throw ValidationException::withMessages(['status' => 'Periode pendaftaran sudah ditutup.']);
        }

        $this->rules->assertComplete($applicant);
        $revision = $applicant->status === ApplicantStatus::NeedsRevision;

        DB::transaction(function () use ($applicant) {
            $period = PpdbPeriod::query()->lockForUpdate()->findOrFail($applicant->ppdb_period_id);

            $applicant->update([
                'registration_number' => $applicant->registration_number ?? $this->numbers->next($period),
                'status' => ApplicantStatus::Submitted,
                'submitted_at' => now(),
            ]);
        });

        $this->audit->record('ppdb', $revision ? 'resubmitted' : 'submitted',
            ($revision ? 'Mengirim perbaikan' : 'Mengirim pendaftaran')." {$applicant->registration_number} ({$applicant->name})", $applicant);

        $operators = User::permission(Permission::PpdbManage->value)->where('is_active', true)->get();
        Notification::send($operators, new ApplicationSubmittedNotification((string) $applicant->registration_number, (string) $applicant->name, $revision));

        return $applicant;
    }
}
