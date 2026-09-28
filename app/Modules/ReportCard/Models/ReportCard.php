<?php

namespace App\Modules\ReportCard\Models;

use App\Modules\Academic\Models\Classroom;
use App\Modules\People\Models\Student;
use App\Modules\ReportCard\Enums\ReportCardStatus;
use App\Modules\School\Models\Semester;
use App\Support\Concerns\HasPublicId;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Raport satu siswa di satu semester.
 *
 * @property int $id
 * @property string $public_id
 * @property int $academic_year_id
 * @property int $semester_id
 * @property int $classroom_id
 * @property int $student_id
 * @property ReportCardStatus $status
 * @property string|null $homeroom_note
 * @property array{sick: int, permit: int, absent: int, days: int}|null $attendance
 * @property CarbonImmutable|null $published_at
 * @property int|null $created_by
 * @property-read Student $student
 * @property-read Classroom $classroom
 * @property-read Semester $semester
 */
class ReportCard extends Model
{
    use HasPublicId, SoftDeletes;

    protected $fillable = [
        'academic_year_id', 'semester_id', 'classroom_id', 'student_id', 'status',
        'homeroom_note', 'attendance', 'published_at', 'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ReportCardStatus::class,
            'attendance' => 'array',
            'published_at' => 'immutable_datetime',
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
     * @return BelongsTo<Classroom, $this>
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * @return BelongsTo<Semester, $this>
     */
    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    /**
     * @return HasMany<ReportCardDetail, $this>
     */
    public function details(): HasMany
    {
        return $this->hasMany(ReportCardDetail::class)->orderBy('position');
    }

    /**
     * @return HasMany<ReportCardExtracurricular, $this>
     */
    public function extracurriculars(): HasMany
    {
        return $this->hasMany(ReportCardExtracurricular::class)->orderBy('position');
    }

    /**
     * @return HasMany<ReportCardAchievement, $this>
     */
    public function achievements(): HasMany
    {
        return $this->hasMany(ReportCardAchievement::class)->orderBy('position');
    }

    /**
     * @return HasMany<ReportCardLog, $this>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(ReportCardLog::class)->orderByDesc('created_at')->orderByDesc('id');
    }
}
