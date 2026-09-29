<?php

namespace App\Modules\Cms\Http\Resources;

use App\Modules\Cms\Models\WebsitePost;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Butuh eager load: category, featuredImage, author.
 *
 * @mixin WebsitePost
 */
class PostResource extends JsonResource
{
    use PresentsContent;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'type' => $this->type->value,
            'slug' => $this->slug,
            'title' => $this->title,
            'category' => $this->category ? ['public_id' => $this->category->public_id, 'name' => $this->category->name] : null,
            'excerpt' => $this->excerpt,
            'content' => $this->content ?? '',
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
            'status' => $this->statusData($this->status, $this->isScheduled()),
            'publish_at' => $this->publish_at?->toIso8601String(),
            'published_at' => $this->published_at?->toIso8601String(),
            'is_featured' => $this->is_featured,
            'event_starts_at' => $this->event_starts_at?->toIso8601String(),
            'event_ends_at' => $this->event_ends_at?->toIso8601String(),
            'event_location' => $this->event_location,
            'image' => $this->imageData($this->featuredImage),
            'author' => $this->author?->name,
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
