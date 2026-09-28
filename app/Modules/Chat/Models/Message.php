<?php

namespace App\Modules\Chat\Models;

use App\Models\User;
use App\Modules\Chat\Enums\MessageType;
use App\Modules\Core\Models\File;
use App\Support\Concerns\HasPublicId;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $public_id
 * @property int $conversation_id
 * @property int|null $sender_id
 * @property string|null $body
 * @property MessageType $type
 * @property CarbonImmutable|null $edited_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Conversation $conversation
 * @property-read User|null $sender
 * @property-read File|null $attachment
 */
class Message extends Model
{
    use HasPublicId, SoftDeletes;

    /** Batas ubah pesan sendiri (menit). */
    public const EDIT_WINDOW_MINUTES = 15;

    /** Batas hapus pesan sendiri (jam). */
    public const DELETE_WINDOW_HOURS = 24;

    public const MAX_BODY = 4000;

    public const PER_PAGE = 30;

    protected $fillable = ['conversation_id', 'sender_id', 'body', 'type', 'edited_at'];

    protected $hidden = ['id', 'conversation_id', 'sender_id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => MessageType::class,
            'edited_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Conversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * @return MorphOne<File, $this>
     */
    public function attachment(): MorphOne
    {
        return $this->morphOne(File::class, 'fileable');
    }

    public function isEditableBy(User $user): bool
    {
        return ! $this->trashed()
            && $this->sender_id === $user->getKey()
            && $this->created_at->gt(now()->subMinutes(self::EDIT_WINDOW_MINUTES));
    }

    public function isDeletableBy(User $user): bool
    {
        return ! $this->trashed()
            && $this->sender_id === $user->getKey()
            && $this->created_at->gt(now()->subHours(self::DELETE_WINDOW_HOURS));
    }

    /**
     * Cuplikan satu baris untuk daftar percakapan & notifikasi.
     */
    public function preview(int $limit = 80): string
    {
        if ($this->trashed()) {
            return 'Pesan dihapus';
        }

        $text = trim((string) preg_replace('/\s+/', ' ', (string) $this->body));

        return $text !== '' ? Str::limit($text, $limit) : $this->type->placeholder();
    }
}
