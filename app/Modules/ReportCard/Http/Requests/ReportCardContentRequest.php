<?php

namespace App\Modules\ReportCard\Http\Requests;

use App\Modules\ReportCard\Models\ReportCard;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReportCardContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $card = $this->route('card');

        return $card instanceof ReportCard && ($this->user()?->can('update', $card) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'homeroom_note' => ['nullable', 'string', 'max:1000'],
            'extracurriculars' => ['array', 'max:10'],
            'extracurriculars.*.name' => ['required', 'string', 'max:100'],
            'extracurriculars.*.predicate' => ['nullable', 'string', 'max:20'],
            'extracurriculars.*.note' => ['nullable', 'string', 'max:255'],
            'achievements' => ['array', 'max:10'],
            'achievements.*.name' => ['required', 'string', 'max:150'],
            'achievements.*.note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'homeroom_note' => 'catatan wali kelas',
            'extracurriculars.*.name' => 'nama kegiatan',
            'extracurriculars.*.predicate' => 'predikat',
            'achievements.*.name' => 'nama prestasi',
        ];
    }
}
