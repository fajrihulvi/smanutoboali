<?php

namespace App\Modules\Activity\Models;

use App\Models\User;
use App\Modules\Activity\Enums\ActivityStatus;
use App\Modules\Activity\Enums\ActivityType;
use App\Modules\Core\Models\File;
use App\Support\Concerns\HasPublicId;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $public_id
 * @property int|null $academic_year_id
 * @property string $title
 * @property ActivityType $type
 * @property CarbonImmutable $date
 * @property string|null $start_time
 * @property string|null $end_time
 * @property string|null $location
 * @property string|null $description
 * @property ActivityStatus $status
 * @property CarbonImmutable|null $published_at
 * @property int|null $created_by
 * @property-read User|null $creator
 */
class Activity extends Model
{
    use HasPublicId, SoftDeletes;

    public const MAX_PHOTOS = 20;

    protected $fillable = [
        'academic_year_id', 'title', 'type', 'date', 'start_time', 'end_time',
        'location', 'description', 'status', 'published_at', 'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ActivityType::class,
            'status' => ActivityStatus::class,
            'date' => 'immutable_date',
            'published_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return HasMany<ActivityParticipant, $this>
     */
    public function participants(): HasMany
    {
        return $this->hasMany(ActivityParticipant::class);
    }

    /**
     * @return MorphMany<File, $this>
     */
    public function photos(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable')->orderBy('id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
