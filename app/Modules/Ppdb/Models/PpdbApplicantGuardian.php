<?php

namespace App\Modules\Ppdb\Models;

use App\Modules\People\Enums\GuardianRelationship;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $ppdb_applicant_id
 * @property GuardianRelationship $relationship
 * @property string $name
 * @property string|null $nik
 * @property string|null $occupation
 * @property string|null $phone
 * @property string|null $email
 * @property bool $is_primary
 */
class PpdbApplicantGuardian extends Model
{
    protected $fillable = ['ppdb_applicant_id', 'relationship', 'name', 'nik', 'occupation', 'phone', 'email', 'is_primary'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['relationship' => GuardianRelationship::class, 'is_primary' => 'boolean'];
    }
}
