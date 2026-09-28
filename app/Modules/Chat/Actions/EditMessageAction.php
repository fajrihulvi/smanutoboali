<?php

namespace App\Modules\Chat\Actions;

use App\Modules\Chat\Events\MessageUpdated;
use App\Modules\Chat\Models\Message;
use App\Support\Broadcasting\SafeBroadcast;

/**
 * Ubah teks pesan sendiri (batas waktu dicek di MessagePolicy).
 */
class EditMessageAction
{
    public function handle(Message $message, string $body): Message
    {
        $message->update(['body' => trim($body), 'edited_at' => now()]);
        $message->loadMissing(['conversation', 'sender', 'attachment']);

        SafeBroadcast::event(new MessageUpdated($message));

        return $message;
    }
}
