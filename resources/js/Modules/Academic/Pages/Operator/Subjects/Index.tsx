import { Head, router } from '@inertiajs/react';
import { BookOpen, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { ConfirmDialog } from '@/Components/shared/ConfirmDialog';
import { DataTable } from '@/Components/shared/DataTable';
import type { DataTableColumn } from '@/Components/shared/DataTable';
import { FilterBar } from '@/Components/shared/FilterBar';
import { PageHeader } from '@/Components/shared/PageHeader';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import { Button } from '@/Components/ui/button';
import { useFilters } from '@/hooks/use-filters';
import AppLayout from '@/Layouts/AppLayout';
import { SubjectFormDialog } from '@/Modules/Academic/Components/SubjectFormDialog';
import type { Option, SubjectRow } from '@/Modules/Academic/types';
import type { Paginated } from '@/types';

type Props = {
    subjects: Paginated<SubjectRow>;
    filters: { search?: string };
    groups: Option[];
};

const BASE = '/operator/master-data/mapel';

export default function SubjectsIndex({ subjects, filters, groups }: Props) {
    const { values, set, reset, isActive } = useFilters(
        { search: filters.search },
        { only: ['subjects', 'filters'] },
    );
    const [dialog, setDialog] = useState<{
        open: boolean;
        subject: SubjectRow | null;
    }>({ open: false, subject: null });
    const [deleting, setDeleting] = useState<SubjectRow | null>(null);
    const [processing, setProcessing] = useState(false);

    const columns: DataTableColumn<SubjectRow>[] = [
        {
            id: 'code',
            header: 'Kode',
            meta: { className: 'w-24 font-mono text-xs' },
            cell: ({ row }) => row.original.code,
        },
        {
            id: 'name',
            header: 'Mata pelajaran',
            cell: ({ row }) => (
                <div>
                    <p className="font-medium text-slate-900">
                        {row.original.name}
                    </p>
                    <p className="text-xs text-slate-500">
                        {row.original.group.label}
                    </p>
                </div>
            ),
        },
        {
            id: 'assignments',
            header: 'Rombel diampu',
            meta: { className: 'tabular-nums' },
            cell: ({ row }) => row.original.assignments_count ?? 0,
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
            id: 'actions',
            header: () => <span className="sr-only">Aksi</span>,
            meta: { className: 'text-right' },
            cell: ({ row }) => (
                <div className="flex justify-end gap-1">
                    <Button
                        variant="ghost"
                        size="icon"
                        className="size-8"
                        aria-label={`Ubah ${row.original.name}`}
                        onClick={() =>
                            setDialog({ open: true, subject: row.original })
                        }
                    >
                        <Pencil className="size-4" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        className="size-8 text-slate-400 hover:bg-rose-50 hover:text-rose-600"
                        aria-label={`Hapus ${row.original.name}`}
                        onClick={() => setDeleting(row.original)}
                    >
                        <Trash2 className="size-4" />
                    </Button>
                </div>
            ),
        },
    ];

    const destroy = () => {
        if (!deleting) {
            return;
        }

        router.delete(`${BASE}/${deleting.public_id}`, {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => {
                setProcessing(false);
                setDeleting(null);
            },
        });
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Master Data', href: BASE },
                { title: 'Mata Pelajaran', href: BASE },
            ]}
        >
            <Head title="Mata Pelajaran" />

            <PageHeader
                title="Mata Pelajaran"
                description="Daftar mata pelajaran yang dipakai untuk penugasan mengajar dan penilaian."
                actions={
                    <Button
                        onClick={() => setDialog({ open: true, subject: null })}
                    >
                        <Plus />
                        Tambah mapel
                    </Button>
                }
            />

            <DataTable
                columns={columns}
                data={subjects}
                getRowId={(row) => row.public_id}
                only={['subjects', 'filters']}
                empty={
                    isActive
                        ? {
                              title: 'Tidak ada mapel yang cocok',
                              description: 'Ubah kata kunci pencarian.',
                          }
                        : {
                              title: 'Belum ada mata pelajaran',
                              description: 'Tambahkan mata pelajaran pertama.',
                              icon: BookOpen,
                          }
                }
                toolbar={
                    <FilterBar
                        search={values.search}
                        onSearchChange={(value) => set('search', value, true)}
                        searchPlaceholder="Cari kode atau nama…"
                        isActive={isActive}
                        onReset={reset}
                    />
                }
            />

            <SubjectFormDialog
                open={dialog.open}
                onOpenChange={(open) =>
                    setDialog((current) => ({ ...current, open }))
                }
                subject={dialog.subject}
                groups={groups}
            />
            <ConfirmDialog
                open={deleting !== null}
                onOpenChange={(open) => !open && setDeleting(null)}
                variant="danger"
                title={`Hapus ${deleting?.name ?? ''}?`}
                description="Mapel yang masih dipakai di penugasan mengajar tidak dapat dihapus; nonaktifkan saja."
                confirmLabel="Ya, hapus"
                processing={processing}
                onConfirm={destroy}
            />
        </AppLayout>
    );
}
