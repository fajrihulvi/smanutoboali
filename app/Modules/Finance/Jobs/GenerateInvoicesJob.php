<?php

namespace App\Modules\Finance\Jobs;

use App\Modules\Finance\Actions\IssueInvoiceAction;
use App\Modules\Finance\Enums\BatchStatus;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Models\InvoiceBatch;
use App\Modules\Finance\Notifications\InvoiceIssuedNotification;
use App\Modules\Finance\Services\DiscountResolver;
use App\Modules\Finance\Services\InvoicePlanner;
use App\Modules\Finance\Services\InvoiceTargets;
use App\Modules\Finance\Services\StudentGuardians;
use App\Modules\People\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Generate tagihan massal per 100 siswa; siswa yang sudah punya tagihan periode itu dilewati.
 * Orang tua diberi tahu per tagihan yang benar-benar dibuat.
 */
class GenerateInvoicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public const CHUNK = 100;

    public int $timeout = 300;

    public int $tries = 1;

    public function __construct(public int $batchId) {}

    public function handle(InvoiceTargets $targets, DiscountResolver $discounts, IssueInvoiceAction $issue, StudentGuardians $guardians, InvoicePlanner $planner): void
    {
        $batch = InvoiceBatch::query()->with(['feeType', 'creator'])->find($this->batchId);

        if ($batch === null || $batch->status !== BatchStatus::Queued) {
            return;
        }

        $batch->update(['status' => BatchStatus::Running]);

        try {
            foreach (array_chunk($targets->studentIds($batch->scope), self::CHUNK) as $ids) {
                $students = Student::query()->whereIn('id', $ids)->get()->keyBy('id');
                $studentDiscounts = $discounts->forStudents($ids, $batch->feeType);
                // Saring duplikat lebih dulu agar nomor tagihan tidak berlubang (index unik tetap jadi pengaman).
                $existing = array_flip($planner->existingStudentIds($batch->feeType, $batch->period, $ids));
                $created = [];

                foreach ($ids as $id) {
                    $student = $students->get($id);

                    if ($student === null) {
                        continue;
                    }

                    if (isset($existing[$id])) {
                        $batch->increment('skipped_count');

                        continue;
                    }

                    $invoice = $issue->handle($student, $batch->feeType, $batch->period, $batch->period_label, $batch->amount,
                        $batch->due_date, $studentDiscounts->get($id), $batch, $batch->creator);

                    if ($invoice === null) {
                        $batch->increment('skipped_count');

                        continue;
                    }

                    $batch->increment('created_count');
                    $batch->increment('total_amount', $invoice->total);
                    $created[] = $invoice->setRelation('student', $student);
                }

                $this->notify($created, $guardians, $batch);
            }

            $batch->update(['status' => BatchStatus::Done, 'finished_at' => now()]);
        } catch (Throwable $e) {
            $batch->update(['status' => BatchStatus::Failed, 'error' => mb_substr($e->getMessage(), 0, 1000), 'finished_at' => now()]);
            report($e);
        }
    }

    /**
     * @param  list<Invoice>  $invoices
     */
    private function notify(array $invoices, StudentGuardians $guardians, InvoiceBatch $batch): void
    {
        $accounts = $guardians->accounts(array_map(fn (Invoice $i) => $i->student_id, $invoices));

        foreach ($invoices as $invoice) {
            if ($invoice->total === 0 || ! $accounts->has($invoice->student_id)) {
                continue;
            }

            Notification::send($accounts[$invoice->student_id], new InvoiceIssuedNotification(
                $invoice->public_id,
                $invoice->student->name,
                "{$batch->feeType->name} {$batch->period_label}",
                $invoice->total,
                $invoice->due_date->locale('id')->translatedFormat('j F Y'),
            ));
        }
    }
}
