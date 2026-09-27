<?php

namespace App\Modules\People\Actions;

use App\Modules\Access\Actions\ProvisionAccountAction;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\People\Models\Guardian;
use App\Modules\People\Models\Teacher;
use Illuminate\Validation\ValidationException;

/**
 * Tautkan data guru/orang tua ke akun login (buat baru atau pakai yang ada).
 */
class LinkPersonAccountAction
{
    public function __construct(
        private ProvisionAccountAction $provision,
        private AuditLogger $audit,
    ) {}

    /**
     * @return string|null password sementara (null bila memakai akun yang sudah ada)
     *
     * @throws ValidationException
     */
    public function handle(Teacher|Guardian $person, string $email, string $field = 'account_email'): ?string
    {
        if ($person->user_id !== null) {
            throw ValidationException::withMessages([$field => 'Data ini sudah memiliki akun login.']);
        }

        $role = $person instanceof Teacher ? Role::Teacher : Role::Guardian;

        $alreadyLinked = $person::query()
            ->whereHas('user', fn ($query) => $query->where('email', strtolower(trim($email))))
            ->exists();

        if ($alreadyLinked) {
            throw ValidationException::withMessages([
                $field => $person instanceof Teacher
                    ? 'Email ini sudah dipakai data guru lain.'
                    : 'Email ini sudah dipakai data orang tua lain.',
            ]);
        }

        $result = $this->provision->handle($person->name, $email, $role);

        $person->forceFill(['user_id' => $result['user']->getKey()])->save();

        $this->audit->record('people', 'account_linked', "Menautkan akun {$result['user']->email} ke {$person->name}", $person);

        return $result['temporary_password'];
    }
}
