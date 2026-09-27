<?php

namespace App\Modules\Access\Http\Resources;

use App\Modules\Core\Enums\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\Permission\Models\Role as RoleModel;

/**
 * @mixin RoleModel
 */
class RoleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $core = Role::tryFrom($this->name);

        return [
            'code' => $this->name,
            'label' => $core?->label() ?? $this->name,
            'is_core' => $core !== null,
            'users_count' => $this->whenCounted('users'),
            'permissions_count' => $this->whenCounted('permissions'),
            'permissions' => $this->whenLoaded('permissions', fn () => $this->permissions->pluck('name')->sort()->values()->all()),
        ];
    }
}
