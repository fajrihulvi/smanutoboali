<?php

namespace App\Modules\Attendance\Models;

use App\Models\User;
use App\Modules\Attendance\Enums\TeacherAttendanceStatus;
use App\Modules\People\Models\Teacher;
use App\Support\Concerns\HasPublicId;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Kehadiran guru satu hari.
 *
 * @property int $id
 * @property string $public_id
 * @property int|null $academic_year_id
 * @property int $teacher_id
 * @property CarbonImmutable $date
 * @property CarbonImmutable|null $check_in_at
 * @property CarbonImmutable|null $check_out_at
 * @property TeacherAttendanceStatus $status
 * @property string|null $note
 * @property string $source MANDIRI | OPERATOR
 * @property array<string, mixed>|null $check_in_meta
 * @property int|null $recorded_by
 */
class TeacherAttendance extends Model
{
    use HasPublicId, SoftDeletes;

    public const SOURCE_SELF = 'MANDIRI';

    public const SOURCE_OPERATOR = 'OPERATOR';

    protected $fillable = [
        'academic_year_id', 'teacher_id', 'date', 'check_in_at', 'check_out_at',
        'status', 'note', 'source', 'check_in_meta', 'recorded_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'immutable_date',
            'check_in_at' => 'immutable_datetime',
            'check_out_at' => 'immutable_datetime',
            'status' => TeacherAttendanceStatus::class,
            'check_in_meta' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Teacher, $this>
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
