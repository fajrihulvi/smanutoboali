<?php

namespace App\Modules\Finance\Actions;

use App\Modules\Core\Services\AuditLogger;
use App\Modules\Finance\Models\StudentDiscount;

class DeleteDiscountAction
{
    public function __construct(private AuditLogger $audit) {}

    public function handle(StudentDiscount $discount): void
    {
        $discount->loadMissing('student');
        $discount->delete();
        $this->audit->record('finance', 'discount_deleted', "Menghapus potongan {$discount->describe()} untuk {$discount->student->name}", $discount);
    }
}
