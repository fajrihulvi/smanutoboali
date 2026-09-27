import { Link } from '@inertiajs/react';
import { Pencil, UserRoundSearch } from 'lucide-react';
import type { ReactNode } from 'react';
import { DataTable } from '@/Components/shared/DataTable';
import type { DataTableColumn } from '@/Components/shared/DataTable';
import { PersonAvatar } from '@/Components/shared/PersonAvatar';
import { Button } from '@/Components/ui/button';
import { StudentStatusBadge } from '@/Modules/People/Components/StudentStatusBadge';
import type { StudentRow } from '@/Modules/People/types';
import type { Paginated } from '@/types';

type Props = {
    students: Paginated<StudentRow>;
    toolbar: ReactNode;
    filtered: boolean;
    /** Kepala sekolah: tanpa tombol ubah. */
    readOnly?: boolean;
};

export function StudentTable({
    students,
    toolbar,
    filtered,
    readOnly = false,
}: Props) {
    const columns: DataTableColumn<StudentRow>[] = [
        {
            id: 'name',
            header: 'Siswa',
            cell: ({ row }) => (
                <div className="flex min-w-52 items-center gap-3">
                    <PersonAvatar
                        name={row.original.name}
                        photoUrl={row.original.photo_url}
                    />
                    <div className="min-w-0">
                        <p className="truncate font-medium text-slate-900">
                            {row.original.name}
                        </p>
                        <p className="text-xs text-slate-500 tabular-nums">
                            NIS {row.original.nis}
                            {row.original.nisn &&
                                ` · NISN ${row.original.nisn}`}
                        </p>
                    </div>
                </div>
            ),
        },
        {
            id: 'gender',
            header: 'L/P',
            cell: ({ row }) => row.original.gender.value,
        },
        {
            id: 'guardian',
            header: 'Orang tua utama',
            cell: ({ row }) => {
                const primary = row.original.guardians?.find(
                    (guardian) => guardian.is_primary,
                );

                return primary ? (
                    <span className="text-sm">
                        {primary.name}
                        <span className="text-xs text-slate-400">
                            {' '}
                            · {primary.relationship.label}
                        </span>
                    </span>
                ) : (
                    <span className="text-xs text-amber-700">
                        Belum terhubung
                    </span>
                );
            },
        },
        {
            id: 'status',
            header: 'Status',
            cell: ({ row }) => (
                <StudentStatusBadge status={row.original.status} />
            ),
        },
    ];

    if (!readOnly) {
        columns.push({
            id: 'actions',
            header: () => <span className="sr-only">Aksi</span>,
            meta: { className: 'text-right' },
            cell: ({ row }) => (
                <Button variant="ghost" size="sm" asChild>
                    <Link
                        href={`/operator/master-data/siswa/${row.original.public_id}/ubah`}
                    >
                        <Pencil />
                        Ubah
                    </Link>
                </Button>
            ),
        });
    }

    return (
        <DataTable
            columns={columns}
            data={students}
            getRowId={(row) => row.public_id}
            only={['students', 'filters']}
            toolbar={toolbar}
            empty={{
                title: filtered
                    ? 'Siswa tidak ditemukan'
                    : 'Belum ada data siswa',
                description: filtered
                    ? 'Coba ubah kata kunci atau filter.'
                    : 'Tambahkan siswa satu per satu atau impor dari Excel.',
                icon: UserRoundSearch,
            }}
        />
    );
}
