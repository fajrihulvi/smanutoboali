<?php

namespace App\Modules\Ppdb\Services;

use App\Modules\People\Models\Student;

/**
 * Saran NIS berikutnya: NIS numerik terbesar + 1 (panjang dipertahankan).
 */
class NisSuggestion
{
    public function next(): string
    {
        $max = Student::withTrashed()
            ->pluck('nis')
            ->filter(fn ($nis) => ctype_digit((string) $nis))
            ->sortBy(fn ($nis) => [strlen((string) $nis), (string) $nis])
            ->last();

        if ($max === null) {
            return now()->format('Y').'0001';
        }

        // NIS ≤ 18 digit muat di integer 64-bit (tanpa ekstensi bcmath yang belum tentu ada di hosting).
        $max = (string) $max;
        $next = strlen($max) <= 18 ? (string) ((int) $max + 1) : $max.'1';

        return str_pad($next, strlen($max), '0', STR_PAD_LEFT);
    }
}
