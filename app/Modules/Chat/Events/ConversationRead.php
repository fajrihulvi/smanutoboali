<?php

namespace App\Modules\Chat\Events;

use App\Models\User;
use App\Modules\Chat\Models\Conversation;
use Carbon\CarbonImmutable;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Peserta membaca percakapan sampai pesan tertentu (untuk centang "dibaca").
 */
class ConversationRead implements ShouldBroadcastNow
{
    use Dispatchable;

    public function __construct(
        public Conversation $conversation,
        public User $reader,
        public string $lastReadMessage,
        public CarbonImmutable $readAt,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel($this->conversation->broadcastChannel());
    }

    public function broadcastAs(): string
    {
        return 'conversation.read';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'conversation' => $this->conversation->public_id,
            'reader' => $this->reader->public_id,
            'last_read_message' => $this->lastReadMessage,
            'read_at' => $this->readAt->toIso8601String(),
        ];
    }
}
