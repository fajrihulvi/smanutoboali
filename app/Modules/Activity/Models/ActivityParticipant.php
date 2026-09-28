<?php

namespace App\Modules\Activity\Models;

use App\Modules\Academic\Models\Classroom;
use App\Modules\People\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Peserta kegiatan: satu rombel ATAU satu siswa.
 *
 * @property int $id
 * @property int $activity_id
 * @property int|null $classroom_id
 * @property int|null $student_id
 * @property-read Classroom|null $classroom
 * @property-read Student|null $student
 */
class ActivityParticipant extends Model
{
    public $timestamps = false;

    protected $fillable = ['activity_id', 'classroom_id', 'student_id'];

    /**
     * @return BelongsTo<Classroom, $this>
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
