<?php

namespace App\Modules\GuestBook\Http\Resources;

use App\Modules\Core\Services\FileUrlGenerator;
use App\Modules\GuestBook\Models\GuestVisit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin GuestVisit
 */
class GuestVisitResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $urls = app(FileUrlGenerator::class);

        return [
            'public_id' => $this->public_id,
            'full_name' => $this->full_name,
            'institution' => $this->institution,
            'whatsapp' => $this->whatsapp,
            'whatsapp_url' => $this->whatsappLink(),
            'category' => ['value' => $this->category->value, 'label' => $this->category->label()],
            'purpose' => $this->purpose,
            'host' => ['value' => $this->host->value, 'label' => $this->host->label()],
            'notes' => $this->notes,
            'visited_at' => $this->visited_at->toIso8601String(),
            'source' => ['value' => $this->source->value, 'label' => $this->source->label()],
            'photo_url' => $this->whenLoaded('photo', fn () => $this->photo ? $urls->url($this->photo) : null),
            'photo_thumb_url' => $this->whenLoaded('photo', fn () => $this->photo ? $urls->thumbnailUrl($this->photo) : null),
            'created_by' => $this->whenLoaded('creator', fn () => $this->creator?->name),
            'can' => [
                'update' => $request->user()?->can('update', $this->resource) ?? false,
                'delete' => $request->user()?->can('delete', $this->resource) ?? false,
            ],
        ];
    }
}
