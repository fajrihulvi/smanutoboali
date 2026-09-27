<?php

namespace App\Modules\Access\Http\Requests;

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use App\Modules\Core\Enums\Role;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    use PasswordValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'password' => $this->passwordRules(),
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', 'distinct', Rule::enum(Role::class)],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama',
            'roles' => 'role',
            'roles.*' => 'role',
        ];
    }

    /**
     * @return array{name: string, email: string, password: string, roles: list<string>, is_active: bool}
     */
    public function payload(): array
    {
        return [
            'name' => $this->string('name')->trim()->value(),
            'email' => $this->string('email')->trim()->lower()->value(),
            'password' => $this->string('password')->value(),
            'roles' => array_values($this->array('roles')),
            'is_active' => $this->boolean('is_active', true),
        ];
    }
}
