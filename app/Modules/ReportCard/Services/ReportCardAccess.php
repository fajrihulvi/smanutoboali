<?php

namespace App\Modules\ReportCard\Services;

use App\Models\User;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Access\Enums\Permission;
use App\Modules\Core\Enums\Role;
use App\Modules\People\Models\Student;
use App\Modules\People\Models\Teacher;
use App\Modules\ReportCard\Enums\ReportCardStatus;
use App\Modules\ReportCard\Models\ReportCard;

/**
 * Siapa boleh melihat raport dan menjalankan transisi apa.
 *
 * | Transisi                              | Pelaku                         |
 * |---------------------------------------|--------------------------------|
 * | DRAFT → SUBMITTED                     | sistem (semua mapel dikonfirmasi) |
 * | REVISION → SUBMITTED (ajukan ulang)   | wali kelas                     |
 * | SUBMITTED → HOMEROOM_REVIEWED/REVISION| wali kelas                     |
 * | HOMEROOM_REVIEWED → VALIDATED/REVISION| operator                       |
 * | VALIDATED → APPROVED/REVISION         | kepala sekolah                 |
 * | APPROVED → PUBLISHED                  | operator                       |
 * | APPROVED → REVISION                   | kepala sekolah / operator      |
 * | PUBLISHED → REVISION (tarik)          | operator                       |
 */
class ReportCardAccess
{
    public function isHomeroom(User $user, Classroom $classroom): bool
    {
        if (! $user->can(Permission::ReportCardReview->value)) {
            return false;
        }

        $teacher = Teacher::activeForUser($user);

        return $teacher !== null && $classroom->homeroom_teacher_id === $teacher->id;
    }

    public function isOperator(User $user): bool
    {
        return $user->hasRoleEnum(Role::Operator) && $user->can(Permission::ReportCardValidate->value);
    }

    public function isPrincipal(User $user): bool
    {
        return $user->hasRoleEnum(Role::Principal) && $user->can(Permission::ReportCardApprove->value);
    }

    public function canView(User $user, ReportCard $card): bool
    {
        $card->loadMissing('classroom');

        if ($this->isOperator($user) || $this->isPrincipal($user) || $this->isHomeroom($user, $card->classroom)) {
            return true;
        }

        return $card->status === ReportCardStatus::Published
            && $user->hasRoleEnum(Role::Guardian)
            && Student::query()->visibleTo($user, Role::Guardian)->whereKey($card->student_id)->exists();
    }

    public function canPerform(User $user, ReportCard $card, ReportCardStatus $target): bool
    {
        $card->loadMissing('classroom');
        $homeroom = $this->isHomeroom($user, $card->classroom);

        return match ([$card->status, $target]) {
            [ReportCardStatus::Revision, ReportCardStatus::Submitted],
            [ReportCardStatus::Submitted, ReportCardStatus::HomeroomReviewed],
            [ReportCardStatus::Submitted, ReportCardStatus::Revision] => $homeroom,
            [ReportCardStatus::HomeroomReviewed, ReportCardStatus::Validated],
            [ReportCardStatus::HomeroomReviewed, ReportCardStatus::Revision],
            [ReportCardStatus::Approved, ReportCardStatus::Published],
            [ReportCardStatus::Published, ReportCardStatus::Revision] => $this->isOperator($user),
            [ReportCardStatus::Validated, ReportCardStatus::Approved],
            [ReportCardStatus::Validated, ReportCardStatus::Revision] => $this->isPrincipal($user),
            [ReportCardStatus::Approved, ReportCardStatus::Revision] => $this->isPrincipal($user) || $this->isOperator($user),
            default => false,
        };
    }

    /**
     * Transisi yang boleh dijalankan user ini dari status raport saat ini (untuk tombol UI).
     *
     * @return list<ReportCardStatus>
     */
    public function availableTransitions(User $user, ReportCard $card): array
    {
        return array_values(array_filter(
            $card->status->allowedTransitions(),
            fn (ReportCardStatus $target) => $this->canPerform($user, $card, $target),
        ));
    }
}
