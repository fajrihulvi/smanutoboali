import { Head, Link } from '@inertiajs/react';
import { Users } from 'lucide-react';
import { DataTable } from '@/Components/shared/DataTable';
import type { DataTableColumn } from '@/Components/shared/DataTable';
import { FilterBar } from '@/Components/shared/FilterBar';
import { PageHeader } from '@/Components/shared/PageHeader';
import { useFilters } from '@/hooks/use-filters';
import { useInertiaLoading } from '@/hooks/use-inertia-loading';
import AppLayout from '@/Layouts/AppLayout';
import { formatDateTime } from '@/lib/format';
import type { Option } from '@/Modules/Academic/types';
import { SelectFilter } from '@/Modules/People/Components/SelectFilter';
import { ApplicantStatusBadge } from '@/Modules/Ppdb/Components/ApplicantStatusBadge';
import type { ApplicantRow } from '@/Modules/Ppdb/types';
import type { Paginated } from '@/types';

type Props = {
    base: string;
    applicants: Paginated<ApplicantRow>;
    periods: Option[];
    tracks: Option[];
    statuses: Option[];
    filters: { periode?: string; status?: string; jalur?: string; q?: string };
};

export default function ApplicantsIndex({
    base,
    applicants,
    periods,
    tracks,
    statuses,
    filters,
}: Props) {
    const { values, set, reset, isActive } = useFilters(
        {
            periode: filters.periode,
            status: filters.status,
            jalur: filters.jalur,
            q: filters.q,
        },
        { only: ['applicants', 'filters', 'tracks'] },
    );
    const loading = useInertiaLoading();

    const columns: DataTableColumn<ApplicantRow>[] = [
        {
            id: 'name',
            header: 'Calon siswa',
            cell: ({ row }) => (
                <Link
                    href={`${base}/pendaftar/${row.original.public_id}`}
                    className="block"
                >
                    <span className="block font-medium text-slate-900 hover:text-brand-700">
                        {row.original.name}
                    </span>
                    <span className="block font-mono text-xs text-slate-500">
                        {row.original.registration_number}
                    </span>
                </Link>
            ),
        },
        {
            id: 'track',
            header: 'Jalur',
            cell: ({ row }) => row.original.track.name,
        },
        {
            id: 'submitted',
            header: 'Dikirim',
            meta: { className: 'whitespace-nowrap' },
            cell: ({ row }) => formatDateTime(row.original.submitted_at),
        },
        {
            id: 'score',
            header: 'Skor',
            meta: { className: 'tabular-nums' },
            cell: ({ row }) => row.original.selection?.score ?? '—',
        },
        {
            id: 'status',
            header: 'Status',
            cell: ({ row }) => (
                <div className="space-y-1">
                    <ApplicantStatusBadge status={row.original.status} />
                    {row.original.status.value === 'ACCEPTED' &&
                        row.original.reregistration_submitted_at && (
                            <p className="text-xs text-amber-700">
                                Daftar ulang menunggu konfirmasi
                            </p>
                        )}
                </div>
            ),
        },
    ];

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'PPDB', href: `${base}/dashboard` },
                { title: 'Pendaftar', href: `${base}/pendaftar` },
            ]}
        >
            <Head title="Pendaftar PPDB" />
            <PageHeader
                title="Pendaftar"
                description="Pendaftaran terkirim menunggu verifikasi tampil paling atas (terlama dulu)."
            />
            <DataTable
                columns={columns}
                data={applicants}
                getRowId={(row) => row.public_id}
                only={['applicants', 'filters', 'tracks']}
                loading={loading}
                empty={
                    isActive
                        ? {
                              title: 'Tidak ditemukan',
                              description: 'Ubah filter atau kata kunci.',
                          }
                        : { title: 'Belum ada pendaftar', icon: Users }
                }
                toolbar={
                    <FilterBar
                        search={values.q}
                        onSearchChange={(v) => set('q', v, true)}
                        searchPlaceholder="Cari nama/nomor…"
                        isActive={isActive}
                        onReset={reset}
                    >
                        {periods.length > 1 && (
                            <SelectFilter
                                label="Periode"
                                allLabel="Periode terbaru"
                                value={values.periode}
                                options={periods}
                                onChange={(v) => set('periode', v)}
                                className="w-52"
                            />
                        )}
                        <SelectFilter
                            label="Jalur"
                            allLabel="Semua jalur"
                            value={values.jalur}
                            options={tracks}
                            onChange={(v) => set('jalur', v)}
                            className="w-36"
                        />
                        <SelectFilter
                            label="Status"
                            allLabel="Semua status"
                            value={values.status}
                            options={statuses}
                            onChange={(v) => set('status', v)}
                            className="w-52"
                        />
                    </FilterBar>
                }
            />
        </AppLayout>
    );
}
