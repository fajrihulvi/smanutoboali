<?php

namespace App\Modules\Report\Rules;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Throwable;

/**
 * Tanggal akhir paling jauh N hari dari `from` (tanpa bergantung pada request — dipakai juga oleh Job).
 */
class MaxDateRange implements DataAwareRule, ValidationRule
{
    /** @var array<string, mixed> */
    private array $data = [];

    public function __construct(private int $days = 366) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $from = CarbonImmutable::parse((string) ($this->data['from'] ?? ''));
            $to = CarbonImmutable::parse((string) $value);
        } catch (Throwable) {
            return; // format tanggal divalidasi aturan lain
        }

        if ($from->diffInDays($to, true) > $this->days) {
            $fail('Rentang tanggal maksimal satu tahun.');
        }
    }
}
