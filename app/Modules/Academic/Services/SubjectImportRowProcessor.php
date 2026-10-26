<?php

namespace App\Modules\Academic\Services;

use App\Modules\Academic\Actions\SaveSubjectAction;
use App\Modules\Academic\Enums\SubjectGroup;
use App\Modules\Academic\Models\Subject;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Proses satu baris template import mata pelajaran. Kode yang sudah terdaftar dilewati
 * (dilaporkan), bukan menimpa mapel yang ada.
 */
class SubjectImportRowProcessor
{
    /**
     * Kolom template (baris judul). Urutan = urutan di file template.
     *
     * @var list<string>
     */
    public const COLUMNS = ['kode', 'nama', 'kelompok', 'aktif'];

    private const ACTIVE_VALUES = ['YA', 'Y', 'AKTIF', '1', 'TRUE'];

    private const INACTIVE_VALUES = ['TIDAK', 'T', 'NONAKTIF', 'TIDAK AKTIF', '0', 'FALSE'];

    public function __construct(private SaveSubjectAction $saveSubject) {}

    /**
     * @param  array<string, mixed>  $raw  satu baris (key = judul kolom)
     * @return list<string> pesan error; kosong = berhasil
     */
    public function process(array $raw): array
    {
        $row = $this->normalize($raw);

        // Aturan sama dengan form mapel (SubjectRequest).
        $validator = Validator::make($row, [
            'kode' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9.\-]+$/'],
            'nama' => ['required', 'string', 'max:100'],
            'kelompok' => ['required'],
        ], [
            'kode.regex' => 'Kode hanya boleh berisi huruf, angka, titik, dan tanda hubung.',
        ], [
            'kode' => 'kode', 'nama' => 'nama mata pelajaran', 'kelompok' => 'kelompok',
        ]);

        $errors = $validator->errors()->all();
        $group = $this->group($row['kelompok']);
        $active = $this->active($row['aktif']);

        if ($row['kelompok'] !== null && $group === null) {
            $allowed = implode(', ', array_map(fn (SubjectGroup $g) => $g->label(), SubjectGroup::cases()));
            $errors[] = "Kelompok \"{$row['kelompok']}\" tidak dikenali (pilih {$allowed}).";
        }

        if ($active === null) {
            $errors[] = "Kolom aktif \"{$row['aktif']}\" tidak dikenali (isi Ya atau Tidak).";
        }

        if ($row['kode'] !== null && Subject::query()->where('code', $row['kode'])->exists()) {
            $errors[] = "Kode {$row['kode']} sudah terdaftar.";
        }

        if ($errors !== [] || $group === null || $active === null) {
            return array_values($errors);
        }

        try {
            $this->saveSubject->handle([
                'code' => (string) $row['kode'],
                'name' => (string) $row['nama'],
                'group' => $group->value,
                'is_active' => $active,
            ], audit: false);
        } catch (Throwable $e) {
            report($e);

            return ['Gagal disimpan: '.class_basename($e).'. Periksa kembali isi baris ini.'];
        }

        return [];
    }

    /**
     * Terima kode (UMUM, MUATAN_LOKAL, …) maupun label ("Muatan Lokal").
     */
    private function group(?string $value): ?SubjectGroup
    {
        if ($value === null) {
            return null;
        }

        $value = strtoupper(preg_replace('/\s+/', ' ', $value) ?? $value);

        foreach (SubjectGroup::cases() as $group) {
            if ($value === $group->value || $value === strtoupper($group->label()) || str_replace(' ', '_', $value) === $group->value) {
                return $group;
            }
        }

        return null;
    }

    /**
     * Kosong = aktif.
     */
    private function active(?string $value): ?bool
    {
        if ($value === null) {
            return true;
        }

        $value = strtoupper($value);

        return match (true) {
            in_array($value, self::ACTIVE_VALUES, true) => true,
            in_array($value, self::INACTIVE_VALUES, true) => false,
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, string|null>
     */
    private function normalize(array $raw): array
    {
        $row = [];

        foreach (self::COLUMNS as $column) {
            $value = $raw[$column] ?? null;

            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            }

            $row[$column] = $value === null ? null : (trim((string) (is_float($value) && floor($value) === $value ? (int) $value : $value)) ?: null);
        }

        // Kode disimpan huruf besar, sama seperti form mapel.
        $row['kode'] = $row['kode'] !== null ? strtoupper($row['kode']) : null;

        return $row;
    }
}
