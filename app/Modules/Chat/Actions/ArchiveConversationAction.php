<?php

namespace App\Modules\Chat\Actions;

use App\Models\User;
use App\Modules\Chat\Models\Conversation;
use App\Modules\Chat\Models\ConversationParticipant;

/**
 * Arsipkan/keluarkan dari arsip — hanya untuk user ini (lawan bicara tidak terpengaruh).
 */
class ArchiveConversationAction
{
    public function handle(Conversation $conversation, User $user, bool $archived): void
    {
        ConversationParticipant::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $user->getKey())
            ->update(['archived_at' => $archived ? now() : null]);
    }
}
