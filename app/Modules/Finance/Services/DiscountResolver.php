<?php

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Models\FeeType;
use App\Modules\Finance\Models\StudentDiscount;
use Illuminate\Support\Collection;

/**
 * Potongan aktif per siswa untuk satu jenis biaya: aturan khusus jenis biaya diutamakan
 * di atas aturan "semua jenis".
 */
class DiscountResolver
{
    /**
     * @param  list<int>  $studentIds
     * @return Collection<int, StudentDiscount> key = student_id
     */
    public function forStudents(array $studentIds, FeeType $feeType): Collection
    {
        return StudentDiscount::query()
            ->whereIn('student_id', $studentIds)
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('fee_type_id')->orWhere('fee_type_id', $feeType->id))
            ->get()
            ->sortBy(fn (StudentDiscount $d) => $d->fee_type_id === null ? 1 : 0)
            ->unique('student_id')
            ->keyBy('student_id');
    }

    /**
     * Potongan dalam rupiah (0 bila tidak ada).
     */
    public function amountFor(?StudentDiscount $discount, int $base): int
    {
        return $discount ? $discount->kind->apply($base, $discount->value) : 0;
    }
}
