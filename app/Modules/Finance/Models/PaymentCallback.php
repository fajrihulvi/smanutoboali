<?php

namespace App\Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Log mentah setiap callback payment gateway (audit & investigasi).
 *
 * @property int $id
 * @property string $gateway
 * @property string|null $order_id
 * @property string|null $transaction_status
 * @property bool $signature_valid
 * @property string|null $result
 * @property array<string, mixed> $payload
 */
class PaymentCallback extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['gateway', 'order_id', 'transaction_status', 'status_code', 'gross_amount', 'signature_valid', 'result', 'payload'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['signature_valid' => 'boolean', 'payload' => 'array'];
    }
}
