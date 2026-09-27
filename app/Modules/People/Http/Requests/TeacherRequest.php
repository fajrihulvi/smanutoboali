<?php

namespace App\Modules\People\Http\Requests;

use App\Modules\People\Enums\EmploymentStatus;
use App\Modules\People\Enums\Gender;
use App\Modules\People\Models\Teacher;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Tambah & ubah guru oleh operator.
 */
class TeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        $teacher = $this->teacher();

        return $teacher
            ? ($this->user()?->can('update', $teacher) ?? false)
            : ($this->user()?->can('create', Teacher::class) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $ignore = $this->teacher()?->getKey();

        return [
            'name' => ['required', 'string', 'max:150'],
            'nip' => ['nullable', 'digits:18', Rule::unique(Teacher::class)->ignore($ignore)],
            'nuptk' => ['nullable', 'digits:16', Rule::unique(Teacher::class)->ignore($ignore)],
            'gender' => ['required', Rule::enum(Gender::class)],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s]+$/'],
            'employment_status' => ['required', Rule::enum(EmploymentStatus::class)],
            'is_active' => ['boolean'],
            'photo' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:2048'],
            'remove_photo' => ['boolean'],
            'create_account' => ['boolean'],
            'account_email' => ['nullable', 'required_if_accepted:create_account', 'email', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama',
            'nip' => 'NIP',
            'nuptk' => 'NUPTK',
            'gender' => 'jenis kelamin',
            'phone' => 'telepon',
            'employment_status' => 'status kepegawaian',
            'photo' => 'foto',
            'account_email' => 'email akun',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['phone.regex' => 'Telepon hanya boleh berisi angka, spasi, + dan -.'];
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            'name' => $this->string('name')->trim()->value(),
            'nip' => $this->filled('nip') ? $this->string('nip')->value() : null,
            'nuptk' => $this->filled('nuptk') ? $this->string('nuptk')->value() : null,
            'gender' => $this->string('gender')->value(),
            'phone' => $this->filled('phone') ? $this->string('phone')->trim()->value() : null,
            'employment_status' => $this->string('employment_status')->value(),
            'is_active' => $this->boolean('is_active', true),
        ];
    }

    public function accountEmail(): ?string
    {
        return $this->boolean('create_account') ? $this->string('account_email')->trim()->lower()->value() : null;
    }

    private function teacher(): ?Teacher
    {
        $teacher = $this->route('teacher');

        return $teacher instanceof Teacher ? $teacher : null;
    }
}
