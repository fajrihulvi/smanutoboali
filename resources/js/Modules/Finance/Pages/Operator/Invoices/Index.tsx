import { Head, Link } from '@inertiajs/react';
import {
    AlarmClock,
    FilePlus2,
    ReceiptText,
    ShieldCheck,
    Wallet,
} from 'lucide-react';
import { DataTable } from '@/Components/shared/DataTable';
import type { DataTableColumn } from '@/Components/shared/DataTable';
import { FilterBar } from '@/Components/shared/FilterBar';
import { PageHeader } from '@/Components/shared/PageHeader';
import { StatCard } from '@/Components/shared/StatCard';
import { Button } from '@/Components/ui/button';
import { useFilters } from '@/hooks/use-filters';
import { useInertiaLoading } from '@/hooks/use-inertia-loading';
import AppLayout from '@/Layouts/AppLayout';
import { formatCurrency, formatDate } from '@/lib/format';
import type { Option } from '@/Modules/Academic/types';
import { InvoiceStatusBadge } from '@/Modules/Finance/Components/InvoiceStatusBadge';
import type { FinanceSummary, InvoiceRow } from '@/Modules/Finance/types';
import { SelectFilter } from '@/Modules/People/Components/SelectFilter';
import type { Paginated } from '@/types';

type Props = {
    invoices: Paginated<InvoiceRow>;
    filters: {
        q?: string;
        status?: string;
        jenis?: string;
        rombel?: string;
        periode?: string;
    };
    summary: FinanceSummary;
    feeTypes: Option[];
    classrooms: Option[];
    statuses: Option[];
};

const BASE = '/operator/keuangan';

export default function InvoicesIndex({
    invoices,
    filters,
    summary,
    feeTypes,
    classrooms,
    statuses,
}: Props) {
    const { values, set, reset, isActive } = useFilters(
        {
            q: filters.q,
            status: filters.status,
            jenis: filters.jenis,
            rombel: filters.rombel,
            periode: filters.periode,
        },
        { only: ['invoices', 'filters', 'summary'] },
    );
    const loading = useInertiaLoading();

    const columns: DataTableColumn<InvoiceRow>[] = [
        {
            id: 'student',
            header: 'Siswa',
            cell: ({ row }) => (
                <Link
                    href={`${BASE}/tagihan/${row.original.public_id}`}
                    className="block min-w-0"
                >
                    <span className="block font-medium text-slate-900 hover:text-brand-700">
                        {row.original.student.name}
                    </span>
                    <span className="block text-xs text-slate-500">
                        {row.original.student.nis}
                        {row.original.classroom &&
                            ` · ${row.original.classroom}`}
                    </span>
                </Link>
            ),
        },
        {
            id: 'title',
            header: 'Tagihan',
            cell: ({ row }) => (
                <div>
                    <p className="text-slate-900">{row.original.title}</p>
                    <p className="font-mono text-xs text-slate-500">
                        {row.original.number}
                    </p>
                </div>
            ),
        },
        {
            id: 'due',
            header: 'Jatuh tempo',
            meta: { className: 'whitespace-nowrap' },
            cell: ({ row }) => formatDate(row.original.due_date),
        },
        {
            id: 'total',
            header: 'Total / sisa',
            meta: { className: 'text-right tabular-nums whitespace-nowrap' },
            cell: ({ row }) => (
                <div>
                    <p className="text-slate-900">
                        {formatCurrency(row.original.total)}
                    </p>
                    {row.original.remaining > 0 &&
                        row.original.remaining !== row.original.total && (
                            <p className="text-xs text-amber-700">
                                sisa {formatCurrency(row.original.remaining)}
                            </p>
                        )}
                </div>
            ),
        },
        {
            id: 'status',
            header: 'Status',
            cell: ({ row }) => <InvoiceStatusBadge invoice={row.original} />,
        },
    ];

    return (
        <AppLayout
            breadcrumbs={[{ title: 'Keuangan', href: `${BASE}/tagihan` }]}
        >
            <Head title="Tagihan" />
            <PageHeader
                title="Tagihan"
                description="Semua tagihan siswa. Klik nama siswa untuk mencatat pembayaran atau melihat rincian."
                actions={
                    <>
                        <Button variant="outline" asChild>
                            <Link href={`${BASE}/verifikasi`}>
                                <ShieldCheck />
                                Verifikasi
                                {summary.pending_verification > 0 &&
                                    ` (${summary.pending_verification})`}
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href={`${BASE}/buat`}>
                                <FilePlus2 />
                                Buat tagihan
                            </Link>
                        </Button>
                    </>
                }
            />

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard
                    label="Total tunggakan"
                    value={formatCurrency(summary.outstanding)}
                    icon={Wallet}
                    hint={`${summary.outstanding_invoices} tagihan belum lunas`}
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

            <DataTable
                columns={columns}
                data={invoices}
                getRowId={(row) => row.public_id}
                only={['invoices', 'filters', 'summary']}
                loading={loading}
                empty={
                    isActive
                        ? {
                              title: 'Tagihan tidak ditemukan',
                              description: 'Ubah kata kunci atau filter.',
                          }
                        : {
                              title: 'Belum ada tagihan',
                              description:
                                  'Buat tagihan massal untuk rombel atau seluruh siswa.',
                              icon: Wallet,
                          }
                }
                toolbar={
                    <FilterBar
                        search={values.q}
                        onSearchChange={(value) => set('q', value, true)}
                        searchPlaceholder="Cari siswa, NIS, atau nomor…"
                        isActive={isActive}
                        onReset={reset}
                    >
                        <SelectFilter
                            label="Filter status"
                            allLabel="Semua status"
                            value={values.status}
                            options={statuses}
                            onChange={(v) => set('status', v)}
                            className="w-44"
                        />
                        <SelectFilter
                            label="Filter jenis biaya"
                            allLabel="Semua jenis"
                            value={values.jenis}
                            options={feeTypes}
                            onChange={(v) => set('jenis', v)}
                            className="w-40"
                        />
                        <SelectFilter
                            label="Filter rombel"
                            allLabel="Semua rombel"
                            value={values.rombel}
                            options={classrooms}
                            onChange={(v) => set('rombel', v)}
                            className="w-36"
                        />
                    </FilterBar>
                }
            />
        </AppLayout>
    );
}
