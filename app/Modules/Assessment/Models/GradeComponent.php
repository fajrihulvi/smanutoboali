<?php

namespace App\Modules\Assessment\Models;

use App\Modules\Academic\Models\TeachingAssignment;
use App\Modules\Assessment\Enums\ComponentType;
use App\Modules\School\Models\Semester;
use App\Support\Concerns\HasPublicId;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Komponen penilaian (mis. "UH 1", bobot 20%) untuk satu mapel-rombel di satu semester.
 * Terkunci selama dipublikasikan ke orang tua.
 *
 * @property int $id
 * @property string $public_id
 * @property int $academic_year_id
 * @property int $semester_id
 * @property int $teaching_assignment_id
 * @property ComponentType $type
 * @property string $name
 * @property int $weight
 * @property int $position
 * @property CarbonImmutable|null $published_at
 * @property int|null $published_by
 */
class GradeComponent extends Model
{
    use HasPublicId, SoftDeletes;

    protected $fillable = [
        'academic_year_id', 'semester_id', 'teaching_assignment_id', 'type', 'name', 'weight', 'position',
        'published_at', 'published_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ComponentType::class,
            'weight' => 'integer',
            'position' => 'integer',
            'published_at' => 'immutable_datetime',
        ];
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }

    /**
     * @return HasMany<Grade, $this>
     */
    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    /**
     * @return BelongsTo<TeachingAssignment, $this>
     */
    public function teachingAssignment(): BelongsTo
    {
        return $this->belongsTo(TeachingAssignment::class);
    }

    /**
     * @return BelongsTo<Semester, $this>
     */
    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeFor(Builder $query, int $teachingAssignmentId, int $semesterId): void
    {
        $query->where('teaching_assignment_id', $teachingAssignmentId)
            ->where('semester_id', $semesterId)
            ->orderBy('position')
            ->orderBy('id');
    }
}
