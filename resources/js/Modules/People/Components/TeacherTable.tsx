import { Link } from '@inertiajs/react';
import { Pencil, UserRoundSearch } from 'lucide-react';
import type { ReactNode } from 'react';
import { DataTable } from '@/Components/shared/DataTable';
import type { DataTableColumn } from '@/Components/shared/DataTable';
import { PersonAvatar } from '@/Components/shared/PersonAvatar';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import { Button } from '@/Components/ui/button';
import type { TeacherRow } from '@/Modules/People/types';
import type { Paginated } from '@/types';

type Props = {
    teachers: Paginated<TeacherRow>;
    toolbar: ReactNode;
    filtered: boolean;
    /** Kepala sekolah: tanpa kolom akun & tombol ubah. */
    readOnly?: boolean;
};

export function TeacherTable({
    teachers,
    toolbar,
    filtered,
    readOnly = false,
}: Props) {
    const columns: DataTableColumn<TeacherRow>[] = [
        {
            id: 'name',
            header: 'Guru',
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
                            {row.original.nip
                                ? `NIP ${row.original.nip}`
                                : row.original.nuptk
                                  ? `NUPTK ${row.original.nuptk}`
                                  : 'NIP/NUPTK belum diisi'}
                        </p>
                    </div>
                </div>
            ),
        },
        {
            id: 'employment',
            header: 'Status kepegawaian',
            cell: ({ row }) => (
                <span className="whitespace-nowrap">
                    {row.original.employment_status.label}
                </span>
            ),
        },
        {
            id: 'phone',
            header: 'Telepon',
            cell: ({ row }) => (
                <span className="tabular-nums">
                    {row.original.phone ?? '—'}
                </span>
            ),
        },
        {
            id: 'status',
            header: 'Status',
            cell: ({ row }) =>
                row.original.is_active ? (
                    <StatusBadge variant="success" dot>
                        Aktif
                    </StatusBadge>
                ) : (
                    <StatusBadge variant="neutral" dot>
                        Nonaktif
                    </StatusBadge>
                ),
        },
    ];

    if (!readOnly) {
        columns.push(
            {
                id: 'account',
                header: 'Akun login',
                cell: ({ row }) =>
                    row.original.account ? (
                        <span className="text-xs text-slate-600">
                            {row.original.account.email}
                        </span>
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
                        <Link
                            href={`/operator/master-data/guru/${row.original.public_id}/ubah`}
                        >
                            <Pencil />
                            Ubah
                        </Link>
                    </Button>
                ),
            },
        );
    }

    return (
        <DataTable
            columns={columns}
            data={teachers}
            getRowId={(row) => row.public_id}
            only={['teachers', 'filters']}
            toolbar={toolbar}
            empty={{
                title: filtered
                    ? 'Guru tidak ditemukan'
                    : 'Belum ada data guru',
                description: filtered
                    ? 'Coba ubah kata kunci atau filter.'
                    : 'Tambahkan data guru untuk mulai.',
                icon: UserRoundSearch,
            }}
        />
    );
}
