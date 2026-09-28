<?php

namespace App\Modules\Finance\Models;

use App\Modules\Finance\Enums\InvoiceStatus;
use App\Modules\Finance\Enums\PaymentStatus;
use App\Modules\People\Models\Student;
use App\Support\Concerns\HasPublicId;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Tagihan satu jenis biaya untuk satu siswa pada satu periode. Nominal dalam rupiah (integer).
 *
 * @property int $id
 * @property string $public_id
 * @property string $number
 * @property int|null $academic_year_id
 * @property int $student_id
 * @property int $fee_type_id
 * @property int|null $invoice_batch_id
 * @property string $period
 * @property string $period_label
 * @property string|null $dedupe_key
 * @property CarbonImmutable $due_date
 * @property int $total
 * @property int $paid_amount
 * @property InvoiceStatus $status
 * @property string|null $notes
 * @property CarbonImmutable|null $paid_at
 * @property CarbonImmutable|null $cancelled_at
 * @property int|null $cancelled_by
 * @property int|null $created_by
 * @property CarbonImmutable $created_at
 * @property-read Student $student
 * @property-read FeeType $feeType
 * @property-read Collection<int, InvoiceItem> $items
 * @property-read Collection<int, Payment> $payments
 */
class Invoice extends Model
{
    use HasPublicId, SoftDeletes;

    protected $fillable = [
        'number', 'academic_year_id', 'student_id', 'fee_type_id', 'invoice_batch_id', 'period', 'period_label',
        'dedupe_key', 'due_date', 'total', 'paid_amount', 'status', 'notes', 'paid_at', 'cancelled_at',
        'cancelled_by', 'created_by',
    ];

    protected $hidden = ['id', 'academic_year_id', 'student_id', 'fee_type_id', 'invoice_batch_id', 'dedupe_key', 'cancelled_by', 'created_by'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'immutable_date',
            'total' => 'integer',
            'paid_amount' => 'integer',
            'status' => InvoiceStatus::class,
            'paid_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
        ];
    }

    public static function dedupeKey(int $studentId, int $feeTypeId, string $period): string
    {
        return "{$studentId}:{$feeTypeId}:{$period}";
    }

    public function remaining(): int
    {
        return max(0, $this->total - $this->paid_amount);
    }

    public function isOverdue(?CarbonImmutable $today = null): bool
    {
        return $this->status->isPayable() && $this->due_date->lt(($today ?? CarbonImmutable::today()));
    }

    /**
     * Belum lunas dan belum dibatalkan (tunggakan).
     *
     * @param  Builder<Invoice>  $query
     */
    public function scopeOutstanding(Builder $query): void
    {
        $query->whereIn('status', [InvoiceStatus::Unpaid, InvoiceStatus::Pending, InvoiceStatus::Failed]);
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
        return $this->belongsTo(FeeType::class)->withTrashed();
    }

    /**
     * @return HasMany<InvoiceItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('id');
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderBy('id');
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function pendingPayments(): HasMany
    {
        return $this->hasMany(Payment::class)->where('status', PaymentStatus::Pending);
    }
}
