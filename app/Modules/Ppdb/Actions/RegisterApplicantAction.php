<?php

namespace App\Modules\Ppdb\Actions;

use App\Models\User;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\Ppdb\Notifications\VerifyApplicantEmailNotification;
use Illuminate\Support\Facades\DB;

/**
 * Buat akun calon orang tua (role applicant) lalu kirim tautan verifikasi email.
 */
class RegisterApplicantAction
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  array{name: string, email: string, password: string}  $data
     */
    public function handle(array $data): User
    {
        $user = DB::transaction(function () use ($data) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'is_active' => true,
            ]);
            $user->assignRole(Role::Applicant->value);

            return $user;
        });

        $this->audit->record('ppdb', 'account_registered', "Akun PPDB baru: {$user->email}", $user, causer: $user);
        $user->notify(new VerifyApplicantEmailNotification);

        return $user;
    }
}
