<?php

namespace App\Modules\Core\Policies;

use App\Models\User;
use App\Modules\Core\Enums\FileVisibility;
use App\Modules\Core\Models\File;
use Illuminate\Support\Facades\Gate;

class FilePolicy
{
    /**
     * - File publik: boleh.
     * - Pengunggah: boleh.
     * - Selain itu: ikut policy `view` milik model pemilik file (fileable),
     *   mis. lampiran chat → ConversationPolicy, dokumen PPDB → ApplicantPolicy.
     * - File privat tanpa pemilik: hanya pengunggah.
     */
    public function view(User $user, File $file): bool
    {
        if ($file->visibility === FileVisibility::Public) {
            return true;
        }

        if ($file->uploaded_by !== null && $file->uploaded_by === $user->getKey()) {
            return true;
        }

        $owner = $file->fileable;

        return $owner !== null && Gate::forUser($user)->allows('view', $owner);
    }
}
