<?php

namespace App\Modules\People\Actions;

use App\Modules\Core\Services\AuditLogger;
use App\Modules\People\Models\Guardian;
use App\Modules\People\Models\Teacher;

/**
 * Lepaskan akun login dari data guru/orang tua. Akun & role-nya tetap ada
 * (bisa ditautkan ulang, dinonaktifkan, atau dihapus di User Management).
 */
class UnlinkPersonAccountAction
{
    public function __construct(private AuditLogger $audit) {}

    public function handle(Teacher|Guardian $person): void
    {
        $email = $person->user()->value('email');

        if ($email === null) {
            return;
        }

        $person->forceFill(['user_id' => null])->save();

        $this->audit->record('people', 'account_unlinked', "Melepas akun {$email} dari {$person->name}", $person);
    }
}
