<?php

namespace App\Modules\Access\Http\Requests;

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use App\Support\PasswordStrength;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ResetUserPasswordRequest extends FormRequest
{
    use PasswordValidationRules;

    public function authorize(): bool
    {
        $target = $this->route('user');

        return $target instanceof User && ($this->user()?->can('resetPassword', $target) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $target = $this->route('user');

        return [
            'password' => $this->passwordRules(PasswordStrength::forUser($target instanceof User ? $target : null)),
        ];
    }
}
