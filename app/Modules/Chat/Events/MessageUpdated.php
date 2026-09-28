<?php

namespace App\Modules\Chat\Events;

use App\Modules\Chat\Http\Resources\MessageResource;
use App\Modules\Chat\Models\Message;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Pesan diubah atau dihapus (payload `deleted: true`, isi dikosongkan).
 */
class MessageUpdated implements ShouldBroadcastNow
{
    use Dispatchable;

    public function __construct(public Message $message) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel($this->message->conversation->broadcastChannel());
    }

    public function broadcastAs(): string
    {
        return 'message.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'conversation' => $this->message->conversation->public_id,
            'message' => (new MessageResource($this->message))->resolve(),
        ];
    }
}
