<?php

namespace App\Modules\ReportCard\Enums;

/**
 * Alur raport (VIBE_PLANNING §5):
 * DRAFT → SUBMITTED → HOMEROOM_REVIEWED → VALIDATED → APPROVED → PUBLISHED,
 * dengan REVISION dari tahap mana pun setelah SUBMITTED (termasuk PUBLISHED, oleh operator).
 */
enum ReportCardStatus: string
{
    case Draft = 'DRAFT';
    case Submitted = 'SUBMITTED';
    case HomeroomReviewed = 'HOMEROOM_REVIEWED';
    case Validated = 'VALIDATED';
    case Approved = 'APPROVED';
    case Published = 'PUBLISHED';
    case Revision = 'REVISION';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Submitted => 'Diajukan',
            self::HomeroomReviewed => 'Direview wali kelas',
            self::Validated => 'Divalidasi operator',
            self::Approved => 'Disetujui kepala sekolah',
            self::Published => 'Terbit',
            self::Revision => 'Perlu revisi',
        };
    }

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Submitted],
            self::Submitted => [self::HomeroomReviewed, self::Revision],
            self::HomeroomReviewed => [self::Validated, self::Revision],
            self::Validated => [self::Approved, self::Revision],
            self::Approved => [self::Published, self::Revision],
            self::Published => [self::Revision],
            self::Revision => [self::Submitted],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    /**
     * Isi raport (catatan, ekskul, prestasi) masih boleh diubah wali kelas.
     */
    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Revision, self::Submitted], true);
    }

    /**
     * Detail nilai boleh di-generate ulang.
     */
    public function canRegenerate(): bool
    {
        return $this === self::Draft || $this === self::Revision;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $s) => ['value' => $s->value, 'label' => $s->label()], self::cases());
    }
}
