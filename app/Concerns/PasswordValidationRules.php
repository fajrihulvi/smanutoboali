<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Validation\Rules\Password;

trait PasswordValidationRules
{
    /**
     * Get the validation rules used to validate passwords.
     *
     * @param  Password|null  $strength  aturan kekuatan (lihat PasswordStrength); default = aturan staf
     * @return array<int, Rule|array<mixed>|string>
     */
    protected function passwordRules(?Password $strength = null): array
    {
        return ['required', 'string', $strength ?? Password::default(), 'confirmed'];
    }

    /**
     * Get the validation rules used to validate the current password.
     *
     * @return array<int, Rule|array<mixed>|string>
     */
    protected function currentPasswordRules(): array
    {
        return ['required', 'string', 'current_password'];
    }
}
