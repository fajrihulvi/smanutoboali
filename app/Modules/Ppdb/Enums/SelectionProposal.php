<?php

namespace App\Modules\Ppdb\Enums;

/**
 * Usulan operator untuk keputusan kepala sekolah.
 */
enum SelectionProposal: string
{
    case Accept = 'DITERIMA';
    case Reject = 'TIDAK';

    public function label(): string
    {
        return match ($this) {
            self::Accept => 'Usul diterima',
            self::Reject => 'Usul tidak diterima',
        };
    }

    public function decision(): ApplicantStatus
    {
        return $this === self::Accept ? ApplicantStatus::Accepted : ApplicantStatus::Rejected;
    }
}
