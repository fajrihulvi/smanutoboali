<?php

namespace App\Modules\Academic\Http\Requests;

use App\Modules\Academic\Models\Subject;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SubjectImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Subject::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Isi file diperiksa lagi (MIME asli) oleh FileService.
        return ['file' => ['required', 'file', 'extensions:xlsx,csv', 'max:10240']];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['file.extensions' => 'Gunakan file .xlsx atau .csv dari template.'];
    }
}
