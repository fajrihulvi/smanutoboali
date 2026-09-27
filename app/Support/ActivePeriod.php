<?php

namespace App\Support;

/**
 * Helper periode akademik aktif (tahun ajaran + semester).
 *
 * TODO(S08): ambil dari tabel academic_years & semesters (is_active),
 * cache hasilnya, dan bagikan ke Inertia shared props.
 */
class ActivePeriod
{
    /**
     * Tahun ajaran aktif. Mengembalikan null sampai modul School dibuat (S08).
     */
    public function academicYear(): ?object
    {
        return null;
    }

    /**
     * Semester aktif. Mengembalikan null sampai modul School dibuat (S08).
     */
    public function semester(): ?object
    {
        return null;
    }

    public function academicYearId(): ?int
    {
        return null;
    }
}
