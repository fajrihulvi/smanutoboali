<?php

namespace App\Modules\Report\Reports;

use App\Modules\Access\Enums\Permission;
use App\Modules\Core\Enums\Role;
use App\Modules\Report\Rules\MaxDateRange;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Rule;

/**
 * Satu jenis laporan: siapa yang boleh, filter apa, dan cara menyusun datanya.
 *
 * @phpstan-type Field array{name: string, label: string, type: 'date'|'select', options?: list<array{value: string, label: string}>, required: bool, default: string|null, placeholder?: string}
 */
abstract class Report
{
    abstract public function key(): string;

    abstract public function label(): string;

    abstract public function description(): string;

    /**
     * @return list<Role>
     */
    abstract protected function roles(): array;

    /**
     * Izin tambahan (salah satu cukup), selain `report.export`.
     *
     * @return list<Permission>
     */
    protected function permissions(): array
    {
        return [];
    }

    /**
     * @return list<Field>
     */
    abstract public function fields(ReportContext $context): array;

    /**
     * @return array<string, mixed>
     */
    abstract public function rules(ReportContext $context): array;

    /**
     * @param  array<string, mixed>  $filters  sudah divalidasi
     */
    abstract public function build(ReportContext $context, array $filters): ReportData;

    public function allows(ReportContext $context): bool
    {
        if (! in_array($context->role, $this->roles(), true) || ! $context->user->can(Permission::ReportExport->value)) {
            return false;
        }

        $extra = $this->permissions();

        return $extra === [] || collect($extra)->contains(fn (Permission $p) => $context->user->can($p->value));
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'from' => 'tanggal mulai',
            'to' => 'tanggal akhir',
            'classroom' => 'rombel',
            'semester' => 'semester',
            'status' => 'status',
            'period' => 'periode',
            'track' => 'jalur',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Bagian filter yang sering dipakai
    |--------------------------------------------------------------------------
    */

    /**
     * @return list<Field>
     */
    protected function dateRangeFields(): array
    {
        $today = CarbonImmutable::today();

        return [
            ['name' => 'from', 'label' => 'Dari tanggal', 'type' => 'date', 'required' => true, 'default' => $today->startOfMonth()->toDateString()],
            ['name' => 'to', 'label' => 'Sampai tanggal', 'type' => 'date', 'required' => true, 'default' => $today->toDateString()],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function dateRangeRules(): array
    {
        return [
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from', new MaxDateRange],
        ];
    }

    /**
     * @return Field
     */
    protected function classroomField(ReportContext $context, bool $required = false): array
    {
        return [
            'name' => 'classroom',
            'label' => 'Rombel',
            'type' => 'select',
            'options' => $context->classroomOptions(),
            'required' => $required,
            'default' => null,
            'placeholder' => $required ? 'Pilih rombel' : 'Semua rombel',
        ];
    }

    /**
     * @return list<mixed>
     */
    protected function classroomRules(ReportContext $context, bool $required = false): array
    {
        return [$required ? 'required' : 'nullable', 'string', Rule::in(array_column($context->classroomOptions(), 'value'))];
    }

    protected function dateLabel(string $from, string $to): string
    {
        return 'Periode: '.CarbonImmutable::parse($from)->translatedFormat('j F Y').' – '.CarbonImmutable::parse($to)->translatedFormat('j F Y');
    }
}
