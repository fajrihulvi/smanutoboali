<?php

namespace App\Modules\Cms\Actions;

use App\Modules\Cms\Services\WebsiteSettings;
use App\Modules\Core\Services\AuditLogger;

class SaveWebsiteSettingsAction
{
    public function __construct(private WebsiteSettings $settings, private AuditLogger $audit) {}

    /**
     * @param  array<string, string|null>  $values
     */
    public function handle(array $values): void
    {
        $this->settings->save($values);
        $this->audit->record('cms', 'settings_updated', 'Mengubah pengaturan website', properties: ['keys' => array_keys($values)]);
    }
}
