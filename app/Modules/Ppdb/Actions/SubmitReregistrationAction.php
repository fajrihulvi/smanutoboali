<?php

namespace App\Modules\Ppdb\Actions;

use App\Models\User;
use App\Modules\Access\Enums\Permission;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\Ppdb\Models\PpdbApplicant;
use App\Modules\Ppdb\Notifications\ApplicationSubmittedNotification;
use Illuminate\Support\Facades\Notification;

/**
 * Pendaftar yang diterima mengonfirmasi/melengkapi data daftar ulang → menunggu konfirmasi operator.
 */
class SubmitReregistrationAction
{
    public function __construct(private SaveApplicationDraftAction $save, private AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $data  alamat & data orang tua (tervalidasi)
     */
    public function handle(PpdbApplicant $applicant, array $data): PpdbApplicant
    {
        $this->save->handle($applicant, $data);
        $applicant->update(['reregistration_submitted_at' => now()]);

        $this->audit->record('ppdb', 'reregistration_submitted', "Mengirim daftar ulang {$applicant->registration_number} ({$applicant->name})", $applicant);

        $operators = User::permission(Permission::PpdbManage->value)->where('is_active', true)->get();
        Notification::send($operators, new ApplicationSubmittedNotification((string) $applicant->registration_number, 'Daftar ulang: '.$applicant->name, false));

        return $applicant;
    }
}
