<?php

namespace App\Modules\Chat\Actions;

use App\Models\User;
use App\Modules\Chat\Events\ConversationRead;
use App\Modules\Chat\Models\Conversation;
use App\Modules\Chat\Models\ConversationParticipant;
use App\Modules\Chat\Models\Message;
use App\Support\Broadcasting\SafeBroadcast;

/**
 * Tandai percakapan dibaca sampai pesan terakhir; broadcast hanya bila ada perubahan.
 */
class MarkConversationReadAction
{
    public function handle(Conversation $conversation, User $user): bool
    {
        $latest = Message::withTrashed()->where('conversation_id', $conversation->id)->latest('id')->first(['id', 'public_id']);

        if ($latest === null) {
            return false;
        }

        $readAt = now();
        $updated = ConversationParticipant::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $user->getKey())
            ->where(fn ($q) => $q->whereNull('last_read_message_id')->orWhere('last_read_message_id', '<', $latest->id))
            ->update(['last_read_message_id' => $latest->id, 'last_read_at' => $readAt]);

        if ($updated === 0) {
            return false;
        }

        SafeBroadcast::event(new ConversationRead($conversation, $user, $latest->public_id, $readAt));

        return true;
    }
}
