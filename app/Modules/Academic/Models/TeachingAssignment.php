<?php

namespace App\Modules\Academic\Models;

use App\Modules\People\Models\Teacher;
use App\Modules\School\Models\AcademicYear;
use App\Support\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * "Siapa mengajar apa di rombel mana tahun ini" (VIBE_PLANNING §5).
 *
 * @property int $id
 * @property string $public_id
 * @property int $teacher_id
 * @property int $classroom_id
 * @property int $subject_id
 * @property int $academic_year_id
 * @property-read Teacher $teacher
 * @property-read Classroom $classroom
 * @property-read Subject $subject
 */
class TeachingAssignment extends Model
{
    use HasPublicId;

    protected $fillable = ['teacher_id', 'classroom_id', 'subject_id', 'academic_year_id'];

    /**
     * @return BelongsTo<Teacher, $this>
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * @return BelongsTo<Classroom, $this>
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * @return BelongsTo<Subject, $this>
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * @return BelongsTo<AcademicYear, $this>
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
