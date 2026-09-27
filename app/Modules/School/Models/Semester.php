<?php

namespace App\Modules\School\Models;

use App\Modules\School\Enums\SemesterType;
use App\Support\Concerns\HasPublicId;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $public_id
 * @property int $academic_year_id
 * @property SemesterType $type
 * @property CarbonImmutable $start_date
 * @property CarbonImmutable $end_date
 * @property bool $is_active
 * @property-read AcademicYear $academicYear
 */
class Semester extends Model
{
    use HasPublicId;

    protected $fillable = ['academic_year_id', 'type', 'start_date', 'end_date', 'is_active'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => SemesterType::class,
            'start_date' => 'immutable_date',
            'end_date' => 'immutable_date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<AcademicYear, $this>
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function label(): string
    {
        return 'Semester '.$this->type->label();
    }
}
