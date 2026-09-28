<?php

namespace App\Modules\Chat\Actions;

use App\Models\User;
use App\Modules\Chat\Models\Conversation;
use App\Modules\Chat\Models\ConversationParticipant;

/**
 * Bisukan: pesan tetap dihitung belum dibaca, tetapi tanpa toast pemberitahuan.
 */
class MuteConversationAction
{
    public function handle(Conversation $conversation, User $user, bool $muted): void
    {
        ConversationParticipant::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $user->getKey())
            ->update(['muted' => $muted]);
    }
}
