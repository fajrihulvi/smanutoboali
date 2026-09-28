<?php

namespace App\Modules\Chat\Policies;

use App\Models\User;
use App\Modules\Chat\Models\Message;

/**
 * Pesan (dan lampirannya lewat FilePolicy) terlihat oleh peserta percakapan;
 * ubah/hapus hanya pengirim dalam batas waktu.
 */
class MessagePolicy
{
    public function view(User $user, Message $message): bool
    {
        return $user->can('view', $message->conversation);
    }

    public function update(User $user, Message $message): bool
    {
        return $message->isEditableBy($user) && $this->view($user, $message);
    }

    public function delete(User $user, Message $message): bool
    {
        return $message->isDeletableBy($user) && $this->view($user, $message);
    }
}
