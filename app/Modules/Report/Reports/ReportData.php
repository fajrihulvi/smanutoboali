<?php

namespace App\Modules\Report\Reports;

/**
 * Hasil satu laporan: siap ditampilkan (pratinjau) atau dirender ke PDF/Excel.
 */
final class ReportData
{
    /**
     * @param  list<string>  $meta  keterangan filter, mis. "Periode: 1–30 Sep 2026"
     * @param  list<string>  $columns
     * @param  list<list<string|int|float|null>>  $rows
     * @param  list<string|int|float|null>|null  $footer  baris total (opsional)
     * @param  list<int>  $numeric  indeks kolom angka (rata kanan)
     * @param  list<int>  $currency  indeks kolom rupiah (juga angka)
     */
    public function __construct(
        public string $title,
        public array $meta,
        public array $columns,
        public array $rows,
        public ?array $footer = null,
        public array $numeric = [],
        public array $currency = [],
        public bool $landscape = false,
    ) {}

    public function count(): int
    {
        return count($this->rows);
    }

    public function isNumeric(int $index): bool
    {
        return in_array($index, $this->numeric, true) || in_array($index, $this->currency, true);
    }

    /**
     * Nilai sel untuk tampilan (PDF/pratinjau): rupiah & angka berformat Indonesia.
     */
    public function display(int $index, string|int|float|null $value): string
    {
        if ($value === null || $value === '') {
            return '–';
        }

        if (in_array($index, $this->currency, true) && is_numeric($value)) {
            return 'Rp '.number_format((float) $value, 0, ',', '.');
        }

        if (is_float($value)) {
            return number_format($value, 1, ',', '.');
        }

        if (is_int($value)) {
            return number_format($value, 0, ',', '.');
        }

        return $value;
    }
}
