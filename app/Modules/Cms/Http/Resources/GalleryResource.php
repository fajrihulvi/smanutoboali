<?php

namespace App\Modules\Cms\Http\Resources;

use App\Modules\Cms\Models\WebsiteGallery;
use App\Modules\Cms\Models\WebsiteGalleryPhoto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Butuh eager load: photos.file (atau withCount('photos') + cover untuk daftar).
 *
 * @mixin WebsiteGallery
 */
class GalleryResource extends JsonResource
{
    use PresentsContent;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $photos = $this->relationLoaded('photos') ? $this->photos : collect();

        return [
            'public_id' => $this->public_id,
            'slug' => $this->slug,
            'title' => $this->title,
            'description' => $this->description,
            'date' => $this->date?->toDateString(),
            'status' => $this->statusData($this->status, $this->isScheduled()),
            'publish_at' => $this->publish_at?->toIso8601String(),
            'photos_count' => $this->photos_count ?? $photos->count(),
            'cover' => $this->imageData($photos->first()?->file),
            'photos' => $photos->map(fn (WebsiteGalleryPhoto $p) => [
                'public_id' => $p->public_id,
                'caption' => $p->caption,
                'url' => $this->imageData($p->file)['url'] ?? null,
                'thumb_url' => $this->imageData($p->file)['thumb_url'] ?? null,
            ])->values(),
        ];
    }
}
