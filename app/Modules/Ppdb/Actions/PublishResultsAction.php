<?php

namespace App\Modules\Ppdb\Actions;

use App\Models\User;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\Ppdb\Enums\ApplicantStatus;
use App\Modules\Ppdb\Jobs\NotifyPpdbResultsJob;
use App\Modules\Ppdb\Models\PpdbApplicant;
use App\Modules\Ppdb\Models\PpdbPeriod;
use Illuminate\Validation\ValidationException;

/**
 * Umumkan hasil seleksi periode: hasil terlihat di portal & cek hasil publik, lalu pendaftar yang
 * sudah diputuskan dan belum diberi tahu dikirimi email + in-app (Job).
 */
class PublishResultsAction
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @throws ValidationException
     */
    public function handle(PpdbPeriod $period, User $actor): int
    {
        $pending = PpdbApplicant::query()
            ->where('ppdb_period_id', $period->id)
            ->whereIn('status', [ApplicantStatus::Accepted, ApplicantStatus::Rejected])
            ->whereNull('result_notified_at')
            ->count();

        if ($pending === 0 && $period->results_published_at !== null) {
            throw ValidationException::withMessages(['period' => 'Tidak ada keputusan baru untuk diumumkan.']);
        }

        if ($pending === 0) {
            throw ValidationException::withMessages(['period' => 'Belum ada keputusan kepala sekolah untuk diumumkan.']);
        }

        $period->update(['results_published_at' => $period->results_published_at ?? now()]);
        $this->audit->record('ppdb', 'published', "Mengumumkan hasil seleksi {$period->name} ({$pending} pendaftar)", $period, causer: $actor);

        NotifyPpdbResultsJob::dispatch($period->id);

        return $pending;
    }
}
