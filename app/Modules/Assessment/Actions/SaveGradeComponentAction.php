<?php

namespace App\Modules\Assessment\Actions;

use App\Modules\Academic\Models\TeachingAssignment;
use App\Modules\Assessment\Enums\ComponentType;
use App\Modules\Assessment\Models\GradeComponent;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\School\Models\Semester;
use Illuminate\Validation\ValidationException;

class SaveGradeComponentAction
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  array{type: ComponentType, name: string, weight: int}  $data
     *
     * @throws ValidationException
     */
    public function handle(TeachingAssignment $assignment, Semester $semester, array $data, ?GradeComponent $component = null): GradeComponent
    {
        if ($component?->isPublished()) {
            throw ValidationException::withMessages(['name' => 'Komponen sudah dipublikasikan. Batalkan publikasi untuk mengubahnya.']);
        }

        $duplicate = GradeComponent::query()
            ->where('teaching_assignment_id', $assignment->id)
            ->where('semester_id', $semester->id)
            ->where('name', $data['name'])
            ->when($component, fn ($q) => $q->whereKeyNot($component->id))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages(['name' => "Komponen \"{$data['name']}\" sudah ada."]);
        }

        $isNew = $component === null;
        $component ??= new GradeComponent([
            'academic_year_id' => $assignment->academic_year_id,
            'semester_id' => $semester->id,
            'teaching_assignment_id' => $assignment->id,
            'position' => (int) GradeComponent::query()->for($assignment->id, $semester->id)->max('position') + 1,
        ]);

        $component->fill($data)->save();

        $this->audit->record(
            'assessment',
            $isNew ? 'component_created' : 'component_updated',
            ($isNew ? 'Menambah' : 'Mengubah')." komponen nilai {$component->name} ({$component->weight}%)",
            $component,
        );

        return $component;
    }
}
