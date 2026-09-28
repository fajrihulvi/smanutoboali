<?php

namespace App\Modules\Chat\Policies;

use App\Models\User;
use App\Modules\Access\Enums\Permission;
use App\Modules\Chat\Models\Conversation;

/**
 * Percakapan hanya bisa diakses pesertanya (dan hanya bila user masih boleh memakai chat).
 */
class ConversationPolicy
{
    public function view(User $user, Conversation $conversation): bool
    {
        return $user->can(Permission::ChatUse->value) && $conversation->hasParticipant($user);
    }
}
