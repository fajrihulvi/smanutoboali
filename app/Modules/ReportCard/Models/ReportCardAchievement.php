<?php

namespace App\Modules\ReportCard\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $report_card_id
 * @property string $name
 * @property string|null $note
 * @property int $position
 */
class ReportCardAchievement extends Model
{
    protected $fillable = ['report_card_id', 'name', 'note', 'position'];
}
