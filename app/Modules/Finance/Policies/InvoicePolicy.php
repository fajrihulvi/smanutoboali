<?php

namespace App\Modules\Finance\Policies;

use App\Models\User;
use App\Modules\Access\Enums\Permission;
use App\Modules\Core\Enums\Role;
use App\Modules\Finance\Models\Invoice;
use App\Modules\People\Models\Student;

/**
 * Operator (finance.manage) mengelola semua; kepala sekolah melihat; orang tua hanya tagihan anaknya.
 */
class InvoicePolicy
{
    public function manage(User $user): bool
    {
        return $user->can(Permission::FinanceManage->value);
    }

    public function view(User $user, Invoice $invoice): bool
    {
        if ($this->manage($user)) {
            return true;
        }

        if (! $user->can(Permission::FinanceView->value)) {
            return false;
        }

        return $user->hasRoleEnum(Role::Principal) || $this->ownsStudent($user, $invoice);
    }

    /**
     * Orang tua mengunggah bukti transfer untuk tagihan anaknya yang masih bisa dibayar.
     */
    public function pay(User $user, Invoice $invoice): bool
    {
        return $user->can(Permission::FinanceView->value)
            && $invoice->status->isPayable()
            && $this->ownsStudent($user, $invoice);
    }

    private function ownsStudent(User $user, Invoice $invoice): bool
    {
        return $user->hasRoleEnum(Role::Guardian)
            && Student::query()->visibleTo($user, Role::Guardian)->whereKey($invoice->student_id)->exists();
    }
}
