<?php

namespace App\Modules\Chat\Actions;

use App\Models\User;
use App\Modules\Chat\Enums\ConversationType;
use App\Modules\Chat\Models\Conversation;
use App\Modules\Chat\Models\ConversationParticipant;
use App\Modules\Chat\Services\ChatEligibility;
use App\Modules\People\Models\Student;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

/**
 * Mulai (atau buka kembali) percakapan 1:1. Satu percakapan per pasangan per konteks anak;
 * aman dari request ganda karena `direct_key` unik.
 */
class StartConversationAction
{
    public function __construct(private ChatEligibility $eligibility) {}

    /**
     * @throws AuthorizationException
     */
    public function handle(User $actor, User $target, ?Student $student = null): Conversation
    {
        if (! $this->eligibility->canChat($actor, $target, $student)) {
            throw new AuthorizationException('Anda tidak dapat memulai percakapan dengan pengguna ini.');
        }

        return DB::transaction(function () use ($actor, $target, $student) {
            $conversation = Conversation::query()->createOrFirst(
                ['direct_key' => Conversation::directKey($actor, $target, $student)],
                ['type' => ConversationType::Direct, 'student_id' => $student?->getKey(), 'created_by' => $actor->getKey()],
            );

            foreach ([$actor, $target] as $user) {
                ConversationParticipant::query()->firstOrCreate(['conversation_id' => $conversation->id, 'user_id' => $user->getKey()]);
            }

            // Membuka percakapan yang diarsipkan dari daftar kontak → kembali ke kotak masuk.
            ConversationParticipant::query()
                ->where('conversation_id', $conversation->id)
                ->where('user_id', $actor->getKey())
                ->update(['archived_at' => null]);

            return $conversation;
        });
    }
}
