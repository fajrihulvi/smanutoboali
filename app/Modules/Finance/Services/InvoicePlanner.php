<?php

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Models\FeeType;
use App\Modules\Finance\Models\Invoice;

/**
 * Preview generate massal: berapa siswa, berapa dilewati (sudah punya tagihan periode itu),
 * berapa mendapat potongan, dan total setelah potongan.
 *
 * @phpstan-import-type Scope from InvoiceTargets
 */
class InvoicePlanner
{
    public function __construct(private InvoiceTargets $targets, private DiscountResolver $discounts) {}

    /**
     * @param  Scope  $scope
     * @return array{students: int, skipped: int, to_create: int, discounted: int, gross: int, total: int}
     */
    public function preview(FeeType $feeType, string $period, int $amount, array $scope): array
    {
        $studentIds = $this->targets->studentIds($scope);
        $existing = $this->existingStudentIds($feeType, $period, $studentIds);
        $toCreate = array_values(array_diff($studentIds, $existing));
        $discounts = $this->discounts->forStudents($toCreate, $feeType);

        $total = 0;
        foreach ($toCreate as $studentId) {
            $total += $amount - $this->discounts->amountFor($discounts->get($studentId), $amount);
        }

        return [
            'students' => count($studentIds),
            'skipped' => count($existing),
            'to_create' => count($toCreate),
            'discounted' => $discounts->count(),
            'gross' => $amount * count($toCreate),
            'total' => $total,
        ];
    }

    /**
     * Siswa yang sudah punya tagihan aktif (tidak dibatalkan) untuk jenis & periode ini.
     *
     * @param  list<int>  $studentIds
     * @return list<int>
     */
    public function existingStudentIds(FeeType $feeType, string $period, array $studentIds): array
    {
        $keys = array_map(fn (int $id) => Invoice::dedupeKey($id, $feeType->id, $period), $studentIds);

        return collect(array_chunk($keys, 500))
            ->flatMap(fn (array $chunk) => Invoice::withTrashed()->whereIn('dedupe_key', $chunk)->pluck('student_id'))
            ->map(fn ($id) => (int) $id)
            ->unique()->values()->all();
    }
}
