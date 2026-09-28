<?php

namespace App\Modules\Finance\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Tunggakan per siswa (nominal rupiah sebagai angka agar bisa dijumlah di Excel).
 */
class OutstandingExport implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    /**
     * @param  list<array{nis: string, name: string, classroom: string|null, invoices: int, amount: int, oldest_due: string|null}>  $rows
     */
    public function __construct(private array $rows) {}

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ['NIS', 'Nama', 'Rombel', 'Jumlah tagihan', 'Tunggakan (Rp)', 'Jatuh tempo terlama'];
    }

    /**
     * @return list<list<string|int|null>>
     */
    public function array(): array
    {
        return array_map(fn (array $r) => [$r['nis'], $r['name'], $r['classroom'], $r['invoices'], $r['amount'], $r['oldest_due']], $this->rows);
    }

    public function title(): string
    {
        return 'Tunggakan';
    }
}
