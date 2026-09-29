<?php

namespace App\Modules\People\Http\Resources;

use App\Modules\Core\Services\FileUrlGenerator;
use App\Modules\People\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Teacher
 */
class TeacherResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'name' => $this->name,
            'nip' => $this->nip,
            'nuptk' => $this->nuptk,
            'gender' => ['value' => $this->gender->value, 'label' => $this->gender->label()],
            'phone' => $this->phone,
            'employment_status' => ['value' => $this->employment_status->value, 'label' => $this->employment_status->label()],
            'is_active' => $this->is_active,
            'photo_url' => $this->whenLoaded('photo', fn () => $this->photo ? app(FileUrlGenerator::class)->thumbnailUrl($this->photo) : null),
            'account' => $this->whenLoaded('user', fn () => $this->user ? [
                'email' => $this->user->email,
                'is_active' => $this->user->is_active,
            ] : null),
        ];
    }
}
