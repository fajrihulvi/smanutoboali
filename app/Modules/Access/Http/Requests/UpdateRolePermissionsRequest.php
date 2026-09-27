<?php

namespace App\Modules\Access\Http\Requests;

use App\Modules\Access\Enums\Permission;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role as RoleModel;

class UpdateRolePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = $this->route('role');

        return $role instanceof RoleModel && ($this->user()?->can('update', $role) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'permissions' => ['present', 'array'],
            'permissions.*' => ['string', 'distinct', Rule::enum(Permission::class)],
        ];
    }

    /**
     * @return list<string>
     */
    public function permissions(): array
    {
        return array_values($this->array('permissions'));
    }
}
