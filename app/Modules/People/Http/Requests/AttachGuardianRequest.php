<?php

namespace App\Modules\People\Http\Requests;

use App\Modules\People\Enums\GuardianRelationship;
use App\Modules\People\Models\Guardian;
use App\Modules\People\Models\Student;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Hubungkan orang tua ke siswa: pilih orang tua yang ada (guardian) ATAU buat baru (new_*).
 */
class AttachGuardianRequest extends FormRequest
{
    public function authorize(): bool
    {
        $student = $this->route('student');

        return $student instanceof Student && ($this->user()?->can('update', $student) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'mode' => ['required', 'in:existing,new'],
            'guardian' => ['required_if:mode,existing', 'nullable', 'string', Rule::exists(Guardian::class, 'public_id')->whereNull('deleted_at')],
            'new_name' => ['required_if:mode,new', 'nullable', 'string', 'max:150'],
            'new_phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s]+$/'],
            'new_email' => ['nullable', 'email', 'max:150'],
            'relationship' => ['required', Rule::enum(GuardianRelationship::class)],
            'is_primary' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'guardian' => 'orang tua',
            'new_name' => 'nama orang tua',
            'new_phone' => 'telepon',
            'new_email' => 'email',
            'relationship' => 'hubungan',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'guardian.required_if' => 'Pilih orang tua yang akan dihubungkan.',
            'new_name.required_if' => 'Nama orang tua wajib diisi.',
        ];
    }
}
