<?php

namespace App\Modules\Ppdb\Database\Seeders;

use App\Models\User;
use App\Modules\Core\Enums\Role;
use App\Modules\Ppdb\Actions\SaveApplicationDraftAction;
use App\Modules\Ppdb\Actions\SavePeriodAction;
use App\Modules\Ppdb\Actions\StartApplicationAction;
use App\Modules\Ppdb\Actions\SubmitApplicationAction;
use App\Modules\Ppdb\Actions\UploadDocumentAction;
use App\Modules\Ppdb\Models\PpdbPeriod;
use App\Modules\Ppdb\Models\PpdbTrack;
use App\Modules\School\Actions\CreateAcademicYearAction;
use App\Modules\School\Models\AcademicYear;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;

/**
 * PPDB demo: periode tahun ajaran berikutnya yang sedang dibuka (Reguler, Prestasi, Afirmasi),
 * akun calon orang tua ppdb@skoolia.test dengan satu pendaftaran terkirim dan satu draf,
 * serta beberapa pendaftar lain untuk antrean verifikasi (S27).
 */
class PpdbDemoSeeder extends Seeder
{
    public function run(
        SavePeriodAction $savePeriod,
        StartApplicationAction $start,
        SaveApplicationDraftAction $save,
        UploadDocumentAction $upload,
        SubmitApplicationAction $submit,
        CreateAcademicYearAction $createYear,
    ): void {
        $operator = User::query()->where('email', 'operator@skoolia.test')->first();

        if (app()->isProduction() || PpdbPeriod::query()->exists() || ! $operator) {
            return;
        }

        $current = AcademicYear::query()->where('is_active', true)->first();
        $startYear = $current ? (int) substr($current->name, 0, 4) + 1 : (int) now()->format('Y') + 1;
        $target = AcademicYear::query()->where('name', $startYear.'/'.($startYear + 1))->first()
            ?? $createYear->handle(['name' => $startYear.'/'.($startYear + 1), 'start_date' => "{$startYear}-07-01", 'end_date' => ($startYear + 1).'-06-30']);

        $now = CarbonImmutable::now();
        $period = $savePeriod->handle([
            'name' => "PPDB {$target->name} Gelombang 1",
            'academic_year' => $target->public_id,
            'grade' => 7,
            'opens_at' => $now->subDays(10)->startOfDay()->toDateTimeString(),
            'closes_at' => $now->addDays(30)->endOfDay()->toDateTimeString(),
            'announces_at' => $now->addDays(40)->setTime(9, 0)->toDateTimeString(),
            'description' => "Pendaftaran peserta didik baru kelas 7 tahun ajaran {$target->name}. Siapkan akta kelahiran, kartu keluarga, dan pas foto.",
            'is_active' => true,
            'tracks' => [
                ['name' => 'Reguler', 'quota' => 90, 'description' => 'Jalur umum', 'requirements' => [
                    ['name' => 'Akta kelahiran', 'is_required' => true],
                    ['name' => 'Kartu keluarga', 'is_required' => true],
                    ['name' => 'Pas foto', 'description' => 'Latar merah/biru', 'is_required' => true],
                ]],
                ['name' => 'Prestasi', 'quota' => 20, 'description' => 'Prestasi akademik/non-akademik', 'requirements' => [
                    ['name' => 'Akta kelahiran', 'is_required' => true],
                    ['name' => 'Kartu keluarga', 'is_required' => true],
                    ['name' => 'Sertifikat prestasi', 'is_required' => true],
                ]],
                ['name' => 'Afirmasi', 'quota' => 10, 'description' => 'Pemegang KIP/KKS', 'requirements' => [
                    ['name' => 'Kartu keluarga', 'is_required' => true],
                    ['name' => 'Kartu KIP/KKS', 'is_required' => true],
                ]],
            ],
        ], $operator);

        $regular = $period->tracks()->where('name', 'Reguler')->firstOrFail();
        $achievement = $period->tracks()->where('name', 'Prestasi')->firstOrFail();

        $demo = $this->applicantAccount('ppdb@skoolia.test', 'Hendra Wijaya');
        $this->completeApplication($start, $save, $upload, $submit, $demo, $regular, 'Nadia Wijaya', 'P', '2014-03-12', 'Hendra Wijaya', '081277001122');
        $draft = $start->handle($demo, $regular);
        $save->handle($draft, ['name' => 'Raka Wijaya', 'gender' => 'L']);

        foreach ([['Dimas Saputra', 'L', 'Agus Saputra', $regular], ['Salsa Maharani', 'P', 'Rina Maharani', $achievement], ['Fajar Nugroho', 'L', 'Tono Nugroho', $regular]] as $i => [$child, $gender, $parent, $track]) {
            $account = $this->applicantAccount("pendaftar{$i}@skoolia.test", $parent);
            $this->completeApplication($start, $save, $upload, $submit, $account, $track, $child, $gender, '2014-0'.($i + 1).'-15', $parent, '0812330000'.$i);
        }
    }

    private function applicantAccount(string $email, string $name): User
    {
        $user = User::query()->firstOrCreate(['email' => $email], ['name' => $name, 'password' => 'password', 'is_active' => true]);
        $user->forceFill(['email_verified_at' => now()])->save();
        $user->assignRole(Role::Applicant->value);

        return $user;
    }

    private function completeApplication(
        StartApplicationAction $start,
        SaveApplicationDraftAction $save,
        UploadDocumentAction $upload,
        SubmitApplicationAction $submit,
        User $account,
        PpdbTrack $track,
        string $child,
        string $gender,
        string $birthDate,
        string $parent,
        string $phone,
    ): void {
        $applicant = $start->handle($account, $track);
        $save->handle($applicant, [
            'name' => $child, 'nik' => '19710'.str_pad((string) random_int(0, 99999999999), 11, '0', STR_PAD_LEFT), 'gender' => $gender,
            'birth_place' => 'Pangkalpinang', 'birth_date' => $birthDate, 'religion' => 'ISLAM', 'address' => 'Jl. Mentok No. '.random_int(1, 99).', Pangkalpinang',
            'previous_school' => 'SD Negeri '.random_int(1, 30).' Pangkalpinang',
            'guardians' => [
                ['relationship' => 'AYAH', 'name' => $parent, 'phone' => $phone, 'occupation' => 'Wiraswasta', 'is_primary' => true],
            ],
        ]);

        foreach ($track->requirements as $requirement) {
            $upload->handle($applicant, $requirement, UploadedFile::fake()->image(str($requirement->name)->slug().'.jpg', 800, 1100), $account);
        }

        $submit->handle($applicant->fresh() ?? $applicant);
    }
}
