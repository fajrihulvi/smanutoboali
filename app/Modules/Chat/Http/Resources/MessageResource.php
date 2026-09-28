<?php

namespace App\Modules\Chat\Http\Resources;

use App\Models\User;
use App\Modules\Chat\Enums\MessageType;
use App\Modules\Chat\Models\ConversationParticipant;
use App\Modules\Chat\Models\Message;
use App\Modules\Core\Services\FileUrlGenerator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Pesan untuk respons HTTP (dengan sudut pandang user) dan payload broadcast (netral:
 * klien menentukan "pesan saya" dari sender.public_id). Butuh eager load: sender, attachment.
 *
 * @mixin Message
 */
class MessageResource extends JsonResource
{
    private ?User $viewer = null;

    /** Peserta lawan bicara, untuk status dibaca pesan milik viewer. */
    private ?ConversationParticipant $counterpart = null;

    public function viewedBy(User $viewer, ?ConversationParticipant $counterpart): static
    {
        $this->viewer = $viewer;
        $this->counterpart = $counterpart;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $deleted = $this->trashed();
        $attachment = $deleted ? null : $this->attachment;

        $data = [
            'public_id' => $this->public_id,
            'type' => $this->type->value,
            'body' => $deleted ? null : $this->body,
            'deleted' => $deleted,
            'sender' => $this->sender ? ['public_id' => $this->sender->public_id, 'name' => $this->sender->name] : null,
            'attachment' => $attachment ? [
                'public_id' => $attachment->public_id,
                'name' => $attachment->original_name,
                'mime_type' => $attachment->mime_type,
                'is_image' => $attachment->isImage(),
                'size' => $attachment->size,
                'width' => $attachment->width,
                'height' => $attachment->height,
                'url' => app(FileUrlGenerator::class)->url($attachment),
            ] : null,
            'created_at' => $this->created_at->toIso8601String(),
            'edited_at' => $this->edited_at?->toIso8601String(),
        ];

        if ($this->viewer !== null) {
            $mine = $this->sender_id === $this->viewer->getKey();
            $data['is_mine'] = $mine;
            $data['read'] = $mine && ($this->counterpart?->hasRead($this->resource) ?? false);
            $data['can'] = [
                'edit' => $this->isEditableBy($this->viewer) && $this->type === MessageType::Text,
                'delete' => $this->isDeletableBy($this->viewer),
            ];
        }

        return $data;
    }
}
