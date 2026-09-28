<?php

namespace App\Modules\Ppdb\Enums;

enum ApplicantStatus: string
{
    case Draft = 'DRAFT';
    case Submitted = 'SUBMITTED';
    case NeedsRevision = 'NEEDS_REVISION';
    case Verified = 'VERIFIED';
    case InSelection = 'IN_SELECTION';
    case Accepted = 'ACCEPTED';
    case Rejected = 'REJECTED';
    case ReRegistered = 'RE_REGISTERED';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Submitted => 'Terkirim',
            self::NeedsRevision => 'Perlu perbaikan',
            self::Verified => 'Terverifikasi',
            self::InSelection => 'Seleksi',
            self::Accepted => 'Diterima',
            self::Rejected => 'Tidak diterima',
            self::ReRegistered => 'Daftar ulang selesai',
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Draft => $next === self::Submitted,
            self::Submitted => in_array($next, [self::NeedsRevision, self::Verified], true),
            self::NeedsRevision => $next === self::Submitted,
            self::Verified => in_array($next, [self::InSelection, self::NeedsRevision], true),
            self::InSelection => in_array($next, [self::Accepted, self::Rejected], true),
            self::Accepted => $next === self::ReRegistered,
            self::Rejected, self::ReRegistered => false,
        };
    }

    /**
     * Formulir boleh diubah pendaftar.
     */
    public function isEditable(): bool
    {
        return $this === self::Draft || $this === self::NeedsRevision;
    }

    /**
     * Urutan tahap untuk stepper (tahap akhir berbeda untuk diterima/ditolak).
     */
    public function step(): int
    {
        return match ($this) {
            self::Draft => 0,
            self::Submitted, self::NeedsRevision => 1,
            self::Verified => 2,
            self::InSelection => 3,
            self::Accepted, self::Rejected => 4,
            self::ReRegistered => 5,
        };
    }

    /**
     * @return array{value: string, label: string}
     */
    public function toOption(): array
    {
        return ['value' => $this->value, 'label' => $this->label()];
    }
}
