import { Head, router } from '@inertiajs/react';
import {
    AlarmClock,
    Download,
    ReceiptText,
    ShieldCheck,
    Wallet,
} from 'lucide-react';
import { useState } from 'react';
import type { FormEvent, ReactNode } from 'react';
import { DataTable } from '@/Components/shared/DataTable';
import type { DataTableColumn } from '@/Components/shared/DataTable';
import { FilterBar } from '@/Components/shared/FilterBar';
import { PageHeader } from '@/Components/shared/PageHeader';
import { StatCard } from '@/Components/shared/StatCard';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { useFilters } from '@/hooks/use-filters';
import { useInertiaLoading } from '@/hooks/use-inertia-loading';
import AppLayout from '@/Layouts/AppLayout';
import { formatCurrency, formatDate } from '@/lib/format';
import type { Option } from '@/Modules/Academic/types';
import type { FinanceSummary } from '@/Modules/Finance/types';
import { SelectFilter } from '@/Modules/People/Components/SelectFilter';
import type { Paginated } from '@/types';

type StudentRow = {
    nis: string;
    name: string;
    classroom: string | null;
    invoices: number;
    amount: number;
    oldest_due: string | null;
};

type Props = {
    base: string;
    summary: FinanceSummary;
    byClassroom: {
        classroom: string;
        students: number;
        invoices: number;
        amount: number;
    }[];
    students: Paginated<StudentRow>;
    receipts: {
        months: { month: string; total: number }[];
        fee_types: { name: string; total: number }[];
        total: number;
    };
    range: { from: string; to: string };
    filters: { q?: string; rombel?: string };
    classrooms: Option[];
};

function Card({
    title,
    actions,
    children,
}: {
    title: string;
    actions?: ReactNode;
    children: ReactNode;
}) {
    return (
        <section className="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <header className="flex flex-wrap items-center justify-between gap-2">
                <h2 className="text-sm font-semibold text-slate-900">
                    {title}
                </h2>
                {actions}
            </header>
            {children}
        </section>
    );
}

/** Batang horizontal sederhana (tanpa library grafik). */
function Bars({ rows }: { rows: { label: string; total: number }[] }) {
    const max = Math.max(1, ...rows.map((r) => r.total));

    if (rows.length === 0) {
        return (
            <p className="text-sm text-slate-500">
                Belum ada penerimaan pada rentang ini.
            </p>
        );
    }

    return (
        <ul className="space-y-2">
            {rows.map((row) => (
                <li
                    key={row.label}
                    className="grid grid-cols-[6.5rem_1fr] items-center gap-3 text-sm sm:grid-cols-[8rem_1fr_8rem]"
                >
                    <span className="truncate text-slate-600">{row.label}</span>
                    <span className="h-2.5 overflow-hidden rounded-full bg-slate-100">
                        <span
                            className="block h-full rounded-full bg-brand-500"
                            style={{ width: `${(row.total / max) * 100}%` }}
                        />
                    </span>
                    <span className="col-span-2 text-right font-medium text-slate-900 tabular-nums sm:col-span-1">
                        {formatCurrency(row.total)}
                    </span>
                </li>
            ))}
        </ul>
    );
}

