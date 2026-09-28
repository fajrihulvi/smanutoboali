<?php

namespace App\Modules\Chat\Actions;

use App\Modules\Chat\Events\MessageUpdated;
use App\Modules\Chat\Models\Message;
use App\Modules\Core\Services\FileService;
use App\Support\Broadcasting\SafeBroadcast;
use Illuminate\Support\Facades\DB;

/**
 * Hapus pesan sendiri: soft delete (tampil "Pesan dihapus") dan lampiran ikut dihapus.
 */
class DeleteMessageAction
{
    public function __construct(private FileService $files) {}

    public function handle(Message $message): Message
    {
        DB::transaction(function () use ($message) {
            if ($message->attachment !== null) {
                $this->files->delete($message->attachment);
            }

            $message->delete();
        });

        $message->setRelation('attachment', null);
        $message->loadMissing(['conversation', 'sender']);

        SafeBroadcast::event(new MessageUpdated($message));

        return $message;
    }
}
