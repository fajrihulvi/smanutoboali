<?php

namespace App\Modules\Finance\Models;

use App\Modules\Finance\Enums\GatewayStatus;
use App\Support\Concerns\HasPublicId;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $public_id
 * @property int $invoice_id
 * @property string $gateway
 * @property string $order_id
 * @property int $amount
 * @property GatewayStatus $status
 * @property string|null $snap_token
 * @property string|null $redirect_url
 * @property string|null $payment_type
 * @property string|null $gateway_transaction_id
 * @property int|null $payment_id
 * @property int|null $created_by
 * @property CarbonImmutable|null $settled_at
 * @property CarbonImmutable $created_at
 * @property-read Invoice $invoice
 * @property-read Payment|null $payment
 */
class GatewayTransaction extends Model
{
    use HasPublicId;

    protected $fillable = [
        'invoice_id', 'gateway', 'order_id', 'amount', 'status', 'snap_token', 'redirect_url',
        'payment_type', 'gateway_transaction_id', 'payment_id', 'created_by', 'settled_at',
    ];

    protected $hidden = ['id', 'invoice_id', 'payment_id', 'created_by', 'snap_token'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'status' => GatewayStatus::class,
            'settled_at' => 'immutable_datetime',
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
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
