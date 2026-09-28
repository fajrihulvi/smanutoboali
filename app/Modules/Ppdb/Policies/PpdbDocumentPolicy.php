<?php

namespace App\Modules\Ppdb\Policies;

use App\Models\User;
use App\Modules\Ppdb\Models\PpdbDocument;
use Illuminate\Support\Facades\Gate;

/**
 * Dokumen mengikuti hak melihat pendaftarannya (dipakai FilePolicy).
 */
class PpdbDocumentPolicy
{
    public function view(User $user, PpdbDocument $document): bool
    {
        return Gate::forUser($user)->allows('view', $document->applicant);
    }
}
