<?php

namespace App\Modules\Academic\Http\Requests;

use App\Modules\Academic\Enums\MaterialType;
use App\Modules\Academic\Models\LearningMaterial;
use App\Modules\Academic\Models\TeachingAssignment;
use App\Modules\Academic\Policies\LearningMaterialPolicy;
use App\Modules\Core\Services\SettingsService;
use App\Support\ActivePeriod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class LearningMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        $material = $this->material();

        return $material
            ? ($this->user()?->can('update', $material) ?? false)
            : ($this->user()?->can('create', LearningMaterial::class) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'teaching_assignment' => ['required', 'string', Rule::exists(TeachingAssignment::class, 'public_id')],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'type' => ['required', Rule::enum(MaterialType::class)],
            'url' => ['nullable', 'required_if:type,'.MaterialType::Link->value, 'url:http,https', 'max:500'],
            'file' => ['nullable', 'file', 'max:'.app(SettingsService::class)->uploadMaxKb()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'teaching_assignment' => 'mata pelajaran',
            'title' => 'judul',
            'description' => 'deskripsi',
            'type' => 'jenis materi',
            'url' => 'tautan',
            'file' => 'file',
        ];
    }

    /**
     * Penugasan harus milik guru ini dan berada di tahun ajaran aktif.
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->has('teaching_assignment')) {
                    return;
                }

                $assignment = $this->assignment();
                $user = $this->user();

                if (
                    $user === null
                    || ! app(LearningMaterialPolicy::class)->teaches($user, $assignment)
                    || $assignment->academic_year_id !== app(ActivePeriod::class)->academicYearId()
                ) {
                    $validator->errors()->add('teaching_assignment', 'Pilih mata pelajaran yang Anda ampu di tahun ajaran aktif.');
                }
            },
        ];
    }

    /**
     * @return array{teaching_assignment: TeachingAssignment, title: string, description: string|null, type: MaterialType, url: string|null}
     */
    public function payload(): array
    {
        $description = $this->string('description')->trim()->value();
        $url = $this->string('url')->trim()->value();

        return [
            'teaching_assignment' => $this->assignment(),
            'title' => $this->string('title')->trim()->value(),
            'description' => $description !== '' ? $description : null,
            'type' => MaterialType::from($this->string('type')->value()),
            'url' => $url !== '' ? $url : null,
        ];
    }

    public function upload(): ?UploadedFile
    {
        $file = $this->file('file');

        return $file instanceof UploadedFile ? $file : null;
    }

    private function assignment(): TeachingAssignment
    {
        return TeachingAssignment::query()->where('public_id', $this->string('teaching_assignment')->value())->firstOrFail();
    }

    private function material(): ?LearningMaterial
    {
        $material = $this->route('material');

        return $material instanceof LearningMaterial ? $material : null;
    }
}
