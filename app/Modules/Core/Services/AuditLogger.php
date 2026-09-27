<?php

namespace App\Modules\Core\Services;

use Illuminate\Database\Eloquent\Model;

/**
 * Satu pintu pencatatan audit log (spatie/laravel-activitylog).
 * log_name = nama modul huruf kecil, contoh 'access', 'attendance'.
 */
class AuditLogger
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function record(
        string $module,
        string $event,
        string $description,
        ?Model $subject = null,
        array $properties = [],
        ?Model $causer = null,
    ): void {
        $logger = activity($module)
            ->event($event)
            ->withProperties($properties);

        if ($subject) {
            $logger->performedOn($subject);
        }

        $causer ??= auth()->user();

        if ($causer) {
            $logger->causedBy($causer);
        }

        $logger->log($description);
    }
}
