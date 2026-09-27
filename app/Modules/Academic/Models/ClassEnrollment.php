<?php

namespace App\Modules\Academic\Models;

use App\Modules\Academic\Enums\EnrollmentStatus;
use App\Modules\People\Models\Student;
use App\Modules\School\Models\AcademicYear;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Keanggotaan siswa di rombel pada satu tahun ajaran (riwayat tetap tersimpan).
 *
 * @property int $id
 * @property int $student_id
 * @property int $classroom_id
 * @property int $academic_year_id
 * @property EnrollmentStatus $status
 * @property-read Student $student
 * @property-read Classroom $classroom
 * @property-read AcademicYear $academicYear
 */
class ClassEnrollment extends Model
{
    protected $fillable = ['student_id', 'classroom_id', 'academic_year_id', 'status'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['status' => EnrollmentStatus::class];
    }

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * @return BelongsTo<Classroom, $this>
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * @return BelongsTo<AcademicYear, $this>
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
