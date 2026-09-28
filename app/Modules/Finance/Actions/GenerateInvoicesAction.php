<?php

namespace App\Modules\Finance\Actions;

use App\Models\User;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\Finance\Enums\BatchStatus;
use App\Modules\Finance\Jobs\GenerateInvoicesJob;
use App\Modules\Finance\Models\FeeType;
use App\Modules\Finance\Models\InvoiceBatch;
use App\Support\ActivePeriod;
use Carbon\CarbonImmutable;

/**
 * Catat batch generate massal lalu antrekan Job (diproses scheduler di shared hosting).
 *
 * @phpstan-import-type Scope from \App\Modules\Finance\Services\InvoiceTargets
 */
class GenerateInvoicesAction
{
    public function __construct(private AuditLogger $audit, private ActivePeriod $period) {}

    /**
     * @param  Scope  $scope
     */
    public function handle(User $actor, FeeType $feeType, string $period, string $periodLabel, int $amount, CarbonImmutable $dueDate, array $scope): InvoiceBatch
    {
        $batch = InvoiceBatch::query()->create([
            'academic_year_id' => $this->period->academicYearId(),
            'fee_type_id' => $feeType->id,
            'period' => $period,
            'period_label' => $periodLabel,
            'amount' => $amount,
            'due_date' => $dueDate->toDateString(),
            'scope' => $scope,
            'status' => BatchStatus::Queued,
            'created_by' => $actor->id,
        ]);

        $this->audit->record('finance', 'generated', "Generate tagihan {$feeType->name} {$periodLabel}", $batch);

        GenerateInvoicesJob::dispatch($batch->id);

        return $batch;
    }
}
