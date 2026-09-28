<?php

namespace App\Modules\ReportCard\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Guru mapel menyatakan nilai & deskripsi mapelnya di satu rombel siap masuk raport.
 *
 * @property int $id
 * @property int $teaching_assignment_id
 * @property int $semester_id
 * @property int|null $confirmed_by
 * @property CarbonImmutable $confirmed_at
 */
class ReportSubjectConfirmation extends Model
{
    protected $fillable = ['teaching_assignment_id', 'semester_id', 'confirmed_by', 'confirmed_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['confirmed_at' => 'immutable_datetime'];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
