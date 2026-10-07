<?php

namespace App\Modules\People\Http\Requests;

use App\Modules\People\Models\Teacher;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TeacherImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Teacher::class) ?? false;
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
