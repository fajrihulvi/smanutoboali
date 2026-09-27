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
 * @property-read File|null $logo
 */
class School extends Model
{
    protected $fillable = [
        'name', 'npsn', 'level', 'address', 'phone', 'email',
        'logo_file_id', 'principal_name', 'principal_nip',
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
     * Baris profil sekolah (dibuat migrasi; dibuat ulang bila hilang).
     */
    public static function current(): self
    {
        return static::query()->with('logo')->oldest('id')->first()
            ?? static::query()->create(['name' => 'Sekolah Skoolia', 'level' => SchoolLevel::Smp]);
    }
}
