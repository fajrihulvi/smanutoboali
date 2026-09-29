<?php

namespace App\Modules\People\Http\Resources;

use App\Modules\Core\Services\FileUrlGenerator;
use App\Modules\People\Enums\GuardianRelationship;
use App\Modules\People\Models\Guardian;
use App\Modules\People\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Student
 */
class StudentResource extends JsonResource
{
    /**
     * Sertakan kontak orang tua (nomor telepon/email). Hanya untuk operator.
     */
    public bool $withGuardianContact = false;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'nis' => $this->nis,
            'nisn' => $this->nisn,
            'name' => $this->name,
            'gender' => ['value' => $this->gender->value, 'label' => $this->gender->label()],
            'birth_place' => $this->birth_place,
            'birth_date' => $this->birth_date?->toDateString(),
            'religion' => $this->religion ? ['value' => $this->religion->value, 'label' => $this->religion->label()] : null,
            'address' => $this->address,
            'status' => ['value' => $this->status->value, 'label' => $this->status->label()],
            'entry_date' => $this->entry_date?->toDateString(),
            'photo_url' => $this->whenLoaded('photo', fn () => $this->photo ? app(FileUrlGenerator::class)->thumbnailUrl($this->photo) : null),
            'guardians' => $this->whenLoaded('guardians', fn () => $this->guardians
                ->sortByDesc(fn (Guardian $guardian) => $guardian->getRelationValue('pivot')->is_primary)
                ->map(function (Guardian $guardian) {
                    $pivot = $guardian->getRelationValue('pivot');
                    $relationship = GuardianRelationship::from($pivot->relationship);

                    return [
                        'public_id' => $guardian->public_id,
                        'name' => $guardian->name,
                        'relationship' => ['value' => $relationship->value, 'label' => $relationship->label()],
                        'is_primary' => (bool) $pivot->is_primary,
                        'phone' => $this->withGuardianContact ? $guardian->phone : null,
                        'email' => $this->withGuardianContact ? $guardian->email : null,
                        'has_account' => $guardian->user_id !== null,
                    ];
                })->values()->all()),
        ];
    }

    public function withGuardianContact(): static
    {
        $this->withGuardianContact = true;

        return $this;
    }
}
