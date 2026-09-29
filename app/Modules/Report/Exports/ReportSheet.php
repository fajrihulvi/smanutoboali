<?php

namespace App\Modules\Report\Exports;

use App\Modules\Report\Reports\ReportData;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Satu sheet laporan: judul & keterangan filter di atas, lalu header dan data.
 * Angka & rupiah tetap bertipe angka agar bisa dijumlah di Excel.
 */
class ReportSheet implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    public function __construct(private ReportData $data, private string $school) {}

    /**
     * @return list<list<string|int|float|null>>
     */
    public function array(): array
    {
        $top = [[$this->data->title], [$this->school], ...array_map(fn (string $m) => [$m], $this->data->meta), []];

        return [
            ...$top,
            $this->data->columns,
            ...$this->data->rows,
            ...($this->data->footer ? [$this->data->footer] : []),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function styles(Worksheet $sheet): array
    {
        $headerRow = count($this->data->meta) + 4;
        $styles = [1 => ['font' => ['bold' => true, 'size' => 13]], $headerRow => ['font' => ['bold' => true]]];

        if ($this->data->footer) {
            $styles[$headerRow + count($this->data->rows) + 1] = ['font' => ['bold' => true]];
        }

        foreach ($this->data->currency as $index) {
            $column = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->getStyle("{$column}".($headerRow + 1).':'.$column.($headerRow + count($this->data->rows) + 1))
                ->getNumberFormat()->setFormatCode('#,##0');
        }

        return $styles;
    }

    public function title(): string
    {
        return Str::limit($this->data->title, 31, '');
    }
}
