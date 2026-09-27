<?php

namespace App\Modules\Academic\Models;

use App\Models\User;
use App\Modules\Academic\Enums\MaterialType;
use App\Modules\Core\Models\File;
use App\Modules\School\Models\AcademicYear;
use App\Support\Concerns\HasPublicId;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Materi pembelajaran untuk satu mapel di satu rombel: satu file ATAU satu tautan.
 *
 * @property int $id
 * @property string $public_id
 * @property int $academic_year_id
 * @property int $teaching_assignment_id
 * @property string $title
 * @property string|null $description
 * @property MaterialType $type
 * @property string|null $url
 * @property int|null $created_by
 * @property CarbonImmutable|null $created_at
 */
class LearningMaterial extends Model
{
    use HasPublicId, SoftDeletes;

    protected $fillable = ['academic_year_id', 'teaching_assignment_id', 'title', 'description', 'type', 'url', 'created_by'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['type' => MaterialType::class];
    }

    /**
     * @return BelongsTo<TeachingAssignment, $this>
     */
    public function teachingAssignment(): BelongsTo
    {
        return $this->belongsTo(TeachingAssignment::class);
    }

    /**
     * @return BelongsTo<AcademicYear, $this>
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return MorphOne<File, $this>
     */
    public function file(): MorphOne
    {
        return $this->morphOne(File::class, 'fileable');
    }
}
