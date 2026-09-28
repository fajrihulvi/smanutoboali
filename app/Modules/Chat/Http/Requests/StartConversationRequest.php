<?php

namespace App\Modules\Chat\Http\Requests;

use App\Models\User;
use App\Modules\People\Models\Student;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StartConversationRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user' => ['required', 'string', Rule::exists(User::class, 'public_id')],
            'student' => ['nullable', 'string', Rule::exists(Student::class, 'public_id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['user' => 'penerima', 'student' => 'anak'];
    }

    public function target(): User
    {
        return User::query()->where('public_id', $this->string('user')->value())->firstOrFail();
    }

    public function student(): ?Student
    {
        return $this->filled('student')
            ? Student::query()->where('public_id', $this->string('student')->value())->firstOrFail()
            : null;
    }
}
