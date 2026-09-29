<?php

namespace App\Modules\Ppdb\Services;

use App\Modules\Ppdb\Enums\ApplicantStatus;
use App\Modules\Ppdb\Models\PpdbApplicant;
use App\Modules\Ppdb\Models\PpdbTrack;
use Illuminate\Database\Eloquent\Collection;

/**
 * Peringkat seleksi per jalur: skor tertinggi dulu; skor sama → yang mengirim lebih awal.
 * Pendaftar tanpa skor di urutan paling bawah.
 */
class SelectionRanking
{
    /**
     * @return Collection<int, PpdbApplicant>
     */
    public function forTrack(PpdbTrack $track): Collection
    {
        return PpdbApplicant::query()
            ->where('ppdb_track_id', $track->id)
            ->whereIn('status', [ApplicantStatus::Verified, ApplicantStatus::InSelection, ApplicantStatus::Accepted, ApplicantStatus::Rejected, ApplicantStatus::ReRegistered])
            ->orderByRaw('CASE WHEN score IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('score')
            ->orderBy('submitted_at')
            ->orderBy('id')
            ->get();
    }
}
