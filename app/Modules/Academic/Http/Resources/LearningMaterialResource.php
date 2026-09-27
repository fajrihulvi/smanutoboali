<?php

namespace App\Modules\Academic\Http\Resources;

use App\Modules\Academic\Models\LearningMaterial;
use App\Modules\Core\Services\FileUrlGenerator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Materi pembelajaran. Butuh eager load file + teachingAssignment.{subject,classroom}.
 *
 * @mixin LearningMaterial
 */
class LearningMaterialResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $assignment = $this->teachingAssignment;

        return [
            'public_id' => $this->public_id,
            'title' => $this->title,
            'description' => $this->description,
            'type' => ['value' => $this->type->value, 'label' => $this->type->label()],
            'url' => $this->url,
            'file' => $this->file ? [
                'name' => $this->file->original_name,
                'size' => $this->file->size,
                'mime_type' => $this->file->mime_type,
                'url' => app(FileUrlGenerator::class)->url($this->file),
            ] : null,
            'assignment' => $assignment->public_id,
            'subject' => $assignment->subject->name,
            'classroom' => $assignment->classroom->name,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
