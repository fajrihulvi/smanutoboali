import { Head, Link } from '@inertiajs/react';
import { Users } from 'lucide-react';
import { DataTable } from '@/Components/shared/DataTable';
import type { DataTableColumn } from '@/Components/shared/DataTable';
import { FilterBar } from '@/Components/shared/FilterBar';
import { PageHeader } from '@/Components/shared/PageHeader';
import { useFilters } from '@/hooks/use-filters';
import AppLayout from '@/Layouts/AppLayout';
import type { Paginated } from '@/types';

type TaughtStudent = {
    public_id: string;
    nis: string;
    name: string;
    gender: string;
    classroom: { public_id: string; name: string } | null;
};

type Props = {
    students: Paginated<TaughtStudent>;
    filters: { search?: string };
};

const columns: DataTableColumn<TaughtStudent>[] = [
    {
        id: 'name',
        header: 'Nama',
        cell: ({ row }) => (
            <div>
                <p className="font-medium text-slate-900">
                    {row.original.name}
                </p>
                <p className="text-xs text-slate-500 tabular-nums">
                    NIS {row.original.nis}
                </p>
            </div>
        ),
    },
    {
        id: 'gender',
        header: 'L/P',
        meta: { className: 'w-16' },
        cell: ({ row }) => row.original.gender,
    },
    {
        id: 'classroom',
        header: 'Rombel',
        cell: ({ row }) =>
            row.original.classroom ? (
                <Link
                    href={`/guru/kelas/${row.original.classroom.public_id}`}
                    className="font-medium text-brand-700 hover:underline"
                >
                    {row.original.classroom.name}
                </Link>
            ) : (
                <span className="text-slate-400">–</span>
            ),
    },
];

export default function TeacherStudentsIndex({ students, filters }: Props) {
    const { values, set, reset, isActive } = useFilters(
        { search: filters.search },
        { only: ['students', 'filters'] },
    );

    return (
        <AppLayout breadcrumbs={[{ title: 'Siswa', href: '/guru/siswa' }]}>
            <Head title="Siswa" />

            <PageHeader
                title="Siswa"
                description="Siswa di rombel yang Anda ampu atau walikan pada tahun ajaran aktif."
            />

            <DataTable
                columns={columns}
                data={students}
                getRowId={(row) => row.public_id}
                only={['students', 'filters']}
                empty={
                    isActive
                        ? {
                              title: 'Tidak ada siswa yang cocok',
                              description: 'Ubah kata kunci pencarian.',
                          }
                        : {
                              title: 'Belum ada siswa',
                              description:
                                  'Siswa tampil setelah Anda ditugaskan di rombel tahun ajaran aktif.',
                              icon: Users,
                          }
                }
                toolbar={
                    <FilterBar
                        search={values.search}
                        onSearchChange={(value) => set('search', value, true)}
                        searchPlaceholder="Cari nama atau NIS…"
                        isActive={isActive}
                        onReset={reset}
                    />
                }
            />
        </AppLayout>
    );
}
