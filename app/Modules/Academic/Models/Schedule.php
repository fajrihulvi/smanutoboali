<?php

namespace App\Modules\Academic\Models;

use App\Modules\Academic\Enums\Weekday;
use App\Modules\School\Models\AcademicYear;
use App\Support\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Satu slot jadwal mingguan milik penugasan mengajar (guru × mapel × rombel).
 *
 * @property int $id
 * @property string $public_id
 * @property int $academic_year_id
 * @property int $teaching_assignment_id
 * @property Weekday $day_of_week
 * @property string $start_time HH:MM:SS
 * @property string $end_time HH:MM:SS
 * @property string|null $room
 */
class Schedule extends Model
{
    use HasPublicId, SoftDeletes;

    protected $fillable = ['academic_year_id', 'teaching_assignment_id', 'day_of_week', 'start_time', 'end_time', 'room'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['day_of_week' => Weekday::class];
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
     * Jadwal yang jamnya beririsan dengan [start, end) pada hari yang sama.
     *
     * @param  Builder<self>  $query
     */
    public function scopeOverlapping(Builder $query, Weekday $day, string $start, string $end): void
    {
        $query->where('day_of_week', $day->value)
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeChronological(Builder $query): void
    {
        $query->orderBy('day_of_week')->orderBy('start_time');
    }

    public function startLabel(): string
    {
        return substr($this->start_time, 0, 5);
    }

    public function endLabel(): string
    {
        return substr($this->end_time, 0, 5);
    }
}
