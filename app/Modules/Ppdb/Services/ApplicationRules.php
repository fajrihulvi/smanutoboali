<?php

namespace App\Modules\Ppdb\Services;

use App\Modules\People\Enums\Gender;
use App\Modules\People\Enums\GuardianRelationship;
use App\Modules\People\Enums\Religion;
use App\Modules\Ppdb\Enums\DocumentStatus;
use App\Modules\Ppdb\Models\PpdbApplicant;
use App\Modules\Ppdb\Models\PpdbApplicantGuardian;
use App\Modules\Ppdb\Models\PpdbDocument;
use App\Modules\Ppdb\Models\PpdbDocumentRequirement;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Aturan validasi formulir PPDB — satu sumber untuk simpan per langkah dan submit.
 * Draf otomatis memakai versi "nullable" (boleh belum lengkap, tapi format tetap dicek).
 */
class ApplicationRules
{
    public const STEPS = ['siswa', 'orang-tua', 'dokumen', 'tinjau'];

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function student(bool $complete): array
    {
        $req = $complete ? 'required' : 'nullable';

        return [
            'name' => [$req, 'string', 'max:150'],
            'nik' => [$req, 'digits:16'],
            'nisn' => ['nullable', 'digits:10'],
            'gender' => [$req, Rule::enum(Gender::class)],
            'birth_place' => [$req, 'string', 'max:100'],
            'birth_date' => [$req, 'date_format:Y-m-d', 'before:today', 'after:'.now()->subYears(25)->toDateString()],
            'religion' => [$req, Rule::enum(Religion::class)],
            'address' => [$req, 'string', 'max:500'],
            'previous_school' => ['nullable', 'string', 'max:150'],
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function guardians(bool $complete): array
    {
        $req = $complete ? 'required' : 'nullable';

        return [
            'guardians' => [$complete ? 'required' : 'nullable', 'array', 'min:'.($complete ? 1 : 0), 'max:3'],
            'guardians.*.relationship' => ['required', 'distinct', Rule::enum(GuardianRelationship::class)],
            'guardians.*.name' => [$req, 'string', 'max:150'],
            'guardians.*.nik' => ['nullable', 'digits:16'],
            'guardians.*.occupation' => ['nullable', 'string', 'max:100'],
            'guardians.*.phone' => [$req, 'string', 'regex:/^\+?[0-9 \-]{8,20}$/'],
            'guardians.*.email' => ['nullable', 'email', 'max:150'],
            'guardians.*.is_primary' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function attributes(): array
    {
        return [
            'name' => 'nama calon siswa', 'nik' => 'NIK', 'nisn' => 'NISN', 'gender' => 'jenis kelamin',
            'birth_place' => 'tempat lahir', 'birth_date' => 'tanggal lahir', 'religion' => 'agama', 'address' => 'alamat',
            'previous_school' => 'asal sekolah', 'guardians' => 'data orang tua', 'guardians.*.name' => 'nama orang tua',
            'guardians.*.phone' => 'nomor telepon', 'guardians.*.nik' => 'NIK orang tua', 'guardians.*.email' => 'email orang tua',
            'guardians.*.relationship' => 'hubungan',
        ];
    }

    /**
     * Pastikan pendaftaran lengkap sebelum dikirim.
     *
     * @throws ValidationException
     */
    public function assertComplete(PpdbApplicant $applicant): void
    {
        $applicant->loadMissing(['guardians', 'documents.file', 'track.requirements']);

        $data = [
            'name' => $applicant->name,
            'nik' => $applicant->nik,
            'nisn' => $applicant->nisn,
            'gender' => $applicant->gender?->value,
            'birth_place' => $applicant->birth_place,
            'birth_date' => $applicant->birth_date?->toDateString(),
            'religion' => $applicant->religion?->value,
            'address' => $applicant->address,
            'previous_school' => $applicant->previous_school,
            'guardians' => $applicant->guardians->map(fn (PpdbApplicantGuardian $g) => [
                'relationship' => $g->relationship->value, 'name' => $g->name, 'nik' => $g->nik,
                'occupation' => $g->occupation, 'phone' => $g->phone, 'email' => $g->email, 'is_primary' => $g->is_primary,
            ])->all(),
        ];

        Validator::make($data, [...self::student(true), ...self::guardians(true)], [], self::attributes())->validate();

        $uploaded = $applicant->documents->filter(fn (PpdbDocument $d) => $d->file !== null && $d->status !== DocumentStatus::Invalid)
            ->pluck('ppdb_document_requirement_id')->all();
        $missing = $applicant->track->requirements
            ->filter(fn (PpdbDocumentRequirement $r) => $r->is_required && ! in_array($r->id, $uploaded, true))
            ->pluck('name');

        if ($missing->isNotEmpty()) {
            throw ValidationException::withMessages(['documents' => 'Lengkapi dokumen: '.$missing->implode(', ').'.']);
        }
    }
}
