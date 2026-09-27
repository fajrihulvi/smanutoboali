<?php

namespace App\Modules\Core\Services;

use App\Modules\Core\Enums\SettingKey;
use App\Modules\Core\Models\File;

/**
 * Identitas sekolah untuk tampilan (shared props Inertia, kop PDF, email).
 */
class SchoolProfile
{
    public function __construct(
        private SettingsService $settings,
        private FileUrlGenerator $urls,
    ) {}

    public function name(): string
    {
        return (string) $this->settings->get(SettingKey::SchoolName);
    }

    public function logoUrl(): ?string
    {
        $publicId = $this->settings->get(SettingKey::SchoolLogo);

        if (! $publicId) {
            return null;
        }

        $file = File::query()->where('public_id', $publicId)->first();

        return $file ? $this->urls->url($file) : null;
    }

    /**
     * @return array{name: string, logo_url: string|null}
     */
    public function toArray(): array
    {
        return ['name' => $this->name(), 'logo_url' => $this->logoUrl()];
    }
}
