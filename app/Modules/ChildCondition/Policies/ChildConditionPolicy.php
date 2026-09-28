<?php

namespace App\Modules\ChildCondition\Policies;

use App\Models\User;
use App\Modules\Access\Enums\Permission;
use App\Modules\ChildCondition\Enums\ConditionStatus;
use App\Modules\ChildCondition\Models\ChildCondition;
use App\Modules\ChildCondition\Services\ConditionAccess;

class ChildConditionPolicy
{
    public function __construct(private ConditionAccess $access) {}

    public function create(User $user): bool
    {
        return $user->can(Permission::ChildConditionSubmit->value);
    }

    /**
     * Juga dipakai FilePolicy untuk foto laporan.
     */
    public function view(User $user, ChildCondition $condition): bool
    {
        return $this->access->canView($user, $condition);
    }

    public function respond(User $user, ChildCondition $condition): bool
    {
        return $condition->status->isOpen() && $this->access->canRespond($user, $condition);
    }

    /**
     * Orang tua pelapor menarik laporan selama belum dibuka guru.
     */
    public function delete(User $user, ChildCondition $condition): bool
    {
        return $condition->guardian_user_id === $user->getKey()
            && $condition->status === ConditionStatus::Submitted
            && $user->can(Permission::ChildConditionSubmit->value);
    }
}
