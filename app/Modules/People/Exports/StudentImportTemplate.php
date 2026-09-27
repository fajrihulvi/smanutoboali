<?php

namespace App\Modules\People\Exports;

use App\Modules\People\Services\StudentImportRowProcessor;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

/**
 * Template import siswa + orang tua (baris judul + satu contoh).
 */
class StudentImportTemplate implements FromArray, ShouldAutoSize, WithColumnFormatting, WithHeadings
{
    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return StudentImportRowProcessor::COLUMNS;
    }

    /**
     * @return list<list<string>>
     */
    public function array(): array
    {
        return [[
            '20260001', '0112233445', 'Aisyah Putri', 'P', 'Pangkalpinang', '2013-05-17', 'Islam',
            'Jl. Merdeka No. 1', '2026-07-13',
            'Budi Santoso', '081234567890', 'budi@contoh.id',
            'Siti Aminah', '081298765432', '',
            '', '', '',
        ]];
    }

    /**
     * Kolom NIS, NISN, dan telepon sebagai teks agar angka 0 di depan tidak hilang.
     *
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'B' => NumberFormat::FORMAT_TEXT,
            'K' => NumberFormat::FORMAT_TEXT,
            'N' => NumberFormat::FORMAT_TEXT,
            'Q' => NumberFormat::FORMAT_TEXT,
        ];
    }
}
