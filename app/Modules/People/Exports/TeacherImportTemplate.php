<?php

namespace App\Modules\People\Exports;

use App\Modules\People\Services\TeacherImportRowProcessor;
use App\Support\Excel\SafeValueBinder;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

/**
 * Template import guru (baris judul + satu contoh).
 */
class TeacherImportTemplate implements FromArray, ShouldAutoSize, WithColumnFormatting, WithCustomValueBinder, WithHeadings
{
    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return TeacherImportRowProcessor::COLUMNS;
    }

    /**
     * @return list<list<string>>
     */
    public function array(): array
    {
        return [[
            '198001012005011001', '1234567890123456', 'Dewi Lestari, S.Pd.', 'P', 'PNS', '081234567890',
        ]];
    }

    /**
     * Kolom NIP, NUPTK, dan telepon sebagai teks agar digit tidak berubah / angka 0 tidak hilang.
     *
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'B' => NumberFormat::FORMAT_TEXT,
            'F' => NumberFormat::FORMAT_TEXT,
        ];
    }

    /**
     * Contoh NIP 18 digit harus tetap teks; binder bawaan mengubahnya menjadi angka (presisi hilang).
     */
    public function bindValue(Cell $cell, mixed $value): bool
    {
        return (new SafeValueBinder)->bindValue($cell, $value);
    }
}
