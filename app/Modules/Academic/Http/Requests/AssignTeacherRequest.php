<?php

namespace App\Modules\Academic\Http\Requests;

use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\Subject;
use App\Modules\People\Models\Teacher;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Satu sel matriks penugasan: rombel × mapel → guru (kosong = hapus penugasan).
 */
class AssignTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Permission sama dengan kelola rombel (master_data.manage).
        return $this->user()?->can('create', Classroom::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'classroom' => ['required', 'string', Rule::exists(Classroom::class, 'public_id')],
            'subject' => ['required', 'string', Rule::exists(Subject::class, 'public_id')->where('is_active', true)],
            'teacher' => ['nullable', 'string', Rule::exists(Teacher::class, 'public_id')->whereNull('deleted_at')->where('is_active', true)],
        ];
    }

    public function classroom(): Classroom
    {
        return Classroom::query()->where('public_id', $this->string('classroom')->value())->firstOrFail();
    }

    public function subject(): Subject
    {
        return Subject::query()->where('public_id', $this->string('subject')->value())->firstOrFail();
    }

    public function teacher(): ?Teacher
    {
        return $this->filled('teacher')
            ? Teacher::query()->where('public_id', $this->string('teacher')->value())->firstOrFail()
            : null;
    }
}
