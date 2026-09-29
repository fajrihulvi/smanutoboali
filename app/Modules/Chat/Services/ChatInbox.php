<?php

namespace App\Modules\Chat\Services;

use App\Models\User;
use App\Modules\Chat\Models\Conversation;
use App\Modules\Chat\Models\Message;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

/**
 * Daftar percakapan milik user beserta jumlah pesan belum dibaca.
 * Belum dibaca = pesan orang lain (tidak terhapus) dengan id > last_read_message_id peserta.
 */
class ChatInbox
{
    public const LIMIT = 100;

    /**
     * @return Collection<int, Conversation>
     */
    public function conversations(User $user, bool $archived = false, ?string $search = null, int $limit = self::LIMIT): Collection
    {
        $search = trim((string) $search);

        return $this->forUser($user)
            ->when($archived, fn (Builder $q) => $q->whereNotNull('me.archived_at'), fn (Builder $q) => $q->whereNull('me.archived_at'))
            ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->whereHas('users', fn (Builder $u) => $u->whereKeyNot($user->getKey())->where('users.name', 'like', "%{$search}%"))
                ->orWhereHas('student', fn (Builder $s) => $s->where('students.name', 'like', "%{$search}%"))))
            ->whereNotNull('conversations.last_message_at')
            ->orderByDesc('conversations.last_message_at')
            ->orderByDesc('conversations.id')
            ->limit($limit)
            ->with(['latestMessage', 'student', 'participants.user.roles'])
            ->get();
    }

    /**
     * Satu percakapan (untuk halaman/ruang chat) dengan kolom yang sama seperti daftar.
     */
    public function find(User $user, Conversation $conversation): ?Conversation
    {
        return $this->forUser($user)
            ->whereKey($conversation->getKey())
            ->with(['latestMessage', 'student', 'participants.user.roles'])
            ->first();
    }

    public function unreadTotal(User $user): int
    {
        return (int) Message::query()
            ->join('conversation_participants as me', fn (JoinClause $j) => $j
                ->on('me.conversation_id', '=', 'messages.conversation_id')
                ->where('me.user_id', $user->getKey()))
            ->where(fn (Builder $q) => $q->whereNull('messages.sender_id')->orWhere('messages.sender_id', '!=', $user->getKey()))
            ->whereRaw('messages.id > COALESCE(me.last_read_message_id, 0)')
            ->count();
    }

    /**
     * Percakapan tempat user menjadi peserta + kolom milik peserta (me_*) + unread_count.
     *
     * @return Builder<Conversation>
     */
    private function forUser(User $user): Builder
    {
        $unread = Message::query()
            ->selectRaw('COUNT(*)')
            ->whereColumn('messages.conversation_id', 'conversations.id')
            ->where(fn (Builder $q) => $q->whereNull('messages.sender_id')->orWhere('messages.sender_id', '!=', $user->getKey()))
            ->whereRaw('messages.id > COALESCE(me.last_read_message_id, 0)');

        return Conversation::query()
            ->join('conversation_participants as me', fn (JoinClause $j) => $j
                ->on('me.conversation_id', '=', 'conversations.id')
                ->where('me.user_id', $user->getKey()))
            ->select('conversations.*')
            ->addSelect([DB::raw('me.archived_at as me_archived_at'), DB::raw('me.muted as me_muted')])
            ->selectSub($unread, 'unread_count');
    }
}
