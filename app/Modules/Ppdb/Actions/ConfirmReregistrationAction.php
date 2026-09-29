<?php

namespace App\Modules\Ppdb\Actions;

use App\Models\User;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\Finance\Actions\IssueInvoiceAction;
use App\Modules\Finance\Models\FeeType;
use App\Modules\People\Actions\AttachGuardianAction;
use App\Modules\People\Actions\CreateStudentAction;
use App\Modules\People\Enums\StudentStatus;
use App\Modules\People\Models\Guardian;
use App\Modules\People\Models\Student;
use App\Modules\Ppdb\Enums\ApplicantStatus;
use App\Modules\Ppdb\Models\PpdbApplicant;
use App\Modules\Ppdb\Models\PpdbApplicantGuardian;
use App\Modules\Ppdb\Notifications\ApplicantUpdateNotification;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Konfirmasi daftar ulang: buat Student + Guardian, tautkan akun pemohon ke Guardian kontak utama
 * (role applicant → guardian), opsional terbitkan tagihan daftar ulang, status RE_REGISTERED.
 *
 * @phpstan-type Fee array{fee_type: FeeType, amount: int, due_date: CarbonImmutable}
 */
class ConfirmReregistrationAction
{
    public function __construct(
        private CreateStudentAction $createStudent,
        private AttachGuardianAction $attach,
        private IssueInvoiceAction $issueInvoice,
        private AuditLogger $audit,
    ) {}

    /**
     * @param  Fee|null  $fee
     *
     * @throws ValidationException
     */
    public function handle(PpdbApplicant $applicant, string $nis, CarbonImmutable $entryDate, User $operator, ?array $fee = null): Student
    {
        $applicant->loadMissing(['period.academicYear', 'guardians', 'user']);

        if (! $applicant->status->canTransitionTo(ApplicantStatus::ReRegistered) || $applicant->period->results_published_at === null) {
            throw ValidationException::withMessages(['status' => 'Hanya pendaftar yang diterima (dan hasilnya sudah diumumkan) yang bisa didaftar ulang.']);
        }

        if (Student::withTrashed()->where('nis', $nis)->exists()) {
            throw ValidationException::withMessages(['nis' => "NIS {$nis} sudah dipakai."]);
        }

        if ($applicant->nisn && Student::withTrashed()->where('nisn', $applicant->nisn)->exists()) {
            throw ValidationException::withMessages(['nis' => "NISN {$applicant->nisn} sudah terdaftar sebagai siswa."]);
        }

        $student = DB::transaction(function () use ($applicant, $nis, $entryDate, $operator, $fee) {
            $student = $this->createStudent->handle([
                'nis' => $nis,
                'nisn' => $applicant->nisn,
                'name' => $applicant->name,
                'gender' => $applicant->gender,
                'birth_place' => $applicant->birth_place,
                'birth_date' => $applicant->birth_date?->toDateString(),
                'religion' => $applicant->religion,
                'address' => $applicant->address,
                'status' => StudentStatus::Active,
                'entry_date' => $entryDate->toDateString(),
            ], audit: false);

            $this->attachGuardians($applicant, $student);
            $this->convertAccount($applicant);

            $applicant->update([
                'student_id' => $student->id,
                'status' => ApplicantStatus::ReRegistered,
                'reregistered_at' => now(),
                'reregistered_by' => $operator->id,
            ]);

            if ($fee !== null && $fee['amount'] > 0) {
                $year = substr($applicant->period->academicYear->name, 0, 4);
                $this->issueInvoice->handle($student, $fee['fee_type'], "ppdb-{$year}", "PPDB {$applicant->period->academicYear->name}", $fee['amount'], $fee['due_date'], actor: $operator);
            }

            return $student;
        });

        $this->audit->record('ppdb', 'reregistered', "Daftar ulang {$applicant->registration_number}: siswa {$student->name} (NIS {$nis}) dibuat", $applicant, causer: $operator);
        $applicant->user->notify(new ApplicantUpdateNotification(ApplicantUpdateNotification::REREGISTERED, (string) $applicant->registration_number, (string) $applicant->name));

        return $student;
    }

    private function attachGuardians(PpdbApplicant $applicant, Student $student): void
    {
        $user = $applicant->user;
        $userGuardian = Guardian::query()->where('user_id', $user->id)->first();

        foreach ($applicant->guardians as $row) {
            /** @var PpdbApplicantGuardian $row */
            // Kontak utama = pemilik akun: pakai Guardian miliknya bila sudah ada (kakak kelas).
            $guardian = $row->is_primary && $userGuardian !== null
                ? $userGuardian
                : Guardian::query()->create([
                    'user_id' => $row->is_primary ? $user->id : null,
                    'name' => $row->name,
                    'nik' => $row->nik,
                    'phone' => $row->phone,
                    'email' => $row->is_primary ? ($row->email ?? $user->email) : $row->email,
                    'occupation' => $row->occupation,
                    'address' => $applicant->address,
                ]);

            $this->attach->handle($student, $guardian, $row->relationship, $row->is_primary, audit: false);
        }
    }

    /**
     * Akun pemohon menjadi orang tua; role applicant dilepas bila tidak ada pendaftaran lain yang masih diproses.
     */
    private function convertAccount(PpdbApplicant $applicant): void
    {
        $user = $applicant->user;
        $user->assignRole(Role::Guardian->value);

        $ongoing = PpdbApplicant::query()
            ->where('user_id', $user->id)
            ->whereKeyNot($applicant->id)
            ->whereNotIn('status', [ApplicantStatus::Rejected, ApplicantStatus::ReRegistered])
            ->exists();

        if (! $ongoing) {
            $user->removeRole(Role::Applicant->value);
        }

        $user->forceFill(['email_verified_at' => $user->email_verified_at ?? now()])->save();
    }
}
