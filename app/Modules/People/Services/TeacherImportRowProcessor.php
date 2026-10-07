<?php

namespace App\Modules\People\Services;

use App\Modules\People\Actions\CreateTeacherAction;
use App\Modules\People\Enums\EmploymentStatus;
use App\Modules\People\Enums\Gender;
use App\Modules\People\Models\Teacher;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Proses satu baris template import guru. Akun login tidak dibuat di sini;
 * operator menautkannya per guru agar password sementara bisa diserahkan.
 */
class TeacherImportRowProcessor
{
    /**
     * Kolom template (baris judul). Urutan = urutan di file template.
     *
     * @var list<string>
     */
    public const COLUMNS = ['nip', 'nuptk', 'nama', 'jenis_kelamin', 'status_kepegawaian', 'telepon'];

    /**
     * NIP (18 digit) & NUPTK (16 digit) melebihi presisi angka Excel (15 digit). Sel angka .xlsx
     * terbaca float (digit akhir sudah dibulatkan); CSV terbaca int yang masih utuh.
     *
     * @var array<string, string>
     */
    private const LONG_NUMBER_COLUMNS = ['nip' => 'NIP', 'nuptk' => 'NUPTK'];

    public function __construct(private CreateTeacherAction $createTeacher) {}

    /**
     * @param  array<string, mixed>  $raw  satu baris (key = judul kolom)
     * @return list<string> pesan error; kosong = berhasil
     */
    public function process(array $raw): array
    {
        $errors = [];

        foreach (self::LONG_NUMBER_COLUMNS as $column => $label) {
            $value = $raw[$column] ?? null;

            if (is_float($value) && abs($value) >= 1e15) {
                $errors[] = "{$label} terbaca sebagai angka sehingga digit terakhirnya berubah. Format kolom {$label} sebagai Teks lalu ketik ulang.";
            }
        }

        if ($errors !== []) {
            return $errors;
        }

        $row = $this->normalize($raw);

        $validator = Validator::make($row, [
            'nip' => ['nullable', 'digits:18'],
            'nuptk' => ['nullable', 'digits:16'],
            'nama' => ['required', 'string', 'max:150'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'status_kepegawaian' => ['required'],
            'telepon' => ['nullable', 'string', 'max:20'],
        ], [
            'jenis_kelamin.in' => 'Jenis kelamin harus L atau P.',
        ], [
            'nip' => 'NIP', 'nuptk' => 'NUPTK', 'nama' => 'nama', 'jenis_kelamin' => 'jenis kelamin',
            'status_kepegawaian' => 'status kepegawaian', 'telepon' => 'telepon',
        ]);

        $errors = $validator->errors()->all();
        $employment = $this->employmentStatus($row['status_kepegawaian']);

        if ($row['status_kepegawaian'] !== null && $employment === null) {
            $allowed = implode(', ', array_map(fn (EmploymentStatus $status) => $status->value, EmploymentStatus::cases()));
            $errors[] = "Status kepegawaian \"{$row['status_kepegawaian']}\" tidak dikenali (pilih {$allowed}).";
        }

        if ($row['nip'] && Teacher::withTrashed()->where('nip', $row['nip'])->exists()) {
            $errors[] = "NIP {$row['nip']} sudah terdaftar.";
        }

        if ($row['nuptk'] && Teacher::withTrashed()->where('nuptk', $row['nuptk'])->exists()) {
            $errors[] = "NUPTK {$row['nuptk']} sudah terdaftar.";
        }

        if ($errors !== [] || $employment === null) {
            return array_values($errors);
        }

        try {
            $this->createTeacher->handle([
                'nip' => $row['nip'],
                'nuptk' => $row['nuptk'],
                'name' => $row['nama'],
                'gender' => Gender::from((string) $row['jenis_kelamin']),
                'phone' => $row['telepon'],
                'employment_status' => $employment,
                'is_active' => true,
            ], audit: false);
        } catch (Throwable $e) {
            report($e);

            return ['Gagal disimpan: '.class_basename($e).'. Periksa kembali isi baris ini.'];
        }

        return [];
    }

    /**
     * Terima kode (PNS, GTY, …) maupun label ("Guru Tetap Yayasan").
     */
    private function employmentStatus(?string $value): ?EmploymentStatus
    {
        if ($value === null) {
            return null;
        }

        $value = strtoupper($value);

        foreach (EmploymentStatus::cases() as $status) {
            if ($value === $status->value || $value === strtoupper($status->label())) {
                return $status;
            }
        }

        return null;
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
            $row[$column] = $value === null ? null : (trim((string) (is_float($value) && floor($value) === $value ? (int) $value : $value)) ?: null);
        }

        $gender = strtoupper((string) $row['jenis_kelamin']);
        $row['jenis_kelamin'] = match (true) {
            in_array($gender, ['L', 'LAKI-LAKI', 'LAKI LAKI', 'PRIA'], true) => 'L',
            in_array($gender, ['P', 'PEREMPUAN', 'WANITA'], true) => 'P',
            default => $row['jenis_kelamin'],
        };

        // NIP sering ditulis berkelompok: "19800101 200501 1 001".
        foreach (array_keys(self::LONG_NUMBER_COLUMNS) as $column) {
            $row[$column] = $row[$column] !== null ? (preg_replace('/[\s.\-]/', '', $row[$column]) ?: null) : null;
        }

        $row['telepon'] = $this->phone($row['telepon']);

        return $row;
    }

    /**
     * Excel sering membuang angka 0 di depan nomor HP (8123… → 08123…).
     */
    private function phone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $phone = preg_replace('/[^0-9+]/', '', $phone) ?? '';

        if (str_starts_with($phone, '8')) {
            $phone = '0'.$phone;
        }

        return $phone !== '' ? substr($phone, 0, 20) : null;
    }
}
