import { Head, Link } from '@inertiajs/react';
import { Plus, UserRoundSearch } from 'lucide-react';
import { Can } from '@/Components/shared/Can';
import { DataTable } from '@/Components/shared/DataTable';
import type { DataTableColumn } from '@/Components/shared/DataTable';
import { FilterBar } from '@/Components/shared/FilterBar';
import { PageHeader } from '@/Components/shared/PageHeader';
import { RowActions } from '@/Components/shared/RowActions';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import { Button } from '@/Components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { useConfirmDelete } from '@/hooks/use-confirm-delete';
import { useFilters } from '@/hooks/use-filters';
import AppLayout from '@/Layouts/AppLayout';
import { formatRelative } from '@/lib/format';
import { RoleBadges } from '@/Modules/Access/Components/RoleBadges';
import type { UserRow } from '@/Modules/Access/types';
import type { Paginated, Role } from '@/types';

type Props = {
    users: Paginated<UserRow>;
    filters: { search?: string; role?: string; status?: string };
    roles: Role[];
};

const userColumns = (
    onDelete: (user: UserRow) => void,
): DataTableColumn<UserRow>[] => [
    {
        id: 'name',
        header: 'Nama',
        cell: ({ row }) => (
            <div className="min-w-44">
                <p className="font-medium text-slate-900">
                    {row.original.name}
                    {row.original.is_self && (
                        <span className="ml-1.5 text-xs font-normal text-slate-400">
                            (Anda)
                        </span>
                    )}
                </p>
                <p className="text-xs text-slate-500">{row.original.email}</p>
            </div>
        ),
    },
    {
        id: 'roles',
        header: 'Role',
        cell: ({ row }) => <RoleBadges roles={row.original.roles} />,
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
    {
        id: 'last_login_at',
        header: 'Login terakhir',
        cell: ({ row }) => (
            <span className="text-sm whitespace-nowrap text-slate-600">
                {row.original.last_login_at
                    ? formatRelative(row.original.last_login_at)
                    : 'Belum pernah'}
            </span>
        ),
    },
    {
        id: 'actions',
        header: () => <span className="sr-only">Aksi</span>,
        meta: { className: 'text-right' },
        cell: ({ row }) => (
            <RowActions
                label={row.original.name}
                editHref={`/operator/users/${row.original.public_id}/edit`}
                onDelete={
                    row.original.is_self
                        ? undefined
                        : () => onDelete(row.original)
                }
            />
        ),
    },
];

export default function UsersIndex({ users, filters, roles }: Props) {
    const { values, set, reset, isActive } = useFilters(
        { search: filters.search, role: filters.role, status: filters.status },
        { only: ['users', 'filters'] },
    );
    const { confirmDelete, dialog } = useConfirmDelete();
    const columns = userColumns((user) =>
        confirmDelete({
            url: `/operator/users/${user.public_id}`,
            title: `Hapus akun ${user.email}?`,
            description:
                'Akun dihapus permanen dan tidak bisa login lagi. Akun yang masih tertaut ke data guru/orang tua harus dilepas dulu dari datanya. Untuk memblokir sementara, cukup nonaktifkan akun.',
        }),
    );

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'User Management', href: '/operator/users' },
            ]}
        >
            <Head title="Pengguna" />

            <PageHeader
                title="Pengguna"
                description="Kelola akun, role, dan status aktif pengguna aplikasi."
                actions={
                    <Can permission="user.manage">
                        <Button asChild>
                            <Link href="/operator/users/create">
                                <Plus />
                                Tambah pengguna
                            </Link>
                        </Button>
                    </Can>
                }
            />

            <DataTable
                columns={columns}
                data={users}
                getRowId={(row) => row.public_id}
                only={['users', 'filters']}
                empty={{
                    title: isActive
                        ? 'Pengguna tidak ditemukan'
                        : 'Belum ada pengguna',
                    description: isActive
                        ? 'Coba ubah kata kunci atau filter.'
                        : 'Tambahkan pengguna pertama untuk mulai.',
                    icon: UserRoundSearch,
                }}
                toolbar={
                    <FilterBar
                        search={values.search}
                        onSearchChange={(value) => set('search', value, true)}
                        searchPlaceholder="Cari nama atau email…"
                        isActive={isActive}
                        onReset={reset}
                    >
                        <Select
                            value={values.role ?? 'all'}
                            onValueChange={(value) =>
                                set('role', value === 'all' ? undefined : value)
                            }
                        >
                            <SelectTrigger
                                className="w-40"
                                aria-label="Filter role"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua role</SelectItem>
                                {roles.map((role) => (
                                    <SelectItem
                                        key={role.code}
                                        value={role.code}
                                    >
                                        {role.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Select
                            value={values.status ?? 'all'}
                            onValueChange={(value) =>
                                set(
                                    'status',
                                    value === 'all' ? undefined : value,
                                )
                            }
                        >
                            <SelectTrigger
                                className="w-36"
                                aria-label="Filter status"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    Semua status
                                </SelectItem>
                                <SelectItem value="active">Aktif</SelectItem>
                                <SelectItem value="inactive">
                                    Nonaktif
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </FilterBar>
                }
            />
            {dialog}
        </AppLayout>
    );
}
