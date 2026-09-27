<?php

namespace App\Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'email' => 'email',
            'password' => 'password',
        ];
    }

    /**
     * @return array{email: string, password: string, remember: bool}
     */
    public function credentials(): array
    {
        return [
            'email' => $this->string('email')->trim()->lower()->value(),
            'password' => $this->string('password')->value(),
            'remember' => $this->boolean('remember'),
        ];
    }
}
