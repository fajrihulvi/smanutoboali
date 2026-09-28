<?php

namespace App\Modules\Assessment\Actions;

use App\Modules\Academic\Models\TeachingAssignment;
use App\Modules\Assessment\Enums\Predicate;
use App\Modules\Assessment\Models\GradeDescriptionTemplate;

class SaveDescriptionTemplatesAction
{
    /**
     * @param  array<string, string>  $templates  predikat → template
     */
    public function handle(TeachingAssignment $assignment, array $templates): void
    {
        foreach (Predicate::cases() as $predicate) {
            GradeDescriptionTemplate::query()->updateOrCreate(
                ['teaching_assignment_id' => $assignment->id, 'predicate' => $predicate],
                ['template' => trim($templates[$predicate->value] ?? $predicate->defaultTemplate())],
            );
        }
    }
}
