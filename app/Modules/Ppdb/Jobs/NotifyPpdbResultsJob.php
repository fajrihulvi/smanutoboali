<?php

namespace App\Modules\Ppdb\Jobs;

use App\Modules\Ppdb\Enums\ApplicantStatus;
use App\Modules\Ppdb\Models\PpdbApplicant;
use App\Modules\Ppdb\Notifications\ApplicantUpdateNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Kirim hasil seleksi ke pendaftar yang sudah diputuskan & belum diberi tahu (per 100).
 */
class NotifyPpdbResultsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(public int $periodId) {}

    public function handle(): void
    {
        PpdbApplicant::query()
            ->where('ppdb_period_id', $this->periodId)
            ->whereIn('status', [ApplicantStatus::Accepted, ApplicantStatus::Rejected])
            ->whereNull('result_notified_at')
            ->with('user')
            ->chunkById(100, function (Collection $applicants) {
                foreach ($applicants as $applicant) {
                    /** @var PpdbApplicant $applicant */
                    $applicant->user->notify(new ApplicantUpdateNotification(
                        $applicant->status === ApplicantStatus::Accepted ? ApplicantUpdateNotification::ACCEPTED : ApplicantUpdateNotification::REJECTED,
                        (string) $applicant->registration_number,
                        (string) $applicant->name,
                    ));
                    $applicant->update(['result_notified_at' => now()]);
                }
            });
    }
}
