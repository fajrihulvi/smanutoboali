<?php

namespace App\Modules\Finance\Actions;

use App\Modules\Core\Services\AuditLogger;
use App\Modules\Finance\Enums\FeeFrequency;
use App\Modules\Finance\Models\FeeType;

class SaveFeeTypeAction
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  array{name: string, default_amount: int, frequency: FeeFrequency, is_active: bool, description: string|null}  $data
     */
    public function handle(array $data, ?FeeType $feeType = null): FeeType
    {
        $isNew = $feeType === null;
        $feeType ??= new FeeType;
        $feeType->fill($data)->save();

        $this->audit->record('finance', $isNew ? 'fee_type_created' : 'fee_type_updated', ($isNew ? 'Menambah' : 'Mengubah')." jenis biaya {$feeType->name}", $feeType);

        return $feeType;
    }
}
