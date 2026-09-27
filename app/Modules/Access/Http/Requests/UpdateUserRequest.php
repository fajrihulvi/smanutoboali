<?php

namespace App\Modules\Access\Http\Requests;

use App\Models\User;
use App\Modules\Core\Enums\Role;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->route('user');

        return $target instanceof User && ($this->user()?->can('update', $target) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var User $target */
        $target = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($target->getKey())],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', 'distinct', Rule::enum(Role::class)],
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
     * @return array{name: string, email: string, roles: list<string>}
     */
    public function payload(): array
    {
        return [
            'name' => $this->string('name')->trim()->value(),
            'email' => $this->string('email')->trim()->lower()->value(),
            'roles' => array_values($this->array('roles')),
        ];
    }
}
