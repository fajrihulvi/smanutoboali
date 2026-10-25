<?php

namespace App\Modules\Auth\Http\Requests;

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use App\Support\PasswordStrength;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    use PasswordValidationRules;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, Rule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email', 'max:255'],
            // Akun dari email yang direset; email tidak terdaftar → aturan staf (reset tetap gagal).
            'password' => $this->passwordRules(PasswordStrength::forUser(
                User::query()->where('email', $this->string('email')->trim()->lower()->value())->first(),
            )),
        ];
    }
}
