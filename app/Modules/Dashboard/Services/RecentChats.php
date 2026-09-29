<?php

namespace App\Modules\Dashboard\Services;

use App\Models\User;
use App\Modules\Access\Enums\Permission;
use App\Modules\Chat\Models\Conversation;
use App\Modules\Chat\Services\ChatInbox;

/**
 * Chat terbaru untuk widget dashboard (tidak di-cache).
 */
class RecentChats
{
    public function __construct(private ChatInbox $inbox) {}

    /**
     * @return list<array{public_id: string, url: string, name: string, student: string|null, preview: string|null, is_mine: bool, unread: int, at: string|null}>
     */
    public function for(User $user, string $base, int $limit = 5): array
    {
        if (! $user->can(Permission::ChatUse->value)) {
            return [];
        }

        return $this->inbox->conversations($user, limit: $limit)
            ->map(function (Conversation $c) use ($user, $base) {
                $latest = $c->latestMessage;

                return [
                    'public_id' => $c->public_id,
                    'url' => "{$base}/{$c->public_id}",
                    'name' => $c->otherParticipant($user)?->user->name ?? 'Percakapan',
                    'student' => $c->student?->name,
                    'preview' => $latest?->preview(60),
                    'is_mine' => $latest !== null && $latest->sender_id === $user->getKey(),
                    'unread' => (int) $c->getAttribute('unread_count'),
                    'at' => $c->last_message_at?->toIso8601String(),
                ];
            })->values()->all();
    }
}
