<?php

namespace App\Modules\Chat\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $conversation_id
 * @property int $user_id
 * @property CarbonImmutable|null $last_read_at
 * @property int|null $last_read_message_id
 * @property CarbonImmutable|null $archived_at
 * @property bool $muted
 * @property-read User $user
 * @property-read Conversation $conversation
 */
class ConversationParticipant extends Model
{
    protected $fillable = ['conversation_id', 'user_id', 'last_read_at', 'last_read_message_id', 'archived_at', 'muted'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_read_at' => 'immutable_datetime',
            'archived_at' => 'immutable_datetime',
            'muted' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Conversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function hasRead(Message $message): bool
    {
        return $this->last_read_message_id !== null && $this->last_read_message_id >= $message->id;
    }
}
