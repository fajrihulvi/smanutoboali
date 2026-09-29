<?php

namespace App\Modules\Dashboard\Services;

use App\Models\User;
use App\Modules\Core\Services\AuditLogQueryService;
use Spatie\Activitylog\Models\Activity;

/**
 * Aktivitas sistem terbaru (audit log) untuk widget dashboard.
 */
class AuditFeed
{
    /**
     * @return list<array{key: string, module: string, description: string, causer: string|null, created_at: string|null}>
     */
    public function latest(int $limit = 8): array
    {
        return Activity::query()
            ->with(['causer' => fn ($q) => $q->select('id', 'name')])
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (Activity $a) => [
                'key' => (string) $a->id,
                'module' => AuditLogQueryService::label($a->log_name),
                'description' => $a->description,
                'causer' => $a->causer instanceof User ? $a->causer->name : null,
                'created_at' => $a->created_at?->toIso8601String(),
            ])->values()->all();
    }
}
