<?php

namespace App\Modules\Finance\Http\Resources;

use App\Modules\Finance\Models\FeeType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin FeeType
 */
class FeeTypeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'name' => $this->name,
            'default_amount' => $this->default_amount,
            'frequency' => ['value' => $this->frequency->value, 'label' => $this->frequency->label()],
            'is_active' => $this->is_active,
            'description' => $this->description,
            'invoices_count' => (int) ($this->getAttribute('invoices_count') ?? 0),
        ];
    }
}
