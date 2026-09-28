<?php

namespace App\Modules\Finance\Http\Resources;

use App\Modules\Finance\Models\StudentDiscount;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Butuh eager load: student, feeType.
 *
 * @mixin StudentDiscount
 */
class DiscountResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'student' => ['public_id' => $this->student->public_id, 'name' => $this->student->name, 'nis' => $this->student->nis],
            'fee_type' => $this->feeType ? ['public_id' => $this->feeType->public_id, 'name' => $this->feeType->name] : null,
            'kind' => ['value' => $this->kind->value, 'label' => $this->kind->label()],
            'value' => $this->value,
            'label' => $this->describe(),
            'reason' => $this->reason,
            'is_active' => $this->is_active,
        ];
    }
}
