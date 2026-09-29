<?php

namespace App\Modules\Cms\Http\Resources;

use App\Modules\Cms\Models\WebsitePage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Butuh eager load: featuredImage.
 *
 * @mixin WebsitePage
 */
class PageResource extends JsonResource
{
    use PresentsContent;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'type' => ['value' => $this->type->value, 'label' => $this->type->label()],
            'is_system' => $this->type->isSystem(),
            'slug' => $this->slug,
            'title' => $this->title,
            'content' => $this->content ?? '',
            'excerpt' => $this->excerpt,
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
            'status' => $this->statusData($this->status, $this->isScheduled()),
            'publish_at' => $this->publish_at?->toIso8601String(),
            'published_at' => $this->published_at?->toIso8601String(),
            'show_in_menu' => $this->show_in_menu,
            'position' => $this->position,
            'image' => $this->imageData($this->featuredImage),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
