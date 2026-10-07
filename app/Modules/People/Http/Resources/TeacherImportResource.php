<?php

namespace App\Modules\People\Http\Resources;

use App\Modules\People\Models\TeacherImport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TeacherImport
 */
class TeacherImportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'original_name' => $this->original_name,
            'status' => ['value' => $this->status->value, 'label' => $this->status->label()],
            'finished' => $this->status->isFinished(),
            'total_rows' => $this->total_rows,
            'success_rows' => $this->success_rows,
            'failed_rows' => $this->failed_rows,
            'errors' => $this->errors ?? [],
            'failure_message' => $this->failure_message,
            'created_by' => $this->whenLoaded('creator', fn () => $this->creator?->name),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
