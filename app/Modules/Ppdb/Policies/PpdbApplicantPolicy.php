<?php

namespace App\Modules\Ppdb\Policies;

use App\Models\User;
use App\Modules\Access\Enums\Permission;
use App\Modules\Ppdb\Enums\ApplicantStatus;
use App\Modules\Ppdb\Models\PpdbApplicant;

/**
 * Pemilik akun melihat & mengubah pendaftarannya sendiri; operator PPDB & kepala sekolah melihat semua.
 */
class PpdbApplicantPolicy
{
    public function view(User $user, PpdbApplicant $applicant): bool
    {
        return $applicant->user_id === $user->id
            || $user->can(Permission::PpdbManage->value)
            || $user->can(Permission::PpdbDecide->value);
    }

    /**
     * Ubah formulir: pemilik, status DRAF/PERLU PERBAIKAN; draf hanya selama periode dibuka.
     */
    public function update(User $user, PpdbApplicant $applicant): bool
    {
        if ($applicant->user_id !== $user->id || ! $applicant->status->isEditable()) {
            return false;
        }

        return $applicant->status === ApplicantStatus::NeedsRevision || $applicant->period->isOpen();
    }

    /**
     * Pemilik boleh menghapus draf yang belum pernah dikirim.
     */
    public function delete(User $user, PpdbApplicant $applicant): bool
    {
        return $applicant->user_id === $user->id && $applicant->status === ApplicantStatus::Draft && $applicant->registration_number === null;
    }
}
