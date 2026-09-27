<?php

namespace App\Modules\Academic\Models;

use App\Modules\Academic\Database\Factories\SubjectFactory;
use App\Modules\Academic\Enums\SubjectGroup;
use App\Support\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $public_id
 * @property string $code
 * @property string $name
 * @property SubjectGroup $group
 * @property bool $is_active
 */
class Subject extends Model
{
    /** @use HasFactory<SubjectFactory> */
    use HasFactory, HasPublicId;

    protected $fillable = ['code', 'name', 'group', 'is_active'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['group' => SubjectGroup::class, 'is_active' => 'boolean'];
    }

    /**
     * @return HasMany<TeachingAssignment, $this>
     */
    public function teachingAssignments(): HasMany
    {
        return $this->hasMany(TeachingAssignment::class);
    }
}
