<?php

namespace App\Modules\Academic\Http\Requests;

use App\Modules\Academic\Enums\SubjectGroup;
use App\Modules\Academic\Models\Subject;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $subject = $this->subject();

        return $subject
            ? ($this->user()?->can('update', $subject) ?? false)
            : ($this->user()?->can('create', Subject::class) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9.\-]+$/', Rule::unique(Subject::class)->ignore($this->subject()?->getKey())],
            'name' => ['required', 'string', 'max:100'],
            'group' => ['required', Rule::enum(SubjectGroup::class)],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['code' => 'kode', 'name' => 'nama mata pelajaran', 'group' => 'kelompok'];
    }

    /**
     * @return array{code: string, name: string, group: string, is_active: bool}
     */
    public function payload(): array
    {
        return [
            'code' => $this->string('code')->trim()->upper()->value(),
            'name' => $this->string('name')->trim()->value(),
            'group' => $this->string('group')->value(),
            'is_active' => $this->boolean('is_active', true),
        ];
    }

    private function subject(): ?Subject
    {
        $subject = $this->route('subject');

        return $subject instanceof Subject ? $subject : null;
    }
}
