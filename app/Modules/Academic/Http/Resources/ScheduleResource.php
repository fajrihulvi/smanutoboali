<?php

namespace App\Modules\Academic\Http\Resources;

use App\Modules\Academic\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Slot jadwal. Butuh eager load teachingAssignment.{subject,teacher,classroom}.
 *
 * @mixin Schedule
 */
class ScheduleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $assignment = $this->teachingAssignment;

        return [
            'public_id' => $this->public_id,
            'day' => $this->day_of_week->value,
            'day_label' => $this->day_of_week->label(),
            'start_time' => $this->startLabel(),
            'end_time' => $this->endLabel(),
            'room' => $this->room,
            'assignment' => $assignment->public_id,
            'subject' => [
                'public_id' => $assignment->subject->public_id,
                'code' => $assignment->subject->code,
                'name' => $assignment->subject->name,
            ],
            'teacher' => ['public_id' => $assignment->teacher->public_id, 'name' => $assignment->teacher->name],
            'classroom' => ['public_id' => $assignment->classroom->public_id, 'name' => $assignment->classroom->name],
        ];
    }
}
