<?php

namespace App\Modules\Finance\Exports;

use App\Modules\Finance\Enums\PaymentStatus;
use App\Modules\Finance\Models\Payment;
use App\Support\Excel\SafeValueBinder;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Cell;

/**
 * Daftar penerimaan (pembayaran terkonfirmasi) dalam rentang tanggal; dibaca per chunk.
 *
 * @implements WithMapping<Payment>
 */
class ReceiptsExport implements FromQuery, ShouldAutoSize, WithCustomValueBinder, WithHeadings, WithMapping, WithTitle
{
    public function __construct(private CarbonImmutable $from, private CarbonImmutable $to) {}

    /**
     * @return Builder<Payment>
     */
    public function query(): Builder
    {
        return Payment::query()
            ->where('status', PaymentStatus::Confirmed)
            ->whereDate('paid_on', '>=', $this->from->toDateString())
            ->whereDate('paid_on', '<=', $this->to->toDateString())
            ->with(['invoice.student', 'invoice.feeType'])
            ->orderBy('paid_on')->orderBy('id');
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ['Tanggal', 'No. kwitansi', 'No. tagihan', 'NIS', 'Nama', 'Jenis biaya', 'Periode', 'Metode', 'Jumlah (Rp)'];
    }

    /**
     * @param  Payment  $payment
     * @return list<string|int|null>
     */
    public function map($payment): array
    {
        return [
            $payment->paid_on->toDateString(),
            $payment->receipt_number,
            $payment->invoice->number,
            $payment->invoice->student->nis,
            $payment->invoice->student->name,
            $payment->invoice->feeType->name,
            $payment->invoice->period_label,
            $payment->method->label(),
            $payment->amount,
        ];
    }

    public function title(): string
    {
        return 'Penerimaan';
    }

    /**
     * Teks tidak pernah dibaca sebagai formula (cegah formula injection).
     */
    public function bindValue(Cell $cell, mixed $value): bool
    {
        return (new SafeValueBinder)->bindValue($cell, $value);
    }
}
