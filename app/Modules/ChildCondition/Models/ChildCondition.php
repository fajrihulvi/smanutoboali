<?php

namespace App\Modules\ChildCondition\Models;

use App\Models\User;
use App\Modules\ChildCondition\Enums\ConditionStatus;
use App\Modules\ChildCondition\Enums\ConditionType;
use App\Modules\Core\Models\File;
use App\Modules\People\Models\Student;
use App\Modules\People\Models\Teacher;
use App\Support\Concerns\HasPublicId;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Laporan kondisi anak dari orang tua ke guru (data sensitif).
 *
 * @property int $id
 * @property string $public_id
 * @property int|null $academic_year_id
 * @property int $student_id
 * @property int|null $guardian_user_id
 * @property ConditionType $type
 * @property string $description
 * @property CarbonImmutable $valid_from
 * @property CarbonImmutable|null $valid_until
 * @property int|null $target_teacher_id
 * @property ConditionStatus $status
 * @property CarbonImmutable|null $received_at
 * @property CarbonImmutable|null $reviewed_at
 * @property CarbonImmutable|null $responded_at
 * @property CarbonImmutable|null $closed_at
 * @property int|null $closed_by
 * @property CarbonImmutable $created_at
 * @property-read Student $student
 * @property-read User|null $reporter
 * @property-read Teacher|null $targetTeacher
 * @property-read Collection<int, ChildConditionResponse> $responses
 * @property-read Collection<int, File> $photos
 */
class ChildCondition extends Model
{
    use HasPublicId, SoftDeletes;

    public const MAX_PHOTOS = 3;

    protected $fillable = [
        'academic_year_id', 'student_id', 'guardian_user_id', 'type', 'description',
        'valid_from', 'valid_until', 'target_teacher_id', 'status',
        'received_at', 'reviewed_at', 'responded_at', 'closed_at', 'closed_by',
    ];

    protected $hidden = ['id', 'academic_year_id', 'student_id', 'guardian_user_id', 'target_teacher_id', 'closed_by'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ConditionType::class,
            'status' => ConditionStatus::class,
            'valid_from' => 'immutable_date',
            'valid_until' => 'immutable_date',
            'received_at' => 'immutable_datetime',
            'reviewed_at' => 'immutable_datetime',
            'responded_at' => 'immutable_datetime',
            'closed_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guardian_user_id');
    }

    /**
     * @return BelongsTo<Teacher, $this>
     */
    public function targetTeacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'target_teacher_id');
    }

    /**
     * @return HasMany<ChildConditionResponse, $this>
     */
    public function responses(): HasMany
    {
        return $this->hasMany(ChildConditionResponse::class)->orderBy('id');
    }

    /**
     * @return MorphMany<File, $this>
     */
    public function photos(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable')->orderBy('id');
    }

    /**
     * Tanggal terakhir berlaku (tanpa valid_until = hanya satu hari).
     */
    public function lastDay(): CarbonImmutable
    {
        return $this->valid_until ?? $this->valid_from;
    }

    public function covers(CarbonImmutable $date): bool
    {
        return $date->betweenIncluded($this->valid_from->startOfDay(), $this->lastDay()->endOfDay());
    }

    /**
     * Berlaku pada tanggal tertentu.
     *
     * @param  Builder<ChildCondition>  $query
     */
    public function scopeCovering(Builder $query, CarbonImmutable $date): void
    {
        $day = $date->toDateString();

        $query->whereDate('valid_from', '<=', $day)
            ->where(fn (Builder $q) => $q
                ->where(fn (Builder $single) => $single->whereNull('valid_until')->whereDate('valid_from', $day))
                ->orWhereDate('valid_until', '>=', $day));
    }
}
