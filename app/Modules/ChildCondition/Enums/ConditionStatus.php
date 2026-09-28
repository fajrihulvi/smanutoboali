<?php

namespace App\Modules\ChildCondition\Enums;

enum ConditionStatus: string
{
    case Submitted = 'SUBMITTED';
    case Received = 'RECEIVED';
    case Reviewed = 'REVIEWED';
    case Responded = 'RESPONDED';
    case Closed = 'CLOSED';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Terkirim',
            self::Received => 'Diterima guru',
            self::Reviewed => 'Ditinjau',
            self::Responded => 'Ditanggapi',
            self::Closed => 'Selesai',
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Submitted => $next === self::Received,
            self::Received => in_array($next, [self::Reviewed, self::Responded, self::Closed], true),
            self::Reviewed => in_array($next, [self::Responded, self::Closed], true),
            self::Responded => in_array($next, [self::Responded, self::Closed], true),
            self::Closed => false,
        };
    }

    public function isOpen(): bool
    {
        return $this !== self::Closed;
    }

    /**
     * @return array{value: string, label: string}
     */
    public function toOption(): array
    {
        return ['value' => $this->value, 'label' => $this->label()];
    }
}
