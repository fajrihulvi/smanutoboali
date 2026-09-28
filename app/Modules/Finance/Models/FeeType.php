<?php

namespace App\Modules\Finance\Models;

use App\Modules\Finance\Enums\FeeFrequency;
use App\Support\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $public_id
 * @property string $name
 * @property int $default_amount
 * @property FeeFrequency $frequency
 * @property bool $is_active
 * @property string|null $description
 */
class FeeType extends Model
{
    use HasPublicId, SoftDeletes;

    protected $fillable = ['name', 'default_amount', 'frequency', 'is_active', 'description'];

    protected $hidden = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'default_amount' => 'integer',
            'frequency' => FeeFrequency::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
