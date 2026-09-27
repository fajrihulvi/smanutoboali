<?php

namespace App\Modules\People\Services;

use App\Modules\People\Actions\AttachGuardianAction;
use App\Modules\People\Actions\CreateStudentAction;
use App\Modules\People\Enums\Gender;
use App\Modules\People\Enums\GuardianRelationship;
use App\Modules\People\Enums\Religion;
use App\Modules\People\Enums\StudentStatus;
use App\Modules\People\Models\Guardian;
use App\Modules\People\Models\Student;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

/**
 * Proses satu baris template import siswa + orang tua.
 */
class StudentImportRowProcessor
{
    /**
     * Kolom template (baris judul). Urutan = urutan di file template.
     *
     * @var list<string>
     */
    public const COLUMNS = [
        'nis', 'nisn', 'nama', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'agama', 'alamat', 'tanggal_masuk',
        'ayah_nama', 'ayah_telepon', 'ayah_email',
        'ibu_nama', 'ibu_telepon', 'ibu_email',
        'wali_nama', 'wali_telepon', 'wali_email',
    ];

    private const GUARDIAN_PREFIXES = [
        'ayah' => GuardianRelationship::Father,
        'ibu' => GuardianRelationship::Mother,
        'wali' => GuardianRelationship::Guardian,
    ];

    public function __construct(
        private CreateStudentAction $createStudent,
        private AttachGuardianAction $attachGuardian,
    ) {}

    /**
     * @param  array<string, mixed>  $raw  satu baris (key = judul kolom)
     * @return list<string> pesan error; kosong = berhasil
     */
    public function process(array $raw): array
    {
        $row = $this->normalize($raw);

        $validator = Validator::make($row, [
            'nis' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9.\-\/]+$/'],
            'nisn' => ['nullable', 'digits:10'],
            'nama' => ['required', 'string', 'max:150'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'tempat_lahir' => ['nullable', 'string', 'max:100'],
            'tanggal_lahir' => ['nullable', 'date_format:Y-m-d'],
            'agama' => ['nullable', 'string'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'tanggal_masuk' => ['nullable', 'date_format:Y-m-d'],
            'ayah_email' => ['nullable', 'email'],
            'ibu_email' => ['nullable', 'email'],
            'wali_email' => ['nullable', 'email'],
        ], [
            'jenis_kelamin.in' => 'Jenis kelamin harus L atau P.',
            'tanggal_lahir.date_format' => 'Tanggal lahir tidak dikenali (gunakan format YYYY-MM-DD atau DD/MM/YYYY).',
            'tanggal_masuk.date_format' => 'Tanggal masuk tidak dikenali (gunakan format YYYY-MM-DD atau DD/MM/YYYY).',
        ], [
            'nis' => 'NIS', 'nisn' => 'NISN', 'nama' => 'nama', 'jenis_kelamin' => 'jenis kelamin',
            'ayah_email' => 'email ayah', 'ibu_email' => 'email ibu', 'wali_email' => 'email wali',
        ]);

        $errors = $validator->errors()->all();

        if ($row['agama'] !== null && Religion::fromLoose($row['agama']) === null) {
            $errors[] = "Agama \"{$row['agama']}\" tidak dikenali.";
        }

        if ($row['nis'] && Student::withTrashed()->where('nis', $row['nis'])->exists()) {
            $errors[] = "NIS {$row['nis']} sudah terdaftar.";
        }

        if ($row['nisn'] && Student::withTrashed()->where('nisn', $row['nisn'])->exists()) {
            $errors[] = "NISN {$row['nisn']} sudah terdaftar.";
        }

        if ($errors !== []) {
            return array_values($errors);
        }

        try {
            DB::transaction(function () use ($row) {
                $student = $this->createStudent->handle([
                    'nis' => $row['nis'],
                    'nisn' => $row['nisn'],
                    'name' => $row['nama'],
                    'gender' => Gender::from((string) $row['jenis_kelamin']),
                    'birth_place' => $row['tempat_lahir'],
                    'birth_date' => $row['tanggal_lahir'],
                    'religion' => Religion::fromLoose($row['agama']),
                    'address' => $row['alamat'],
                    'status' => StudentStatus::Active,
                    'entry_date' => $row['tanggal_masuk'],
                ], audit: false);

                foreach (self::GUARDIAN_PREFIXES as $prefix => $relationship) {
                    $name = $row["{$prefix}_nama"];

                    if (! $name) {
                        continue;
                    }

                    $guardian = $this->findOrCreateGuardian($name, $row["{$prefix}_telepon"], $row["{$prefix}_email"]);
                    $this->attachGuardian->handle($student, $guardian, $relationship, audit: false);
                }
            });
        } catch (Throwable $e) {
            report($e);

            return ['Gagal disimpan: '.class_basename($e).'. Periksa kembali isi baris ini.'];
        }

        return [];
    }

    /**
     * Pakai data orang tua yang sudah ada (kakak-adik): cocokkan email, atau nama + telepon.
     */
    private function findOrCreateGuardian(string $name, ?string $phone, ?string $email): Guardian
    {
        $existing = null;

        if ($email) {
            $existing = Guardian::query()->where('email', $email)->first();
        }

        if (! $existing && $phone) {
            $existing = Guardian::query()->where('name', $name)->where('phone', $phone)->first();
        }

        return $existing ?? Guardian::query()->create([
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
        ]);
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

        foreach (['tanggal_lahir', 'tanggal_masuk'] as $column) {
            $row[$column] = $this->date($raw[$column] ?? null, $row[$column]);
        }

        foreach (array_keys(self::GUARDIAN_PREFIXES) as $prefix) {
            $row["{$prefix}_telepon"] = $this->phone($row["{$prefix}_telepon"]);
            $row["{$prefix}_email"] = $row["{$prefix}_email"] ? strtolower($row["{$prefix}_email"]) : null;
        }

        return $row;
    }

    private function date(mixed $raw, ?string $text): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        // Sel tanggal Excel terbaca sebagai angka serial.
        if (is_numeric($raw)) {
            try {
                return CarbonImmutable::instance(ExcelDate::excelToDateTimeObject((float) $raw))->toDateString();
            } catch (Throwable) {
                return (string) $text;
            }
        }

        // DateTimeImmutable (bukan Carbon) karena mengembalikan false, tidak melempar exception.
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'd.m.Y'] as $format) {
            $date = \DateTimeImmutable::createFromFormat('!'.$format, (string) $text);

            if ($date !== false && $date->format($format) === $text) {
                return $date->format('Y-m-d');
            }
        }

        return (string) $text;
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
