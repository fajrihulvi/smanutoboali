<?php

namespace App\Modules\Ppdb\Services;

use App\Modules\Ppdb\Models\PpdbApplicant;
use App\Modules\Ppdb\Models\PpdbPeriod;

/**
 * Nomor pendaftaran PPDB-<tahun ajaran tujuan>-0001, berurutan per tahun.
 * Dipanggil di dalam transaksi setelah baris periode dikunci (lockForUpdate).
 */
class RegistrationNumber
{
    public function next(PpdbPeriod $period): string
    {
        $period->loadMissing('academicYear');
        $prefix = 'PPDB-'.substr($period->academicYear->name, 0, 4).'-';

        $last = PpdbApplicant::withTrashed()
            ->where('registration_number', 'like', $prefix.'%')
            ->orderByDesc('registration_number')
            ->value('registration_number');

        $number = $last ? ((int) substr((string) $last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $number, 4, '0', STR_PAD_LEFT);
    }
}
