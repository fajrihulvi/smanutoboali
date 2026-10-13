<?php

namespace App\Modules\School\Models;

use App\Modules\Core\Models\File;
use App\Modules\School\Enums\SchoolLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Profil sekolah tunggal. Ambil lewat School::current() atau SchoolProfile.
 *
 * @property int $id
 * @property string $name
 * @property string|null $npsn
 * @property SchoolLevel $level
 * @property string|null $address
 * @property string|null $phone
 * @property string|null $email
 * @property int|null $logo_file_id
 * @property string|null $principal_name
 * @property string|null $principal_nip
 * @property int|null $principal_signature_file_id
 * @property-read File|null $logo
 * @property-read File|null $principalSignature
 */
class School extends Model
{
    /** Nama bawaan sebelum profil diisi di Pengaturan Sekolah. */
    public const DEFAULT_NAME = 'SMA NU Toboali';

    /** Alamat bawaan (kop raport & dokumen) sebelum diubah di Pengaturan Sekolah. */
    public const DEFAULT_ADDRESS = 'Jl. Rias, Desa Rias, Kec. Toboali, Kab. Bangka Selatan, Toboali, Kab. Bangka Selatan, Prov. Kepulauan Bangka Belitung';

    protected $fillable = [
        'name', 'npsn', 'level', 'address', 'phone', 'email',
        'logo_file_id', 'principal_name', 'principal_nip', 'principal_signature_file_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['level' => SchoolLevel::class];
    }

    /**
     * @return BelongsTo<File, $this>
     */
    public function logo(): BelongsTo
    {
        return $this->belongsTo(File::class, 'logo_file_id');
    }

    /**
     * Gambar tanda tangan kepala sekolah untuk PDF raport (S16).
     *
     * @return BelongsTo<File, $this>
     */
    public function principalSignature(): BelongsTo
    {
        return $this->belongsTo(File::class, 'principal_signature_file_id');
    }

    /**
     * Baris profil sekolah (dibuat migrasi; dibuat ulang bila hilang).
     */
    public static function current(): self
    {
        return static::query()->with('logo')->oldest('id')->first()
            ?? static::query()->create(['name' => self::DEFAULT_NAME, 'address' => self::DEFAULT_ADDRESS, 'level' => SchoolLevel::Smp]);
    }
}
