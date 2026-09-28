<?php

namespace App\Modules\Finance\Actions;

use App\Models\User;
use App\Modules\Finance\Enums\InvoiceItemType;
use App\Modules\Finance\Enums\InvoiceStatus;
use App\Modules\Finance\Models\FeeType;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Models\InvoiceBatch;
use App\Modules\Finance\Models\StudentDiscount;
use App\Modules\Finance\Services\FinanceSequence;
use App\Modules\People\Models\Student;
use App\Support\ActivePeriod;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Buat satu tagihan (baris biaya + baris potongan). Mengembalikan null bila siswa sudah punya
 * tagihan aktif untuk jenis biaya & periode yang sama (dijaga index unik dedupe_key).
 * Dipakai generate massal dan (nanti) daftar ulang PPDB.
 */
class IssueInvoiceAction
{
    public function __construct(private FinanceSequence $sequence, private ActivePeriod $period) {}

    public function handle(
        Student $student,
        FeeType $feeType,
        string $period,
        string $periodLabel,
        int $amount,
        CarbonImmutable $dueDate,
        ?StudentDiscount $discount = null,
        ?InvoiceBatch $batch = null,
        ?User $actor = null,
    ): ?Invoice {
        $cut = $discount ? $discount->kind->apply($amount, $discount->value) : 0;
        $discountLine = $discount && $cut > 0 ? "Potongan {$discount->describe()} — {$discount->reason}" : null;

        try {
            return DB::transaction(function () use ($student, $feeType, $period, $periodLabel, $amount, $dueDate, $discountLine, $batch, $actor, $cut) {
                $invoice = Invoice::query()->create([
                    'number' => $this->sequence->next('INV'),
                    'academic_year_id' => $this->period->academicYearId(),
                    'student_id' => $student->id,
                    'fee_type_id' => $feeType->id,
                    'invoice_batch_id' => $batch?->id,
                    'period' => $period,
                    'period_label' => $periodLabel,
                    'dedupe_key' => Invoice::dedupeKey($student->id, $feeType->id, $period),
                    'due_date' => $dueDate->toDateString(),
                    'total' => $amount - $cut,
                    'paid_amount' => 0,
                    'status' => $amount - $cut === 0 ? InvoiceStatus::Paid : InvoiceStatus::Unpaid,
                    'paid_at' => $amount - $cut === 0 ? now() : null,
                    'created_by' => $actor?->id,
                ]);

                $invoice->items()->create(['type' => InvoiceItemType::Fee, 'description' => "{$feeType->name} {$periodLabel}", 'amount' => $amount]);

                if ($discountLine !== null) {
                    $invoice->items()->create(['type' => InvoiceItemType::Discount, 'description' => $discountLine, 'amount' => -$cut]);
                }

                return $invoice;
            });
        } catch (UniqueConstraintViolationException $e) {
            // Nomor tagihan unik tidak mungkin bentrok (dikunci); yang bentrok hanya dedupe_key.
            if (str_contains($e->getMessage(), 'dedupe_key')) {
                return null;
            }

            throw $e;
        }
    }
}
