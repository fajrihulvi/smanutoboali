<?php

namespace App\Modules\Chat\Events;

use App\Models\User;
use App\Modules\Chat\Models\Message;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Pemberitahuan pesan baru ke channel pribadi penerima: toast (bila percakapan tidak
 * sedang dibuka dan tidak dibisukan) dan pembaruan badge unread chat.
 */
class ChatMessageReceived implements ShouldBroadcastNow
{
    use Dispatchable;

    public function __construct(
        public Message $message,
        public User $recipient,
        public bool $muted,
        public int $unreadTotal,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel($this->recipient->receivesBroadcastNotificationsOn());
    }

    public function broadcastAs(): string
    {
        return 'chat.message';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'conversation' => $this->message->conversation->public_id,
            'message' => $this->message->public_id,
            'sender' => $this->message->sender?->name,
            'preview' => $this->message->preview(),
            'muted' => $this->muted,
            'unread_total' => $this->unreadTotal,
        ];
    }
}
