<?php

namespace App\Modules\Finance\Actions;

use App\Models\User;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\Finance\Enums\DiscountKind;
use App\Modules\Finance\Models\StudentDiscount;

/**
 * Simpan aturan potongan. Berlaku untuk tagihan yang dibuat SETELAHNYA (tagihan lama tidak berubah).
 */
class SaveDiscountAction
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  array{student_id: int, fee_type_id: int|null, kind: DiscountKind, value: int, reason: string, is_active: bool}  $data
     */
    public function handle(array $data, User $actor, ?StudentDiscount $discount = null): StudentDiscount
    {
        $isNew = $discount === null;
        $discount ??= new StudentDiscount(['created_by' => $actor->id]);
        $discount->fill($data)->save();
        $discount->loadMissing('student');

        $this->audit->record('finance', $isNew ? 'discount_created' : 'discount_updated',
            ($isNew ? 'Menambah' : 'Mengubah')." potongan {$discount->describe()} untuk {$discount->student->name}", $discount);

        return $discount;
    }
}
