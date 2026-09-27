<?php

namespace App\Modules\Academic\Actions;

use App\Modules\Academic\Models\Schedule;
use App\Modules\Core\Services\AuditLogger;

class DeleteScheduleAction
{
    public function __construct(private AuditLogger $audit) {}

    public function handle(Schedule $schedule): void
    {
        $schedule->loadMissing(['teachingAssignment.subject:id,name', 'teachingAssignment.classroom:id,name']);
        $assignment = $schedule->teachingAssignment;

        $schedule->delete();

        $this->audit->record(
            'academic',
            'schedule_deleted',
            "Menghapus jadwal {$assignment->subject->name} {$assignment->classroom->name} ({$schedule->day_of_week->label()} {$schedule->startLabel()}–{$schedule->endLabel()})",
            $schedule,
        );
    }
}
