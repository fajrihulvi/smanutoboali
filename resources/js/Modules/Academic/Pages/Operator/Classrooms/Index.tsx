import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowUpRight,
    CalendarX2,
    ChevronRight,
    Plus,
    Shapes,
} from 'lucide-react';
import { useState } from 'react';
import { DataTable } from '@/Components/shared/DataTable';
import type { DataTableColumn } from '@/Components/shared/DataTable';
import { EmptyState } from '@/Components/shared/EmptyState';
import { PageHeader } from '@/Components/shared/PageHeader';
import { RowActions } from '@/Components/shared/RowActions';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import { Button } from '@/Components/ui/button';
import { useConfirmDelete } from '@/hooks/use-confirm-delete';
import AppLayout from '@/Layouts/AppLayout';
import { ClassroomFormDialog } from '@/Modules/Academic/Components/ClassroomFormDialog';
import { NativeSelect } from '@/Modules/Academic/Components/NativeSelect';
import { YearSelect } from '@/Modules/Academic/Components/YearSelect';
import type {
    ClassroomRow,
    GradeOption,
    Option,
    YearOption,
} from '@/Modules/Academic/types';
import type { Paginated } from '@/types';

type Props = {
    classrooms: Paginated<ClassroomRow>;
    year: YearOption | null;
    filters: { tingkat?: string };
    years: YearOption[];
    grades: GradeOption[];
    teachers: Option[];
};

const BASE = '/operator/master-data/kelas';

const classroomColumns = (
    onDelete: (classroom: ClassroomRow) => void,
): DataTableColumn<ClassroomRow>[] => [
    {
        id: 'name',
        header: 'Rombel',
        cell: ({ row }) => (
            <div>
                <p className="font-semibold text-slate-900">
                    {row.original.name}
                </p>
                <p className="text-xs text-slate-500">
                    Kelas {row.original.grade_label}
                </p>
            </div>
        ),
    },
    {
        id: 'homeroom',
        header: 'Wali kelas',
        cell: ({ row }) =>
            row.original.homeroom_teacher ? (
                row.original.homeroom_teacher.name
            ) : (
                <StatusBadge variant="warning">Belum ditentukan</StatusBadge>
            ),
    },
    {
        id: 'students',
        header: 'Siswa',
        meta: { className: 'tabular-nums' },
        cell: ({ row }) => (
            <span>
                {row.original.students_count ?? 0}
                {row.original.capacity && (
                    <span className="text-slate-400">
                        {' '}
                        / {row.original.capacity}
                    </span>
                )}
            </span>
        ),
    },
    {
        id: 'actions',
        header: () => <span className="sr-only">Aksi</span>,
        meta: { className: 'text-right' },
        cell: ({ row }) => (
            <div className="flex items-center justify-end gap-1">
                <Button variant="ghost" size="sm" asChild>
                    <Link href={`${BASE}/${row.original.public_id}`}>
                        Kelola
                        <ChevronRight />
                    </Link>
                </Button>
                <RowActions
                    label={`rombel ${row.original.name}`}
                    onDelete={() => onDelete(row.original)}
                />
            </div>
        ),
    },
];

export default function ClassroomsIndex({
    classrooms,
    year,
    filters,
    years,
    grades,
    teachers,
}: Props) {
    const [creating, setCreating] = useState(false);
    const { confirmDelete, dialog } = useConfirmDelete();
    const columns = classroomColumns((classroom) =>
        confirmDelete({
            url: `${BASE}/${classroom.public_id}`,
            title: `Hapus rombel ${classroom.name}?`,
            description:
                'Rombel yang sudah memiliki riwayat siswa tidak dapat dihapus.',
        }),
    );

    if (!year) {
        return (
            <AppLayout breadcrumbs={[{ title: 'Kelas', href: BASE }]}>
                <Head title="Rombel" />
                <PageHeader title="Rombel" />
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState
                        icon={CalendarX2}
                        title="Belum ada tahun ajaran"
                        description="Buat tahun ajaran terlebih dahulu sebelum menyusun rombel."
                        action={
                            <Button size="sm" asChild>
                                <Link href="/operator/master-data/tahun-ajaran">
                                    Kelola tahun ajaran
                                </Link>
                            </Button>
                        }
                    />
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Master Data', href: BASE },
                { title: 'Kelas', href: BASE },
            ]}
        >
            <Head title="Rombel" />

            <PageHeader
                title={`Rombel TA ${year.label}`}
                description="Rombongan belajar, wali kelas, dan anggota siswa per tahun ajaran."
                actions={
                    <>
                        <Button variant="outline" asChild>
                            <Link href={`${BASE}/naik-kelas`}>
                                <ArrowUpRight />
                                Naik kelas
                            </Link>
                        </Button>
                        <Button onClick={() => setCreating(true)}>
                            <Plus />
                            Tambah rombel
                        </Button>
                    </>
                }
            />

            <DataTable
                columns={columns}
                data={classrooms}
                getRowId={(row) => row.public_id}
                only={['classrooms', 'filters']}
                empty={{
                    title: 'Belum ada rombel',
                    description:
                        'Tambahkan rombel atau gunakan Naik Kelas untuk menyalin struktur tahun sebelumnya.',
                    icon: Shapes,
                }}
                toolbar={
                    <div className="flex flex-wrap items-center gap-2">
                        <YearSelect years={years} value={year.value} />
                        <NativeSelect
                            aria-label="Filter tingkat"
                            className="w-36"
                            value={filters.tingkat ?? ''}
                            onChange={(event) =>
                                router.get(
                                    BASE,
                                    {
                                        tahun: year.value,
                                        tingkat:
                                            event.target.value || undefined,
                                    },
                                    {
                                        preserveScroll: true,
                                        only: ['classrooms', 'filters'],
                                    },
                                )
                            }
                        >
                            <option value="">Semua tingkat</option>
                            {grades.map((grade) => (
                                <option key={grade.value} value={grade.value}>
                                    {grade.label}
                                </option>
                            ))}
                        </NativeSelect>
                        {!year.is_active && (
                            <StatusBadge variant="neutral">
                                Bukan tahun ajaran aktif
                            </StatusBadge>
                        )}
                    </div>
                }
            />

            <ClassroomFormDialog
                open={creating}
                onOpenChange={setCreating}
                classroom={null}
                yearPublicId={year.value}
                grades={grades}
                teachers={teachers}
            />
            {dialog}
        </AppLayout>
    );
}
