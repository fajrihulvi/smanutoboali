<?php

namespace App\Modules\Access\Http\Resources;

use App\Models\User;
use App\Modules\Core\Enums\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'name' => $this->name,
            'email' => $this->email,
            'roles' => array_map(fn (Role $role) => $role->toArray(), $this->coreRoles()),
            'is_active' => $this->is_active,
            'is_self' => $request->user()?->is($this->resource) ?? false,
            'last_login_at' => $this->last_login_at?->toIso8601String(),
        ];
    }
}
