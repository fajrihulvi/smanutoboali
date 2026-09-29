<?php

namespace App\Modules\Cms\Policies;

use App\Models\User;
use App\Modules\Access\Enums\Permission;
use App\Modules\Cms\Models\WebsiteBanner;
use App\Modules\Cms\Models\WebsiteCategory;
use App\Modules\Cms\Models\WebsiteGallery;
use App\Modules\Cms\Models\WebsitePage;
use App\Modules\Cms\Models\WebsitePost;

/**
 * Semua konten website dikelola pemegang `cms.manage` (termasuk menerbitkan).
 */
class WebsiteContentPolicy
{
    public function manage(User $user): bool
    {
        return $user->can(Permission::CmsManage->value);
    }

    public function view(User $user, WebsitePage|WebsitePost|WebsiteGallery|WebsiteBanner|WebsiteCategory $content): bool
    {
        return $this->manage($user);
    }

    public function update(User $user, WebsitePage|WebsitePost|WebsiteGallery|WebsiteBanner|WebsiteCategory $content): bool
    {
        return $this->manage($user);
    }

    public function delete(User $user, WebsitePage|WebsitePost|WebsiteGallery|WebsiteBanner|WebsiteCategory $content): bool
    {
        if ($content instanceof WebsitePage && $content->type->isSystem()) {
            return false;
        }

        return $this->manage($user);
    }
}
