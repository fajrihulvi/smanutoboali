<?php

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\Enums\InvoiceStatus;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Notifications\InvoiceReminderNotification;
use App\Modules\Finance\Services\StudentGuardians;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * Pengingat harian (scheduler): tagihan belum lunas yang jatuh tempo 3 hari lagi (H-3)
 * atau lewat jatuh tempo kemarin (H+1). Berbasis tanggal, jadi tiap tagihan diingatkan sekali per tahap.
 */
class SendInvoiceRemindersAction
{
    public const DAYS_BEFORE = 3;

    public function __construct(private StudentGuardians $guardians) {}

    public function handle(CarbonImmutable $today): int
    {
        $sent = 0;

        Invoice::query()
            ->whereIn('status', [InvoiceStatus::Unpaid, InvoiceStatus::Failed])
            ->where(fn ($q) => $q
                ->whereOnDate('due_date', $today->addDays(self::DAYS_BEFORE)->toDateString())
                ->orWhereDate('due_date', $today->subDay()->toDateString()))
            ->with(['student', 'feeType'])
            ->chunkById(200, function (Collection $invoices) use ($today, &$sent) {
                $accounts = $this->guardians->accounts($invoices->pluck('student_id')->map(fn ($id) => (int) $id)->unique()->values()->all());

                foreach ($invoices as $invoice) {
                    /** @var Invoice $invoice */
                    $users = $accounts->get($invoice->student_id);

                    if ($users === null || $users->isEmpty()) {
                        continue;
                    }

                    Notification::send($users, new InvoiceReminderNotification(
                        $invoice->public_id,
                        $invoice->student->name,
                        "{$invoice->feeType->name} {$invoice->period_label}",
                        $invoice->remaining(),
                        $invoice->due_date->locale('id')->translatedFormat('j F Y'),
                        $invoice->due_date->lt($today),
                    ));
                    $sent++;
                }
            });

        return $sent;
    }
}
