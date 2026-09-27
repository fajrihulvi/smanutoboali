import { Head, Link } from '@inertiajs/react';
import { Pencil, Plus, UserRoundSearch } from 'lucide-react';
import { DataTable } from '@/Components/shared/DataTable';
import type { DataTableColumn } from '@/Components/shared/DataTable';
import { FilterBar } from '@/Components/shared/FilterBar';
import { PageHeader } from '@/Components/shared/PageHeader';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import { Button } from '@/Components/ui/button';
import { useFilters } from '@/hooks/use-filters';
import AppLayout from '@/Layouts/AppLayout';
import { SelectFilter } from '@/Modules/People/Components/SelectFilter';
import type { GuardianRow } from '@/Modules/People/types';
import type { Paginated } from '@/types';

type Props = {
    guardians: Paginated<GuardianRow>;
    filters: { search?: string; account?: string };
};

const BASE = '/operator/master-data/orang-tua';

const columns: DataTableColumn<GuardianRow>[] = [
    {
        id: 'name',
        header: 'Nama',
        cell: ({ row }) => (
            <div className="min-w-44">
                <p className="font-medium text-slate-900">
                    {row.original.name}
                </p>
                <p className="text-xs text-slate-500">
                    {row.original.occupation ?? 'Pekerjaan belum diisi'}
                </p>
            </div>
        ),
    },
    {
        id: 'contact',
        header: 'Kontak',
        cell: ({ row }) => (
            <div className="text-sm">
                <p className="tabular-nums">{row.original.phone ?? '—'}</p>
                <p className="text-xs text-slate-500">
                    {row.original.email ?? ''}
                </p>
            </div>
        ),
    },
    {
        id: 'account',
        header: 'Akun login',
        cell: ({ row }) =>
            row.original.account ? (
                <StatusBadge
                    variant={
                        row.original.account.is_active ? 'success' : 'neutral'
                    }
                    dot
                >
                    {row.original.account.is_active ? 'Aktif' : 'Nonaktif'}
                </StatusBadge>
            ) : (
                <StatusBadge variant="warning">Belum ada</StatusBadge>
            ),
    },
    {
        id: 'actions',
        header: () => <span className="sr-only">Aksi</span>,
        meta: { className: 'text-right' },
        cell: ({ row }) => (
            <Button variant="ghost" size="sm" asChild>
                <Link href={`${BASE}/${row.original.public_id}/ubah`}>
                    <Pencil />
                    Ubah
                </Link>
            </Button>
        ),
    },
];

export default function GuardiansIndex({ guardians, filters }: Props) {
    const { values, set, reset, isActive } = useFilters(
        { search: filters.search, account: filters.account },
        { only: ['guardians', 'filters'] },
    );

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Master Data', href: BASE },
                { title: 'Orang Tua', href: BASE },
            ]}
        >
            <Head title="Data Orang Tua" />

            <PageHeader
                title="Data Orang Tua"
                description="Kelola data orang tua/wali siswa dan akun portal orang tua."
                actions={
                    <Button asChild>
                        <Link href={`${BASE}/tambah`}>
                            <Plus />
                            Tambah orang tua
                        </Link>
                    </Button>
                }
            />

            <DataTable
                columns={columns}
                data={guardians}
                getRowId={(row) => row.public_id}
                only={['guardians', 'filters']}
                empty={{
                    title: isActive
                        ? 'Orang tua tidak ditemukan'
                        : 'Belum ada data orang tua',
                    description: isActive
                        ? 'Coba ubah kata kunci atau filter.'
                        : 'Tambahkan data orang tua untuk mulai.',
                    icon: UserRoundSearch,
                }}
                toolbar={
                    <FilterBar
                        search={values.search}
                        onSearchChange={(value) => set('search', value, true)}
                        searchPlaceholder="Cari nama, telepon, email…"
                        isActive={isActive}
                        onReset={reset}
                    >
                        <SelectFilter
                            label="Filter akun login"
                            allLabel="Semua akun"
                            value={values.account}
                            options={[
                                { value: 'yes', label: 'Punya akun' },
                                { value: 'no', label: 'Belum punya akun' },
                            ]}
                            onChange={(value) => set('account', value)}
                        />
                    </FilterBar>
                }
            />
        </AppLayout>
    );
}
