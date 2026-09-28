<?php

namespace App\Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Deskripsi capaian siswa untuk satu mapel-rombel di satu semester (dipakai raport S16).
 *
 * @property int $id
 * @property int $teaching_assignment_id
 * @property int $semester_id
 * @property int $student_id
 * @property string $description
 * @property int|null $updated_by
 */
class GradeDescription extends Model
{
    protected $fillable = ['teaching_assignment_id', 'semester_id', 'student_id', 'description', 'updated_by'];
}
