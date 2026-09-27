<?php

namespace App\Modules\Academic\Http\Resources;

use App\Modules\Academic\Models\Classroom;
use App\Modules\School\Services\SchoolProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Classroom
 */
class ClassroomResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'name' => $this->name,
            'grade' => $this->grade,
            'grade_label' => app(SchoolProfile::class)->level()->gradeLabel($this->grade),
            'capacity' => $this->capacity,
            'students_count' => $this->whenCounted('activeEnrollments', fn () => (int) $this->getAttribute('active_enrollments_count')),
            'academic_year' => $this->whenLoaded('academicYear', fn () => [
                'public_id' => $this->academicYear->public_id,
                'name' => $this->academicYear->name,
                'is_active' => $this->academicYear->is_active,
            ]),
            'homeroom_teacher' => $this->whenLoaded('homeroomTeacher', fn () => $this->homeroomTeacher ? [
                'public_id' => $this->homeroomTeacher->public_id,
                'name' => $this->homeroomTeacher->name,
            ] : null),
        ];
    }
}
