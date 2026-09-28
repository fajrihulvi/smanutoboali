<?php

namespace App\Modules\Assessment\Models;

use App\Modules\Assessment\Enums\Predicate;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $teaching_assignment_id
 * @property Predicate $predicate
 * @property string $template
 */
class GradeDescriptionTemplate extends Model
{
    protected $fillable = ['teaching_assignment_id', 'predicate', 'template'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['predicate' => Predicate::class];
    }
}
