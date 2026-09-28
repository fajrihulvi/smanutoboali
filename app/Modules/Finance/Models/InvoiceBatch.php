<?php

namespace App\Modules\Finance\Models;

use App\Models\User;
use App\Modules\Finance\Enums\BatchStatus;
use App\Support\Concerns\HasPublicId;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu kali generate tagihan massal.
 *
 * @property int $id
 * @property string $public_id
 * @property int|null $academic_year_id
 * @property int $fee_type_id
 * @property string $period
 * @property string $period_label
 * @property int $amount
 * @property CarbonImmutable $due_date
 * @property array{type: string, grade?: int|null, classroom_ids?: list<int>} $scope
 * @property BatchStatus $status
 * @property int $created_count
 * @property int $skipped_count
 * @property int $total_amount
 * @property string|null $error
 * @property int|null $created_by
 * @property CarbonImmutable|null $finished_at
 * @property CarbonImmutable $created_at
 * @property-read FeeType $feeType
 * @property-read User|null $creator
 */
class InvoiceBatch extends Model
{
    use HasPublicId;

    protected $fillable = [
        'academic_year_id', 'fee_type_id', 'period', 'period_label', 'amount', 'due_date', 'scope',
        'status', 'created_count', 'skipped_count', 'total_amount', 'error', 'created_by', 'finished_at',
    ];

    protected $hidden = ['id', 'academic_year_id', 'fee_type_id', 'created_by'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'due_date' => 'immutable_date',
            'scope' => 'array',
            'status' => BatchStatus::class,
            'created_count' => 'integer',
            'skipped_count' => 'integer',
            'total_amount' => 'integer',
            'finished_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<FeeType, $this>
     */
    public function feeType(): BelongsTo
    {
        return $this->belongsTo(FeeType::class)->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
