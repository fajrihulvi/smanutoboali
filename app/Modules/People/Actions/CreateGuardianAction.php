<?php

namespace App\Modules\People\Actions;

use App\Modules\Core\Services\AuditLogger;
use App\Modules\People\Models\Guardian;
use Illuminate\Support\Facades\DB;

class CreateGuardianAction
{
    public function __construct(
        private LinkPersonAccountAction $linkAccount,
        private AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{guardian: Guardian, temporary_password: string|null}
     */
    public function handle(array $data, bool $createAccount = false): array
    {
        return DB::transaction(function () use ($data, $createAccount) {
            $guardian = Guardian::query()->create($data);

            // NIK tidak ikut dicatat ke audit log (data pribadi sensitif).
            $this->audit->record('people', 'created', "Menambah orang tua {$guardian->name}", $guardian, [
                'attributes' => array_diff_key($data, ['nik' => true]),
            ]);

            $password = $createAccount && $guardian->email
                ? $this->linkAccount->handle($guardian, $guardian->email, 'email')
                : null;

            return ['guardian' => $guardian, 'temporary_password' => $password];
        });
    }
}
