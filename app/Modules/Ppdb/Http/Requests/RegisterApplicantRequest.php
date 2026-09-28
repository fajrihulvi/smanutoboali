<?php

namespace App\Modules\Ppdb\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterApplicantRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:150', Rule::unique(User::class, 'email')],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['email.unique' => 'Email sudah terdaftar. Silakan masuk atau gunakan lupa password.'];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['name' => 'nama lengkap', 'email' => 'email', 'password' => 'password'];
    }

    /**
     * @return array{name: string, email: string, password: string}
     */
    public function payload(): array
    {
        return [
            'name' => trim($this->string('name')->value()),
            'email' => mb_strtolower(trim($this->string('email')->value())),
            'password' => $this->string('password')->value(),
        ];
    }
}
