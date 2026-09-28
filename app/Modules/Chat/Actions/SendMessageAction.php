<?php

namespace App\Modules\Chat\Actions;

use App\Models\User;
use App\Modules\Chat\Enums\MessageType;
use App\Modules\Chat\Events\ChatMessageReceived;
use App\Modules\Chat\Events\MessageSent;
use App\Modules\Chat\Models\Conversation;
use App\Modules\Chat\Models\ConversationParticipant;
use App\Modules\Chat\Models\Message;
use App\Modules\Chat\Services\ChatEligibility;
use App\Modules\Chat\Services\ChatInbox;
use App\Modules\Core\Enums\FileDirectory;
use App\Modules\Core\Enums\FileVisibility;
use App\Modules\Core\Services\FileService;
use App\Support\Broadcasting\SafeBroadcast;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Simpan pesan (dan lampiran) ke database lebih dulu, lalu broadcast langsung:
 * MessageSent ke channel percakapan, ChatMessageReceived ke channel pribadi penerima.
 */
class SendMessageAction
{
    public const MAX_ATTACHMENT_KB = 5120;

    public function __construct(
        private ChatEligibility $eligibility,
        private FileService $files,
        private ChatInbox $inbox,
    ) {}

    /**
     * @throws ValidationException
     */
    public function handle(Conversation $conversation, User $sender, ?string $body, ?UploadedFile $upload = null): Message
    {
        $body = $body !== null ? trim($body) : null;
        $body = $body === '' ? null : $body;

        if ($body === null && $upload === null) {
            throw ValidationException::withMessages(['body' => 'Tulis pesan atau lampirkan berkas.']);
        }

        $conversation->loadMissing(['participants.user', 'student']);
        $recipients = $conversation->participants->filter(fn (ConversationParticipant $p) => $p->user_id !== $sender->getKey());

        if (! $this->canSend($conversation, $sender)) {
            throw ValidationException::withMessages(['body' => 'Percakapan ini hanya bisa dibaca karena Anda tidak lagi terhubung dengan pengguna ini.']);
        }

        $message = DB::transaction(function () use ($conversation, $sender, $body, $upload, $recipients) {
            $message = Message::query()->create([
                'conversation_id' => $conversation->id,
                'sender_id' => $sender->getKey(),
                'body' => $body,
                'type' => MessageType::Text,
            ]);

            if ($upload !== null) {
                $file = $this->files->store(
                    $upload, FileDirectory::Chat, FileVisibility::Private, $message,
                    allowedMimes: [...FileService::IMAGE_MIMES, ...FileService::DOCUMENT_MIMES],
                    maxKb: self::MAX_ATTACHMENT_KB, uploader: $sender, field: 'file',
                );
                $message->update(['type' => $file->isImage() ? MessageType::Image : MessageType::File]);
                $message->setRelation('attachment', $file);
            } else {
                $message->setRelation('attachment', null);
            }

            $conversation->update(['last_message_at' => $message->created_at]);

            ConversationParticipant::query()
                ->where('conversation_id', $conversation->id)
                ->where('user_id', $sender->getKey())
                ->update(['last_read_message_id' => $message->id, 'last_read_at' => now(), 'archived_at' => null]);

            // Pesan baru mengeluarkan percakapan dari arsip penerima agar tidak terlewat.
            ConversationParticipant::query()
                ->whereKey($recipients->modelKeys())
                ->update(['archived_at' => null]);

            return $message;
        });

        $message->setRelation('conversation', $conversation);
        $message->setRelation('sender', $sender);

        SafeBroadcast::event(new MessageSent($message));

        foreach ($recipients as $recipient) {
            SafeBroadcast::event(new ChatMessageReceived($message, $recipient->user, $recipient->muted, $this->inbox->unreadTotal($recipient->user)));
        }

        return $message;
    }

    /**
     * Masih boleh mengirim: lawan bicara ada dan hubungan (eligibility) masih berlaku.
     */
    public function canSend(Conversation $conversation, User $sender): bool
    {
        $conversation->loadMissing(['participants.user', 'student']);
        $other = $conversation->otherParticipant($sender)?->user;

        return $other !== null && $this->eligibility->canChat($sender, $other, $conversation->student);
    }
}
