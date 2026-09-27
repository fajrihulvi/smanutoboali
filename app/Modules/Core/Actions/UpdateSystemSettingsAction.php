<?php

namespace App\Modules\Core\Actions;

use App\Modules\Core\Services\AuditLogger;
use App\Modules\Core\Services\SettingsService;

/**
 * Simpan pengaturan sistem (tabel settings) dan catat perubahan ke audit log.
 */
class UpdateSystemSettingsAction
{
    public function __construct(
        private SettingsService $settings,
        private AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $values  key = SettingKey::value
     */
    public function handle(array $values): void
    {
        $old = $this->settings->all();

        $changed = array_filter(
            $values,
            fn (mixed $value, string $key) => ($old[$key] ?? null) !== $value,
            ARRAY_FILTER_USE_BOTH,
        );

        if ($changed === []) {
            return;
        }

        $this->settings->set($changed);

        $this->audit->record('settings', 'updated', 'Mengubah pengaturan sistem', properties: [
            'old' => array_intersect_key($old, $changed),
            'attributes' => $changed,
        ]);
    }
}
