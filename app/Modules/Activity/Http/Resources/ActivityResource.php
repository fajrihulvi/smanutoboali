<?php

namespace App\Modules\Activity\Http\Resources;

use App\Modules\Activity\Models\Activity;
use App\Modules\Activity\Models\ActivityParticipant;
use App\Modules\Core\Models\File;
use App\Modules\Core\Services\FileUrlGenerator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Butuh eager load: participants.classroom, participants.student, photos, creator.
 * `onlyStudentIds` (orang tua): siswa peserta selain anak sendiri disembunyikan.
 *
 * @mixin Activity
 */
class ActivityResource extends JsonResource
{
    /** @var list<int>|null */
    public ?array $onlyStudentIds = null;

    /**
     * @param  list<int>  $ids
     */
    public function onlyStudents(array $ids): static
    {
        $this->onlyStudentIds = $ids;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $urls = app(FileUrlGenerator::class);
        $classrooms = $this->participants->filter(fn (ActivityParticipant $p) => $p->classroom !== null);
        $students = $this->participants->filter(fn (ActivityParticipant $p) => $p->student !== null
            && ($this->onlyStudentIds === null || in_array($p->student_id, $this->onlyStudentIds, true)));

        return [
            'public_id' => $this->public_id,
            'title' => $this->title,
            'type' => ['value' => $this->type->value, 'label' => $this->type->label()],
            'date' => $this->date->toDateString(),
            'start_time' => $this->start_time ? substr($this->start_time, 0, 5) : null,
            'end_time' => $this->end_time ? substr($this->end_time, 0, 5) : null,
            'location' => $this->location,
            'description' => $this->description,
            'status' => ['value' => $this->status->value, 'label' => $this->status->label()],
            'classrooms' => $classrooms->map(fn (ActivityParticipant $p) => ['public_id' => $p->classroom?->public_id, 'name' => $p->classroom?->name])->values(),
            'students' => $students->map(fn (ActivityParticipant $p) => ['public_id' => $p->student?->public_id, 'name' => $p->student?->name])->values(),
            'photos' => $this->photos->map(fn (File $f) => ['public_id' => $f->public_id, 'name' => $f->original_name, 'url' => $urls->url($f)])->values(),
            'creator' => $this->creator?->name,
            'can' => ['update' => $request->user()?->can('update', $this->resource) ?? false],
        ];
    }
}
