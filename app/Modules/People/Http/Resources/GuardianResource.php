<?php

namespace App\Modules\People\Http\Resources;

use App\Modules\People\Models\Guardian;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * NIK tidak pernah dikirim utuh — hanya versi tersamar (4 digit terakhir).
 *
 * @mixin Guardian
 */
class GuardianResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'occupation' => $this->occupation,
            'address' => $this->address,
            'nik_masked' => $this->nik ? str_repeat('•', 12).substr($this->nik, -4) : null,
            'account' => $this->whenLoaded('user', fn () => $this->user ? [
                'email' => $this->user->email,
                'is_active' => $this->user->is_active,
            ] : null),
        ];
    }
}
