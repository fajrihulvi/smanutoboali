<?php

namespace App\Modules\Core\Actions;

use App\Modules\Core\Enums\FileDirectory;
use App\Modules\Core\Enums\FileVisibility;
use App\Modules\Core\Enums\SettingKey;
use App\Modules\Core\Models\File;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\Core\Services\FileService;
use App\Modules\Core\Services\SettingsService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class UpdateSchoolSettingsAction
{
    public function __construct(
        private SettingsService $settings,
        private FileService $files,
        private AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $values  key = SettingKey::value
     */
    public function handle(array $values, ?UploadedFile $logo = null, bool $removeLogo = false): void
    {
        DB::transaction(function () use ($values, $logo, $removeLogo) {
            $old = $this->settings->all();
            $currentLogo = $old[SettingKey::SchoolLogo->value];

            if ($logo) {
                $file = $this->files->store(
                    $logo,
                    FileDirectory::Settings,
                    FileVisibility::Public,
                    allowedMimes: FileService::IMAGE_MIMES,
                    maxKb: 2048,
                    field: 'logo',
                );
                $values[SettingKey::SchoolLogo->value] = $file->public_id;
            } elseif ($removeLogo) {
                $values[SettingKey::SchoolLogo->value] = null;
            }

            $replacedLogo = array_key_exists(SettingKey::SchoolLogo->value, $values) && $currentLogo;

            if ($replacedLogo) {
                $previous = File::query()->where('public_id', $currentLogo)->first();

                if ($previous) {
                    $this->files->delete($previous);
                }
            }

            $this->settings->set($values);

            $changed = array_filter(
                $values,
                fn (mixed $value, string $key) => ($old[$key] ?? null) !== $value,
                ARRAY_FILTER_USE_BOTH,
            );

            if ($changed !== []) {
                $this->audit->record('settings', 'updated', 'Mengubah pengaturan sekolah', properties: [
                    'old' => array_intersect_key($old, $changed),
                    'attributes' => $changed,
                ]);
            }
        });
    }
}
