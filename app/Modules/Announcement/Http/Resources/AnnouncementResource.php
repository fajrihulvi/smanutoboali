<?php

namespace App\Modules\Announcement\Http\Resources;

use App\Modules\Academic\Models\Classroom;
use App\Modules\Announcement\Enums\AnnouncementAudience;
use App\Modules\Announcement\Models\Announcement;
use App\Modules\Core\Models\File;
use App\Modules\Core\Services\FileUrlGenerator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * Butuh eager load: attachments, classrooms, creator. `summary()` untuk daftar (isi dipotong).
 *
 * @mixin Announcement
 */
class AnnouncementResource extends JsonResource
{
    private bool $summary = false;

    public function summary(): static
    {
        $this->summary = true;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $urls = app(FileUrlGenerator::class);
        $status = $this->status();

        return [
            'public_id' => $this->public_id,
            'title' => $this->title,
            'body' => $this->summary ? Str::limit((string) preg_replace('/\s+/', ' ', $this->body), 180) : $this->body,
            'pinned' => $this->pinned,
            'status' => ['value' => $status->value, 'label' => $status->label()],
            'publish_at' => $this->publish_at?->toIso8601String(),
            'audience' => [
                'value' => $this->audience->value,
                'label' => $this->audienceLabel(),
            ],
            'roles' => $this->roles ?? [],
            'classrooms' => $this->classrooms->map(fn (Classroom $c) => ['public_id' => $c->public_id, 'name' => $c->name])->values(),
            'attachments' => $this->attachments->map(fn (File $f) => [
                'public_id' => $f->public_id,
                'name' => $f->original_name,
                'mime_type' => $f->mime_type,
                'is_image' => $f->isImage(),
                'size' => $f->size,
                'url' => $urls->url($f),
            ])->values(),
            'creator' => $this->creator?->name,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }

    private function audienceLabel(): string
    {
        return match ($this->audience) {
            AnnouncementAudience::All => 'Semua',
            AnnouncementAudience::Roles => implode(', ', array_map(fn ($r) => $r->label(), $this->targetRoles())),
            AnnouncementAudience::Classrooms => 'Rombel '.$this->classrooms->pluck('name')->implode(', '),
        };
    }
}
