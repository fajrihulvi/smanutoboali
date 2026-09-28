<?php

namespace App\Modules\Ppdb\Models;

use App\Modules\School\Models\AcademicYear;
use App\Support\Concerns\HasPublicId;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $public_id
 * @property string $name
 * @property int $academic_year_id
 * @property int $grade
 * @property CarbonImmutable $opens_at
 * @property CarbonImmutable $closes_at
 * @property CarbonImmutable|null $announces_at
 * @property string|null $description
 * @property bool $is_active
 * @property int|null $created_by
 * @property-read AcademicYear $academicYear
 * @property-read Collection<int, PpdbTrack> $tracks
 */
class PpdbPeriod extends Model
{
    use HasPublicId, SoftDeletes;

    protected $fillable = ['name', 'academic_year_id', 'grade', 'opens_at', 'closes_at', 'announces_at', 'description', 'is_active', 'created_by'];

    protected $hidden = ['id', 'academic_year_id', 'created_by'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'grade' => 'integer',
            'opens_at' => 'immutable_datetime',
            'closes_at' => 'immutable_datetime',
            'announces_at' => 'immutable_datetime',
            'is_active' => 'boolean',
        ];
    }

    public function isOpen(?CarbonImmutable $now = null): bool
    {
        $now ??= CarbonImmutable::now();

        return $this->is_active && $now->betweenIncluded($this->opens_at, $this->closes_at);
    }

    /**
     * Aktif dan sedang dalam rentang pendaftaran.
     *
     * @param  Builder<PpdbPeriod>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->where('is_active', true)->where('opens_at', '<=', now())->where('closes_at', '>=', now());
    }

    /**
     * @return BelongsTo<AcademicYear, $this>
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * @return HasMany<PpdbTrack, $this>
     */
    public function tracks(): HasMany
    {
        return $this->hasMany(PpdbTrack::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @return HasMany<PpdbApplicant, $this>
     */
    public function applicants(): HasMany
    {
        return $this->hasMany(PpdbApplicant::class);
    }
}
