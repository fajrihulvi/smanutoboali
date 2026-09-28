<?php

namespace App\Modules\Announcement\Policies;

use App\Models\User;
use App\Modules\Access\Enums\Permission;
use App\Modules\Announcement\Models\Announcement;
use App\Modules\Announcement\Services\AnnouncementAudienceResolver;

class AnnouncementPolicy
{
    public function __construct(private AnnouncementAudienceResolver $audience) {}

    public function manage(User $user): bool
    {
        return $user->can(Permission::AnnouncementManage->value);
    }

    /**
     * Juga dipakai FilePolicy untuk lampiran.
     */
    public function view(User $user, Announcement $announcement): bool
    {
        return $this->audience->canView($user, $announcement);
    }

    public function update(User $user, Announcement $announcement): bool
    {
        return $this->manage($user);
    }

    public function delete(User $user, Announcement $announcement): bool
    {
        return $this->manage($user);
    }
}
