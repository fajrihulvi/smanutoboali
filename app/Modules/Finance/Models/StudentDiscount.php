<?php

namespace App\Modules\Finance\Models;

use App\Modules\Finance\Enums\DiscountKind;
use App\Modules\People\Models\Student;
use App\Support\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Potongan/beasiswa per siswa; fee_type_id null = semua jenis biaya.
 *
 * @property int $id
 * @property string $public_id
 * @property int $student_id
 * @property int|null $fee_type_id
 * @property DiscountKind $kind
 * @property int $value
 * @property string $reason
 * @property bool $is_active
 * @property int|null $created_by
 * @property-read Student $student
 * @property-read FeeType|null $feeType
 */
class StudentDiscount extends Model
{
    use HasPublicId, SoftDeletes;

    protected $fillable = ['student_id', 'fee_type_id', 'kind', 'value', 'reason', 'is_active', 'created_by'];

    protected $hidden = ['id', 'student_id', 'fee_type_id', 'created_by'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => DiscountKind::class,
            'value' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function describe(): string
    {
        return $this->kind === DiscountKind::Percent ? "{$this->value}%" : 'Rp'.number_format($this->value, 0, ',', '.');
    }

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * @return BelongsTo<FeeType, $this>
     */
    public function feeType(): BelongsTo
    {
        return $this->belongsTo(FeeType::class);
    }
}
