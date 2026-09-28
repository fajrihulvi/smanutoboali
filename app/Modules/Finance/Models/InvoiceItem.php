<?php

namespace App\Modules\Finance\Models;

use App\Modules\Finance\Enums\InvoiceItemType;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $invoice_id
 * @property InvoiceItemType $type
 * @property string $description
 * @property int $amount
 */
class InvoiceItem extends Model
{
    protected $fillable = ['invoice_id', 'type', 'description', 'amount'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['type' => InvoiceItemType::class, 'amount' => 'integer'];
    }
}
