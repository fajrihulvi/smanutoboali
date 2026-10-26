<?php

namespace App\Modules\Academic\Exports;

use App\Modules\Academic\Services\SubjectImportRowProcessor;
use App\Support\Excel\SafeValueBinder;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

/**
 * Template import mata pelajaran (baris judul + contoh).
 */
class SubjectImportTemplate implements FromArray, ShouldAutoSize, WithColumnFormatting, WithCustomValueBinder, WithHeadings
{
    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return SubjectImportRowProcessor::COLUMNS;
    }

    /**
     * @return list<list<string>>
     */
    public function array(): array
    {
        return [
            ['MTK', 'Matematika', 'Umum', 'Ya'],
            ['TFZ', 'Tahfiz Al-Qur\'an', 'Kepesantrenan', 'Ya'],
        ];
    }

    /**
     * Kolom kode sebagai teks agar kode berawalan 0 (mis. "01") tidak berubah.
     *
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        return ['A' => NumberFormat::FORMAT_TEXT];
    }

    public function bindValue(Cell $cell, mixed $value): bool
    {
        return (new SafeValueBinder)->bindValue($cell, $value);
    }
}
