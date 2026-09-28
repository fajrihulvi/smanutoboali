<?php

namespace App\Modules\Chat\Models;

use App\Models\User;
use App\Modules\Chat\Enums\ConversationType;
use App\Modules\People\Models\Student;
use App\Support\Concerns\HasPublicId;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Percakapan 1:1 antar user, opsional dengan konteks anak (student).
 *
 * @property int $id
 * @property string $public_id
 * @property ConversationType $type
 * @property string|null $direct_key
 * @property int|null $student_id
 * @property int|null $created_by
 * @property CarbonImmutable|null $last_message_at
 * @property CarbonImmutable $created_at
 * @property-read Student|null $student
 * @property-read Collection<int, ConversationParticipant> $participants
 * @property-read Message|null $latestMessage
 */
class Conversation extends Model
{
    use HasPublicId;

    protected $fillable = ['type', 'direct_key', 'student_id', 'created_by', 'last_message_at'];

    protected $hidden = ['id', 'direct_key', 'student_id', 'created_by'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ConversationType::class,
            'last_message_at' => 'immutable_datetime',
        ];
    }

    /**
     * Kunci unik percakapan langsung: urutan user tidak berpengaruh.
     */
    public static function directKey(User $a, User $b, ?Student $student = null): string
    {
        $ids = [(int) $a->getKey(), (int) $b->getKey()];
        sort($ids);

        return $ids[0].':'.$ids[1].':'.($student?->getKey() ?? 0);
    }

    /**
     * @return HasMany<ConversationParticipant, $this>
     */
    public function participants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants');
    }

    /**
     * @return HasMany<Message, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * @return HasOne<Message, $this>
     */
    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function participantFor(User $user): ?ConversationParticipant
    {
        return $this->relationLoaded('participants')
            ? $this->participants->firstWhere('user_id', $user->getKey())
            : $this->participants()->where('user_id', $user->getKey())->first();
    }

    public function hasParticipant(User $user): bool
    {
        return $this->participantFor($user) !== null;
    }

    /**
     * Lawan bicara (percakapan 1:1). Butuh participants.user.
     */
    public function otherParticipant(User $user): ?ConversationParticipant
    {
        return $this->participants->first(fn (ConversationParticipant $p) => $p->user_id !== $user->getKey());
    }

    public function broadcastChannel(): string
    {
        return 'conversation.'.$this->public_id;
    }
}
