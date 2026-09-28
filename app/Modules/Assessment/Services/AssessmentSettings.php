<?php

namespace App\Modules\Assessment\Services;

use App\Modules\Assessment\Enums\Predicate;
use App\Modules\Core\Enums\SettingKey;
use App\Modules\Core\Services\SettingsService;

/**
 * KKM & batas predikat (satu aturan sekolah).
 */
class AssessmentSettings
{
    public function __construct(private SettingsService $settings) {}

    public function kkm(): int
    {
        return (int) $this->settings->get(SettingKey::GradeKkm);
    }

    /**
     * @return array{A: int, B: int, C: int}
     */
    public function thresholds(): array
    {
        return [
            'A' => (int) $this->settings->get(SettingKey::GradePredicateA),
            'B' => (int) $this->settings->get(SettingKey::GradePredicateB),
            'C' => (int) $this->settings->get(SettingKey::GradePredicateC),
        ];
    }

    public function predicateFor(int $score): Predicate
    {
        $t = $this->thresholds();

        return match (true) {
            $score >= $t['A'] => Predicate::A,
            $score >= $t['B'] => Predicate::B,
            $score >= $t['C'] => Predicate::C,
            default => Predicate::D,
        };
    }
}
