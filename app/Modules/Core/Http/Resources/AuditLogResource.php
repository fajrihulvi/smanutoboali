<?php

namespace App\Modules\Core\Http\Resources;

use App\Models\User;
use App\Modules\Core\Services\AuditLogQueryService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\Activitylog\Models\Activity;

/**
 * @mixin Activity
 */
class AuditLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $properties = $this->properties?->toArray() ?? [];
        $causer = $this->causer;

        return [
            'key' => (string) $this->id,
            'module' => $this->log_name,
            'module_label' => AuditLogQueryService::label($this->log_name),
            'event' => $this->event,
            'description' => $this->description,
            'causer' => $causer instanceof User ? ['name' => $causer->name, 'email' => $causer->email] : null,
            'subject_type' => $this->subject_type ? class_basename($this->subject_type) : null,
            'ip' => $properties['ip'] ?? null,
            'user_agent' => $properties['user_agent'] ?? null,
            'changes' => array_diff_key($properties, array_flip(['ip', 'user_agent'])),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
