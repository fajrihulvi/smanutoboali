<?php

namespace App\Modules\Finance\Models;

use App\Models\User;
use App\Modules\Core\Models\File;
use App\Modules\Finance\Enums\PaymentMethod;
use App\Modules\Finance\Enums\PaymentStatus;
use App\Support\Concerns\HasPublicId;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Pembayaran (bisa sebagian/cicilan). Hanya status CONFIRMED yang dihitung ke tagihan.
 *
 * @property int $id
 * @property string $public_id
 * @property int $invoice_id
 * @property PaymentMethod $method
 * @property PaymentStatus $status
 * @property int $amount
 * @property CarbonImmutable $paid_on
 * @property string|null $receipt_number
 * @property string|null $note
 * @property string|null $rejection_reason
 * @property int|null $submitted_by
 * @property int|null $verified_by
 * @property CarbonImmutable|null $verified_at
 * @property CarbonImmutable $created_at
 * @property-read Invoice $invoice
 * @property-read User|null $submitter
 * @property-read User|null $verifier
 * @property-read File|null $proof
 */
class Payment extends Model
{
    use HasPublicId, SoftDeletes;

    protected $fillable = [
        'invoice_id', 'method', 'status', 'amount', 'paid_on', 'receipt_number', 'note',
        'rejection_reason', 'submitted_by', 'verified_by', 'verified_at',
    ];

    protected $hidden = ['id', 'invoice_id', 'submitted_by', 'verified_by'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'amount' => 'integer',
            'paid_on' => 'immutable_date',
            'verified_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Bukti transfer (privat).
     *
     * @return MorphOne<File, $this>
     */
    public function proof(): MorphOne
    {
        return $this->morphOne(File::class, 'fileable');
    }
}
