<?php

namespace App\Modules\Cms\Http\Resources;

use App\Modules\Core\Services\FileUrlGenerator;
use App\Modules\People\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Butuh eager load: photo.
 *
 * @mixin Teacher
 */
class TeacherWebsiteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'name' => $this->name,
            'photo_url' => $this->photo ? app(FileUrlGenerator::class)->thumbnailUrl($this->photo) : null,
            'show_on_website' => $this->show_on_website,
            'website_title' => $this->website_title,
        ];
    }
}
