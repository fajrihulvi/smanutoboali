<?php

namespace App\Modules\Report\Reports;

use App\Modules\Access\Enums\Permission;
use App\Modules\Core\Enums\Role;
use App\Modules\GuestBook\Enums\GuestCategory;
use App\Modules\GuestBook\Enums\GuestHost;
use App\Modules\GuestBook\Models\GuestVisit;
use Illuminate\Validation\Rule;

/**
 * Rekap buku tamu per rentang tanggal kunjungan.
 */
class GuestBookReport extends Report
{
    public function key(): string
    {
        return 'buku-tamu';
    }

    public function label(): string
    {
        return 'Buku Tamu';
    }

    public function description(): string
    {
        return 'Daftar tamu sekolah: waktu, identitas, kategori, keperluan, dan pihak yang ditemui.';
    }

    protected function roles(): array
    {
        return [Role::Operator];
    }

    protected function permissions(): array
    {
        return [Permission::GuestBookManage];
    }

    public function fields(ReportContext $context): array
    {
        return [
            ...$this->dateRangeFields(),
            ['name' => 'category', 'label' => 'Kategori tamu', 'type' => 'select', 'required' => false, 'default' => null, 'placeholder' => 'Semua kategori', 'options' => GuestCategory::options()],
            ['name' => 'host', 'label' => 'Bertemu dengan', 'type' => 'select', 'required' => false, 'default' => null, 'placeholder' => 'Semua', 'options' => GuestHost::options()],
        ];
    }

    public function rules(ReportContext $context): array
    {
        return [
            ...$this->dateRangeRules(),
            'category' => ['nullable', Rule::enum(GuestCategory::class)],
            'host' => ['nullable', Rule::enum(GuestHost::class)],
        ];
    }

    public function attributes(): array
    {
        return [...parent::attributes(), 'category' => 'kategori tamu', 'host' => 'bertemu dengan'];
    }

    public function build(ReportContext $context, array $filters): ReportData
    {
        $category = GuestCategory::tryFrom((string) ($filters['category'] ?? ''));
        $host = GuestHost::tryFrom((string) ($filters['host'] ?? ''));

        $visits = GuestVisit::query()
            ->visitedBetween($filters['from'], $filters['to'])
            ->when($category, fn ($q) => $q->where('category', $category))
            ->when($host, fn ($q) => $q->where('host', $host))
            ->orderBy('visited_at')
            ->orderBy('id')
            ->get();

        $rows = $visits->values()->map(fn (GuestVisit $v, int $i) => [
            $i + 1,
            $v->visited_at->format('d/m/Y'),
            $v->visited_at->format('H.i'),
            $v->full_name,
            $v->institution,
            $v->category->label(),
            $v->whatsapp,
            $v->purpose,
            $v->host->label(),
            $v->notes,
        ])->all();

        return new ReportData(
            title: 'Rekap Buku Tamu',
            meta: [
                $this->dateLabel($filters['from'], $filters['to']),
                ...($category ? ['Kategori: '.$category->label()] : []),
                ...($host ? ['Bertemu dengan: '.$host->label()] : []),
                'Jumlah tamu: '.number_format($visits->count(), 0, ',', '.'),
            ],
            columns: ['No', 'Tanggal', 'Jam', 'Nama lengkap', 'Instansi asal', 'Kategori', 'No. WhatsApp', 'Keperluan', 'Bertemu dengan', 'Catatan'],
            rows: $rows,
            numeric: [0],
            landscape: true,
        );
    }
}
