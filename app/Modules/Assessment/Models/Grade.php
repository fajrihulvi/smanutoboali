<?php

namespace App\Modules\Assessment\Models;

use App\Modules\People\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $grade_component_id
 * @property int $student_id
 * @property string $score desimal (string dari driver)
 * @property string|null $note
 * @property int|null $updated_by
 */
class Grade extends Model
{
    protected $fillable = ['grade_component_id', 'student_id', 'score', 'note', 'updated_by'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['score' => 'decimal:2'];
    }

    public function value(): float
    {
        return (float) $this->score;
    }

    /**
     * @return BelongsTo<GradeComponent, $this>
     */
    public function component(): BelongsTo
    {
        return $this->belongsTo(GradeComponent::class, 'grade_component_id');
    }

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
