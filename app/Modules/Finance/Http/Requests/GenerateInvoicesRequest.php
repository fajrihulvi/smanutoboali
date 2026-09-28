<?php

namespace App\Modules\Finance\Http\Requests;

use App\Modules\Academic\Models\Classroom;
use App\Modules\Finance\Enums\FeeFrequency;
use App\Modules\Finance\Models\FeeType;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Services\InvoiceTargets;
use App\Support\ActivePeriod;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Dipakai untuk preview dan eksekusi generate tagihan massal.
 *
 * @phpstan-import-type Scope from InvoiceTargets
 */
class GenerateInvoicesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', Invoice::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $monthly = $this->feeType()?->frequency === FeeFrequency::Monthly;

        return [
            'fee_type' => ['required', 'string', Rule::exists(FeeType::class, 'public_id')->where('is_active', true)->whereNull('deleted_at')],
            'month' => [Rule::requiredIf($monthly), 'nullable', 'date_format:Y-m'],
            'period_label' => [Rule::requiredIf(! $monthly), 'nullable', 'string', 'max:40'],
            'amount' => ['required', 'integer', 'min:0', 'max:100000000'],
            'due_date' => ['required', 'date_format:Y-m-d'],
            'scope' => ['required', Rule::in([InvoiceTargets::ALL, InvoiceTargets::GRADE, InvoiceTargets::CLASSROOMS])],
            'grade' => ['nullable', 'required_if:scope,'.InvoiceTargets::GRADE, 'integer', 'min:1', 'max:12'],
            'classrooms' => ['nullable', 'required_if:scope,'.InvoiceTargets::CLASSROOMS, 'array'],
            'classrooms.*' => ['string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'fee_type' => 'jenis biaya', 'month' => 'bulan', 'period_label' => 'periode', 'amount' => 'nominal',
            'due_date' => 'jatuh tempo', 'scope' => 'sasaran', 'grade' => 'tingkat', 'classrooms' => 'rombel',
        ];
    }

    public function feeType(): ?FeeType
    {
        return FeeType::query()->where('public_id', $this->string('fee_type')->value())->first();
    }

    /**
     * @return array{0: string, 1: string} [period, label]
     */
    public function period(): array
    {
        if ($this->feeType()?->frequency === FeeFrequency::Monthly) {
            $month = CarbonImmutable::parse($this->string('month')->value().'-01');

            return [$month->format('Y-m'), $month->locale('id')->translatedFormat('F Y')];
        }

        $label = trim($this->string('period_label')->value());

        return [Str::limit(Str::slug($label), 20, ''), $label];
    }

    /**
     * @return Scope
     */
    public function scope(): array
    {
        return match ($this->string('scope')->value()) {
            InvoiceTargets::GRADE => ['type' => InvoiceTargets::GRADE, 'grade' => $this->integer('grade')],
            InvoiceTargets::CLASSROOMS => [
                'type' => InvoiceTargets::CLASSROOMS,
                'classroom_ids' => Classroom::query()
                    ->where('academic_year_id', app(ActivePeriod::class)->academicYearId() ?? 0)
                    ->whereIn('public_id', (array) $this->input('classrooms', []))
                    ->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            ],
            default => ['type' => InvoiceTargets::ALL],
        };
    }

    public function dueDate(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->string('due_date')->value());
    }
}
