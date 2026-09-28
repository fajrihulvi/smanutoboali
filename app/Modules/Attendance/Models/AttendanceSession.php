<?php

namespace App\Modules\Attendance\Models;

use App\Models\User;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\Schedule;
use App\Support\Concerns\HasPublicId;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Satu kali presensi rombel pada satu tanggal (mode harian) atau satu jadwal (mode per mapel).
 *
 * @property int $id
 * @property string $public_id
 * @property int $academic_year_id
 * @property int $semester_id
 * @property int $classroom_id
 * @property CarbonImmutable $date
 * @property int|null $schedule_id
 * @property int|null $recorded_by
 * @property int|null $updated_by
 * @property CarbonImmutable|null $updated_at
 */
class AttendanceSession extends Model
{
    use HasPublicId, SoftDeletes;

    protected $fillable = ['academic_year_id', 'semester_id', 'classroom_id', 'date', 'schedule_id', 'recorded_by', 'updated_by'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['date' => 'immutable_date'];
    }

    /**
     * @return HasMany<AttendanceRecord, $this>
     */
    public function records(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    /**
     * @return BelongsTo<Classroom, $this>
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * @return BelongsTo<Schedule, $this>
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class)->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
