<?php

namespace App\Modules\Assessment\Enums;

enum Predicate: string
{
    case A = 'A';
    case B = 'B';
    case C = 'C';
    case D = 'D';

    public function label(): string
    {
        return match ($this) {
            self::A => 'Sangat baik',
            self::B => 'Baik',
            self::C => 'Cukup',
            self::D => 'Perlu bimbingan',
        };
    }

    /**
     * Template deskripsi bawaan. Placeholder: {nama}, {mapel}.
     */
    public function defaultTemplate(): string
    {
        return match ($this) {
            self::A => '{nama} menunjukkan penguasaan yang sangat baik dalam {mapel} dan mampu menerapkannya secara mandiri.',
            self::B => '{nama} menunjukkan penguasaan yang baik dalam {mapel}; perlu terus berlatih pada materi yang lebih kompleks.',
            self::C => '{nama} cukup menguasai {mapel}; perlu penguatan pada beberapa materi pokok.',
            self::D => '{nama} perlu bimbingan dalam {mapel}, terutama pada pemahaman konsep dasar.',
        };
    }
}
