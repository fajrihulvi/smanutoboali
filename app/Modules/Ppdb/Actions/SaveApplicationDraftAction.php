<?php

namespace App\Modules\Ppdb\Actions;

use App\Modules\Ppdb\Models\PpdbApplicant;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Simpan data formulir (sebagian atau lengkap). Data orang tua disinkronkan per hubungan;
 * bila tidak ada yang ditandai utama, baris pertama menjadi kontak utama.
 */
class SaveApplicationDraftAction
{
    public const STUDENT_FIELDS = ['name', 'nik', 'nisn', 'gender', 'birth_place', 'birth_date', 'religion', 'address', 'previous_school'];

    /**
     * @param  array<string, mixed>  $data  sudah divalidasi
     */
    public function handle(PpdbApplicant $applicant, array $data): PpdbApplicant
    {
        DB::transaction(function () use ($applicant, $data) {
            $student = Arr::only($data, self::STUDENT_FIELDS);

            if ($student !== []) {
                $applicant->update(array_map(fn ($v) => $v === '' ? null : $v, $student));
            }

            if (array_key_exists('guardians', $data)) {
                /** @var list<array<string, mixed>> $rows */
                $rows = array_values(array_filter((array) $data['guardians'], fn ($r) => filled($r['name'] ?? null) || filled($r['phone'] ?? null)));
                $hasPrimary = collect($rows)->contains(fn ($r) => (bool) ($r['is_primary'] ?? false));

                $applicant->guardians()->delete();

                foreach ($rows as $i => $row) {
                    $applicant->guardians()->create([
                        'relationship' => $row['relationship'],
                        'name' => (string) ($row['name'] ?? ''),
                        'nik' => $row['nik'] ?? null,
                        'occupation' => $row['occupation'] ?? null,
                        'phone' => $row['phone'] ?? null,
                        'email' => $row['email'] ?? null,
                        'is_primary' => $hasPrimary ? (bool) ($row['is_primary'] ?? false) : $i === 0,
                    ]);
                }
            }
        });

        return $applicant;
    }
}
