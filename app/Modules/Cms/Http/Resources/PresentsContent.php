<?php

namespace App\Modules\Cms\Http\Resources;

use App\Modules\Cms\Enums\ContentStatus;
use App\Modules\Core\Models\File;
use App\Modules\Core\Services\FileUrlGenerator;

/**
 * Bagian bersama resource konten: status (termasuk "terjadwal") & gambar.
 */
trait PresentsContent
{
    /**
     * @return array{value: string, label: string, scheduled: bool}
     */
    protected function statusData(ContentStatus $status, bool $scheduled): array
    {
        return [
            'value' => $status->value,
            'label' => $scheduled ? 'Terjadwal' : $status->label(),
            'scheduled' => $scheduled,
        ];
    }

    /**
     * @return array{public_id: string, url: string}|null
     */
    protected function imageData(?File $file): ?array
    {
        return $file ? ['public_id' => $file->public_id, 'url' => app(FileUrlGenerator::class)->url($file)] : null;
    }
}
