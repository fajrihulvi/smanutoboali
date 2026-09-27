<?php

namespace App\Modules\School\Http\Resources;

use App\Modules\School\Models\AcademicYear;
use App\Modules\School\Models\Semester;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AcademicYear
 */
class AcademicYearResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'name' => $this->name,
            'start_date' => $this->start_date->toDateString(),
            'end_date' => $this->end_date->toDateString(),
            'is_active' => $this->is_active,
            'can_delete' => $request->user()?->can('delete', $this->resource) ?? false,
            'semesters' => $this->whenLoaded('semesters', fn () => $this->semesters->map(fn (Semester $semester) => [
                'public_id' => $semester->public_id,
                'type' => $semester->type->value,
                'label' => $semester->type->label(),
                'start_date' => $semester->start_date->toDateString(),
                'end_date' => $semester->end_date->toDateString(),
                'is_active' => $semester->is_active,
            ])->values()->all()),
        ];
    }
}
