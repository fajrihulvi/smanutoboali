<?php

namespace App\Modules\Ppdb\Services;

use App\Modules\Ppdb\Enums\ApplicantStatus;
use App\Modules\Ppdb\Models\PpdbApplicant;
use App\Modules\Ppdb\Models\PpdbPeriod;
use App\Modules\Ppdb\Models\PpdbTrack;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Statistik PPDB per periode: per status, per jalur (vs kuota), pendaftar harian 30 hari.
 */
class PpdbDashboard
{
    /**
     * @return array<string, mixed>
     */
    public function for(PpdbPeriod $period, CarbonImmutable $today): array
    {
        $byStatus = PpdbApplicant::query()
            ->where('ppdb_period_id', $period->id)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->toBase()
            ->pluck('total', 'status');

        $period->loadMissing('tracks');
        $byTrack = PpdbApplicant::query()
            ->where('ppdb_period_id', $period->id)
            ->where('status', '!=', ApplicantStatus::Draft)
            ->select('ppdb_track_id', 'status', DB::raw('COUNT(*) as total'))
            ->groupBy('ppdb_track_id', 'status')
            // Query mentah: status tetap string (bukan enum hasil cast) agar bisa dibandingkan.
            ->toBase()
            ->get()
            ->groupBy('ppdb_track_id');

        $from = $today->subDays(29);
        $daily = PpdbApplicant::query()
            ->where('ppdb_period_id', $period->id)
            ->whereNotNull('submitted_at')
            ->where('submitted_at', '>=', $from->startOfDay())
            ->selectRaw('SUBSTR(submitted_at, 1, 10) as day, COUNT(*) as total')
            ->groupByRaw('SUBSTR(submitted_at, 1, 10)')
            ->pluck('total', 'day');

        return [
            'statuses' => array_map(fn (ApplicantStatus $s) => [...$s->toOption(), 'total' => (int) ($byStatus[$s->value] ?? 0)], ApplicantStatus::cases()),
            'tracks' => $period->tracks->map(function (PpdbTrack $track) use ($byTrack) {
                $rows = $byTrack->get($track->id, collect());
                $count = fn (array $statuses) => (int) $rows->whereIn('status', array_map(fn ($s) => $s->value, $statuses))->sum('total');

                return [
                    'public_id' => $track->public_id,
                    'name' => $track->name,
                    'quota' => $track->quota,
                    'registered' => (int) $rows->sum('total'),
                    'verified' => $count([ApplicantStatus::Verified, ApplicantStatus::InSelection, ApplicantStatus::Accepted, ApplicantStatus::Rejected, ApplicantStatus::ReRegistered]),
                    'accepted' => $count([ApplicantStatus::Accepted, ApplicantStatus::ReRegistered]),
                ];
            })->values()->all(),
            'daily' => collect(range(0, 29))->map(fn (int $i) => [
                'date' => $from->addDays($i)->toDateString(),
                'total' => (int) ($daily[$from->addDays($i)->toDateString()] ?? 0),
            ])->values()->all(),
        ];
    }
}
