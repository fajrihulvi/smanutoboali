<?php

namespace App\Modules\Attendance\Models;

use App\Modules\Attendance\Enums\AttendanceStatus;
use App\Modules\People\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $attendance_session_id
 * @property int $student_id
 * @property AttendanceStatus $status
 * @property string|null $note
 */
class AttendanceRecord extends Model
{
    protected $fillable = ['attendance_session_id', 'student_id', 'status', 'note'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['status' => AttendanceStatus::class];
    }

    /**
     * @return BelongsTo<AttendanceSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(AttendanceSession::class, 'attendance_session_id');
    }

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
