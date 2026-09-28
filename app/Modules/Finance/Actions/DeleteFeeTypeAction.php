<?php

namespace App\Modules\Finance\Actions;

use App\Modules\Core\Services\AuditLogger;
use App\Modules\Finance\Models\FeeType;
use Illuminate\Validation\ValidationException;

/**
 * Jenis biaya yang sudah dipakai di tagihan tidak dihapus — nonaktifkan saja.
 */
class DeleteFeeTypeAction
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @throws ValidationException
     */
    public function handle(FeeType $feeType): void
    {
        if ($feeType->invoices()->withTrashed()->exists()) {
            throw ValidationException::withMessages(['fee_type' => "Jenis biaya {$feeType->name} sudah dipakai di tagihan. Nonaktifkan saja."]);
        }

        $feeType->delete();
        $this->audit->record('finance', 'fee_type_deleted', "Menghapus jenis biaya {$feeType->name}", $feeType);
    }
}
