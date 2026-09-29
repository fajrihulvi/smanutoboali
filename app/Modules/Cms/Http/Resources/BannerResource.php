<?php

namespace App\Modules\Cms\Http\Resources;

use App\Modules\Cms\Models\WebsiteBanner;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Butuh eager load: image.
 *
 * @mixin WebsiteBanner
 */
class BannerResource extends JsonResource
{
    use PresentsContent;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $now = now();
        $live = $this->is_active
            && ($this->starts_at === null || $this->starts_at->lte($now))
            && ($this->ends_at === null || $this->ends_at->gte($now));

        return [
            'public_id' => $this->public_id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'link_url' => $this->link_url,
            'link_label' => $this->link_label,
            'position' => $this->position,
            'is_active' => $this->is_active,
            'is_live' => $live,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'image' => $this->imageData($this->image),
        ];
    }
}
