<?php

namespace App\Modules\Ppdb\Actions;

use App\Models\User;
use App\Modules\Ppdb\Enums\ApplicantStatus;
use App\Modules\Ppdb\Models\PpdbApplicant;
use App\Modules\Ppdb\Models\PpdbTrack;
use Illuminate\Validation\ValidationException;

/**
 * Buat draf pendaftaran baru untuk jalur pada periode yang sedang dibuka.
 */
class StartApplicationAction
{
    /**
     * @throws ValidationException
     */
    public function handle(User $user, PpdbTrack $track): PpdbApplicant
    {
        $track->loadMissing('period');

        if (! $track->period->isOpen()) {
            throw ValidationException::withMessages(['track' => 'Pendaftaran periode ini sedang tidak dibuka.']);
        }

        return PpdbApplicant::query()->create([
            'ppdb_period_id' => $track->ppdb_period_id,
            'ppdb_track_id' => $track->id,
            'user_id' => $user->id,
            'status' => ApplicantStatus::Draft,
        ]);
    }
}
