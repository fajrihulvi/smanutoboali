<?php

namespace App\Modules\ReportCard\Policies;

use App\Models\User;
use App\Modules\ReportCard\Models\ReportCard;
use App\Modules\ReportCard\Services\ReportCardAccess;

class ReportCardPolicy
{
    public function __construct(private ReportCardAccess $access) {}

    public function view(User $user, ReportCard $card): bool
    {
        return $this->access->canView($user, $card);
    }

    /**
     * Ubah isi (catatan, ekskul, prestasi): wali kelas selama status masih bisa diubah.
     */
    public function update(User $user, ReportCard $card): bool
    {
        return $card->status->isEditable() && $this->access->isHomeroom($user, $card->loadMissing('classroom')->classroom);
    }
}
