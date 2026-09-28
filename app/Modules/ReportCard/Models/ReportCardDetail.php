<?php

namespace App\Modules\ReportCard\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Snapshot nilai satu mapel di raport.
 *
 * @property int $id
 * @property int $report_card_id
 * @property int|null $teaching_assignment_id
 * @property string $subject_name
 * @property string $subject_group
 * @property int|null $final_score
 * @property string|null $predicate
 * @property string|null $description
 * @property int $position
 */
class ReportCardDetail extends Model
{
    protected $fillable = [
        'report_card_id', 'teaching_assignment_id', 'subject_name', 'subject_group',
        'final_score', 'predicate', 'description', 'position',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['final_score' => 'integer', 'position' => 'integer'];
    }
}
