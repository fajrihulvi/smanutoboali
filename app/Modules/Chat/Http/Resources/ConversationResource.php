<?php

namespace App\Modules\Chat\Http\Resources;

use App\Models\User;
use App\Modules\Chat\Models\Conversation;
use App\Modules\Chat\Services\ChatDirectory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Baris percakapan dari sudut pandang user yang login. Dibentuk dari ChatInbox
 * (kolom unread_count, me_archived_at, me_muted) dengan eager load latestMessage,
 * student, participants.user.roles.
 *
 * @mixin Conversation
 */
class ConversationResource extends JsonResource
{
    /** @var array<int, array{classroom: string, teachers: array<int, list<string>>}> */
    private array $teachersByStudent = [];

    private ?bool $canSend = null;

    /**
     * @param  array<int, array{classroom: string, teachers: array<int, list<string>>}>  $map
     */
    public function withTeacherMap(array $map): static
    {
        $this->teachersByStudent = $map;

        return $this;
    }

    public function withCanSend(bool $canSend): static
    {
        $this->canSend = $canSend;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User $viewer */
        $viewer = $request->user();
        $other = $this->otherParticipant($viewer);
        $otherUser = $other?->user;
        $latest = $this->latestMessage;

        $data = [
            'public_id' => $this->public_id,
            'other' => $otherUser ? [
                'public_id' => $otherUser->public_id,
                'name' => $otherUser->name,
                'role' => app(ChatDirectory::class)->describe($otherUser, $this->student, $this->teachersByStudent),
                'active' => $otherUser->is_active,
            ] : null,
            'student' => $this->student ? ['public_id' => $this->student->public_id, 'name' => $this->student->name] : null,
            'last_message' => $latest ? [
                'preview' => $latest->preview(),
                'is_mine' => $latest->sender_id === $viewer->getKey(),
                'created_at' => $latest->created_at->toIso8601String(),
            ] : null,
            'last_message_at' => $this->last_message_at?->toIso8601String(),
            'unread' => (int) $this->getAttribute('unread_count'),
            'archived' => $this->getAttribute('me_archived_at') !== null,
            'muted' => (bool) $this->getAttribute('me_muted'),
        ];

        if ($this->canSend !== null) {
            $data['can_send'] = $this->canSend;
        }

        return $data;
    }
}
