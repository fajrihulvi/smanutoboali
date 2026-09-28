<?php

namespace App\Modules\Portfolio\Http\Resources;

use App\Modules\Core\Models\File;
use App\Modules\Core\Services\FileUrlGenerator;
use App\Modules\People\Models\Student;
use App\Modules\Portfolio\Models\Portfolio;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Butuh eager load: category, students, files, creator.
 * `onlyStudentIds` (untuk orang tua): hanya tampilkan nama siswa yang diizinkan (anak sendiri),
 * agar nama siswa lain di portofolio kelompok tidak bocor.
 *
 * @mixin Portfolio
 */
class PortfolioResource extends JsonResource
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
        $students = $this->onlyStudentIds === null
            ? $this->students
            : $this->students->filter(fn (Student $s) => in_array($s->id, $this->onlyStudentIds, true));

        return [
            'public_id' => $this->public_id,
            'title' => $this->title,
            'description' => $this->description,
            'date' => $this->date->toDateString(),
            'category' => ['code' => $this->category->code, 'name' => $this->category->name],
            'visibility' => ['value' => $this->visibility->value, 'label' => $this->visibility->label()],
            'students' => $students->map(fn (Student $s) => ['public_id' => $s->public_id, 'name' => $s->name])->values(),
            'students_count' => $this->students->count(),
            'files' => $this->files->map(fn (File $f) => [
                'public_id' => $f->public_id,
                'name' => $f->original_name,
                'mime_type' => $f->mime_type,
                'is_image' => $f->isImage(),
                'size' => $f->size,
                'url' => $urls->url($f),
            ])->values(),
            'creator' => $this->creator?->name,
            'can' => [
                'update' => $request->user()?->can('update', $this->resource) ?? false,
            ],
        ];
    }
}
