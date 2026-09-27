<?php

namespace App\Modules\Academic\Http\Resources;

use App\Modules\Academic\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Subject
 */
class SubjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'code' => $this->code,
            'name' => $this->name,
            'group' => ['value' => $this->group->value, 'label' => $this->group->label()],
            'is_active' => $this->is_active,
            'assignments_count' => $this->whenCounted('teachingAssignments'),
        ];
    }
}
