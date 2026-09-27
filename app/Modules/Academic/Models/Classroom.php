<?php

namespace App\Modules\Academic\Models;

use App\Modules\Academic\Database\Factories\ClassroomFactory;
use App\Modules\Academic\Enums\EnrollmentStatus;
use App\Modules\People\Models\Teacher;
use App\Modules\School\Models\AcademicYear;
use App\Support\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Rombongan belajar (rombel) per tahun ajaran.
 *
 * @property int $id
 * @property string $public_id
 * @property int $academic_year_id
 * @property string $name
 * @property int $grade
 * @property int|null $homeroom_teacher_id
 * @property int|null $capacity
 * @property-read AcademicYear $academicYear
 * @property-read Teacher|null $homeroomTeacher
 */
class Classroom extends Model
{
    /** @use HasFactory<ClassroomFactory> */
    use HasFactory, HasPublicId;

    protected $fillable = ['academic_year_id', 'name', 'grade', 'homeroom_teacher_id', 'capacity'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['grade' => 'integer', 'capacity' => 'integer'];
    }

    /**
     * @return BelongsTo<AcademicYear, $this>
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * @return BelongsTo<Teacher, $this>
     */
    public function homeroomTeacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'homeroom_teacher_id');
    }

    /**
     * @return HasMany<ClassEnrollment, $this>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(ClassEnrollment::class);
    }

    /**
     * @return HasMany<ClassEnrollment, $this>
     */
    public function activeEnrollments(): HasMany
    {
        return $this->enrollments()->where('status', EnrollmentStatus::Active);
    }

    /**
     * @return HasMany<TeachingAssignment, $this>
     */
    public function teachingAssignments(): HasMany
    {
        return $this->hasMany(TeachingAssignment::class);
    }

    /**
     * Guru boleh mengakses rombel ini: wali kelasnya atau punya penugasan di sini.
     */
    public function isTaughtBy(Teacher $teacher): bool
    {
        return $this->homeroom_teacher_id === $teacher->getKey()
            || $this->teachingAssignments()->where('teacher_id', $teacher->getKey())->exists();
    }
}