export default function FinanceReport({
    base,
    summary,
    byClassroom,
    students,
    receipts,
    range,
    filters,
    classrooms,
}: Props) {
    const { values, set, reset, isActive } = useFilters(
        { q: filters.q, rombel: filters.rombel },
        { only: ['students', 'filters'] },
    );
    const loading = useInertiaLoading();
    const [dates, setDates] = useState(range);

    const applyRange = (event: FormEvent) => {
        event.preventDefault();
        router.get(
            base,
            { ...filters, dari: dates.from, sampai: dates.to },
            { preserveScroll: true, only: ['receipts', 'range'] },
        );
    };

    const columns: DataTableColumn<StudentRow>[] = [
        {
            id: 'name',
            header: 'Siswa',
            cell: ({ row }) => (
                <div>
                    <p className="font-medium text-slate-900">
                        {row.original.name}
                    </p>
                    <p className="text-xs text-slate-500">
                        {row.original.nis}
                        {row.original.classroom &&
                            ` · ${row.original.classroom}`}
                    </p>
                </div>
            ),
        },
        {
            id: 'invoices',
            header: 'Tagihan',
            meta: { className: 'tabular-nums' },
            cell: ({ row }) => row.original.invoices,
        },
        {
            id: 'oldest',
            header: 'Jatuh tempo terlama',
            cell: ({ row }) => formatDate(row.original.oldest_due),
        },
        {
            id: 'amount',
            header: 'Tunggakan',
            meta: { className: 'text-right tabular-nums font-medium' },
            cell: ({ row }) => formatCurrency(row.original.amount),
        },
    ];

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Keuangan', href: base },
                { title: 'Rekap', href: base },
            ]}
        >
            <Head title="Rekap keuangan" />
            <PageHeader
                title="Rekap keuangan"
                description="Tunggakan per rombel & siswa, serta penerimaan per bulan dan jenis biaya."
            />

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard
                    label="Total tunggakan"
                    value={formatCurrency(summary.outstanding)}
                    icon={Wallet}
                    hint={`${summary.outstanding_invoices} tagihan`}
                />
                <StatCard
                    label="Lewat jatuh tempo"
                    value={formatCurrency(summary.overdue)}
                    icon={AlarmClock}
                />
                <StatCard
                    label="Diterima bulan ini"
                    value={formatCurrency(summary.received_this_month)}
                    icon={ReceiptText}
                />
                <StatCard
                    label="Menunggu verifikasi"
                    value={summary.pending_verification}
                    icon={ShieldCheck}
                />
            </div>

            <Card
                title={`Penerimaan · ${formatCurrency(receipts.total)}`}
                actions={
                    <Button variant="outline" size="sm" asChild>
                        <a
                            href={`${base}/penerimaan.xlsx?dari=${range.from}&sampai=${range.to}`}
                        >
                            <Download />
                            Excel
                        </a>
                    </Button>
                }
            >
                <form
                    onSubmit={applyRange}
                    className="flex flex-wrap items-end gap-3"
                >
                    <div className="grid gap-1">
                        <Label htmlFor="range-from" className="text-xs">
                            Dari
                        </Label>
                        <Input
                            id="range-from"
                            type="date"
                            value={dates.from}
                            onChange={(e) =>
                                setDates((d) => ({
                                    ...d,
                                    from: e.target.value,
                                }))
                            }
                            className="w-40"
                        />
                    </div>
                    <div className="grid gap-1">
                        <Label htmlFor="range-to" className="text-xs">
                            Sampai
                        </Label>
                        <Input
                            id="range-to"
                            type="date"
                            value={dates.to}
                            onChange={(e) =>
                                setDates((d) => ({ ...d, to: e.target.value }))
                            }
                            className="w-40"
                        />
                    </div>
                    <Button type="submit" variant="outline">
                        Terapkan
                    </Button>
                </form>
                <div className="grid gap-6 lg:grid-cols-2">
                    <div className="min-w-0 space-y-2">
                        <h3 className="text-xs font-semibold tracking-wide text-slate-500 uppercase">
                            Per bulan
                        </h3>
                        <Bars
                            rows={receipts.months.map((m) => ({
                                label: formatDate(`${m.month}-01`, 'MMM yyyy'),
                                total: m.total,
                            }))}
                        />
                    </div>
                    <div className="min-w-0 space-y-2">
                        <h3 className="text-xs font-semibold tracking-wide text-slate-500 uppercase">
                            Per jenis biaya
                        </h3>
                        <Bars
                            rows={receipts.fee_types.map((f) => ({
                                label: f.name,
                                total: f.total,
                            }))}
                        />
                    </div>
                </div>
            </Card>

            <Card title="Tunggakan per rombel">
                {byClassroom.length === 0 ? (
                    <p className="text-sm text-slate-500">
                        Tidak ada tunggakan.
                    </p>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-slate-200 text-left text-xs text-slate-500">
                                    <th className="py-2 font-medium">Rombel</th>
                                    <th className="py-2 text-right font-medium">
                                        Siswa
                                    </th>
                                    <th className="py-2 text-right font-medium">
                                        Tagihan
                                    </th>
                                    <th className="py-2 text-right font-medium">
                                        Tunggakan
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {byClassroom.map((row) => (
                                    <tr key={row.classroom}>
                                        <td className="py-2 text-slate-900">
                                            {row.classroom}
                                        </td>
                                        <td className="py-2 text-right tabular-nums">
                                            {row.students}
                                        </td>
                                        <td className="py-2 text-right tabular-nums">
                                            {row.invoices}
                                        </td>
                                        <td className="py-2 text-right font-medium tabular-nums">
                                            {formatCurrency(row.amount)}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </Card>

            <DataTable
                columns={columns}
                data={students}
                getRowId={(row) => row.nis}
                only={['students', 'filters']}
                loading={loading}
                empty={
                    isActive
                        ? {
                              title: 'Tidak ditemukan',
                              description: 'Ubah pencarian atau rombel.',
                          }
                        : {
                              title: 'Tidak ada tunggakan',
                              description: 'Semua tagihan sudah lunas.',
                              icon: Wallet,
                          }
                }
                toolbar={
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <FilterBar
                            search={values.q}
                            onSearchChange={(v) => set('q', v, true)}
                            searchPlaceholder="Cari siswa…"
                            isActive={isActive}
                            onReset={reset}
                        >
                            <SelectFilter
                                label="Filter rombel"
                                allLabel="Semua rombel"
                                value={values.rombel}
                                options={classrooms}
                                onChange={(v) => set('rombel', v)}
                                className="w-36"
                            />
                        </FilterBar>
                        <Button variant="outline" size="sm" asChild>
                            <a
                                href={`${base}/tunggakan.xlsx${values.rombel ? `?rombel=${values.rombel}` : ''}`}
                            >
                                <Download />
                                Excel tunggakan
                            </a>
                        </Button>
                    </div>
                }
            />
        </AppLayout>
    );
}
