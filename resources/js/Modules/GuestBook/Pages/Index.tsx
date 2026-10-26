import { Head, Link } from '@inertiajs/react';
import {
    BookOpen,
    CalendarDays,
    ExternalLink,
    Eye,
    FileText,
    Plus,
    UsersRound,
} from 'lucide-react';
import { useState } from 'react';
import { DataTable } from '@/Components/shared/DataTable';
import type { DataTableColumn } from '@/Components/shared/DataTable';
import { FilterBar } from '@/Components/shared/FilterBar';
import { PageHeader } from '@/Components/shared/PageHeader';
import { PersonAvatar } from '@/Components/shared/PersonAvatar';
import { RowActions } from '@/Components/shared/RowActions';
import { StatCard } from '@/Components/shared/StatCard';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { useConfirmDelete } from '@/hooks/use-confirm-delete';
import { useFilters } from '@/hooks/use-filters';
import AppLayout from '@/Layouts/AppLayout';
import { formatDateTime, formatNumber } from '@/lib/format';
import { GuestVisitDetail } from '@/Modules/GuestBook/Components/GuestVisitDetail';
import { RecapDialog } from '@/Modules/GuestBook/Components/RecapDialog';
import type { GuestVisitRow, Option } from '@/Modules/GuestBook/types';
import { SelectFilter } from '@/Modules/People/Components/SelectFilter';
import type { Paginated } from '@/types';

type Props = {
    base: string;
    visits: Paginated<GuestVisitRow>;
    filters: {
        search?: string;
        category?: string;
        host?: string;
        from?: string;
        to?: string;
    };
    categories: Option[];
    hosts: Option[];
    stats: { today: number; month: number };
    can: { create: boolean; recap: boolean };
};

export default function GuestBookIndex({
    base,
    visits,
    filters,
    categories,
    hosts,
    stats,
    can,
}: Props) {
    const { values, set, reset, isActive } = useFilters(
        {
            search: filters.search,
            category: filters.category,
            host: filters.host,
            from: filters.from,
            to: filters.to,
        },
        { only: ['visits', 'filters'] },
    );
    const [detail, setDetail] = useState<GuestVisitRow | null>(null);
    const [recapOpen, setRecapOpen] = useState(false);
    const { confirmDelete, dialog } = useConfirmDelete();

    const columns: DataTableColumn<GuestVisitRow>[] = [
        {
            id: 'guest',
            header: 'Tamu',
            cell: ({ row }) => (
                <button
                    type="button"
                    onClick={() => setDetail(row.original)}
                    className="flex min-w-0 items-center gap-3 text-left"
                >
                    <PersonAvatar
                        name={row.original.full_name}
                        photoUrl={row.original.photo_thumb_url}
                    />
                    <span className="min-w-0">
                        <span className="block font-medium break-words text-slate-900 hover:text-brand-700">
                            {row.original.full_name}
                        </span>
                        <span className="block text-xs break-words text-slate-500">
                            {row.original.institution} ·{' '}
                            {row.original.category.label}
                        </span>
                    </span>
                </button>
            ),
        },
        {
            id: 'host',
            header: 'Bertemu dengan',
            cell: ({ row }) => (
                <span className="whitespace-nowrap">
                    {row.original.host.label}
                </span>
            ),
        },
        {
            id: 'purpose',
            header: 'Keperluan',
            cell: ({ row }) => (
                <span className="line-clamp-2 min-w-48 text-slate-700">
                    {row.original.purpose}
                </span>
            ),
        },
        {
            id: 'visited_at',
            header: 'Waktu',
            cell: ({ row }) => (
                <span className="whitespace-nowrap text-slate-600 tabular-nums">
                    {formatDateTime(row.original.visited_at)}
                </span>
            ),
        },
        {
            id: 'actions',
            header: () => <span className="sr-only">Aksi</span>,
            cell: ({ row }) => (
                <div className="flex justify-end gap-1">
                    <Button
                        variant="ghost"
                        size="icon"
                        className="size-8"
                        aria-label={`Detail ${row.original.full_name}`}
                        title="Detail"
                        onClick={() => setDetail(row.original)}
                    >
                        <Eye className="size-4" />
                    </Button>
                    {(row.original.can.update || row.original.can.delete) && (
                        <RowActions
                            label={row.original.full_name}
                            editHref={
                                row.original.can.update
                                    ? `${base}/${row.original.public_id}/ubah`
                                    : undefined
                            }
                            onDelete={
                                row.original.can.delete
                                    ? () =>
                                          confirmDelete({
                                              url: `${base}/${row.original.public_id}`,
                                              title: `Hapus data tamu ${row.original.full_name}?`,
                                              description:
                                                  'Data kunjungan ini tidak akan tampil lagi di buku tamu dan rekap.',
                                          })
                                    : undefined
                            }
                        />
                    )}
                </div>
            ),
        },
    ];

    return (
        <AppLayout breadcrumbs={[{ title: 'Buku Tamu', href: base }]}>
            <Head title="Buku Tamu" />

            <PageHeader
                title="Buku Tamu"
                description="Daftar tamu yang berkunjung ke sekolah, diisi tamu lewat website atau dicatat petugas."
                actions={
                    <>
                        <Button variant="outline" asChild>
                            <a
                                href="/buku-tamu"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <ExternalLink />
                                Form tamu
                            </a>
                        </Button>
                        {can.recap && (
                            <Button
                                variant="outline"
                                onClick={() => setRecapOpen(true)}
                            >
                                <FileText />
                                Rekap PDF
                            </Button>
                        )}
                        {can.create && (
                            <Button asChild>
                                <Link href={`${base}/tambah`}>
                                    <Plus />
                                    Catat tamu
                                </Link>
                            </Button>
                        )}
                    </>
                }
            />

            <div className="grid gap-4 sm:grid-cols-2">
                <StatCard
                    label="Tamu hari ini"
                    value={formatNumber(stats.today)}
                    icon={UsersRound}
                />
                <StatCard
                    label="Tamu bulan ini"
                    value={formatNumber(stats.month)}
                    icon={CalendarDays}
                />
            </div>

            <DataTable
                columns={columns}
                data={visits}
                getRowId={(row) => row.public_id}
                only={['visits', 'filters']}
                toolbar={
                    <FilterBar
                        search={values.search}
                        onSearchChange={(value) => set('search', value, true)}
                        searchPlaceholder="Cari nama, instansi, keperluan…"
                        isActive={isActive}
                        onReset={reset}
                    >
                        <SelectFilter
                            label="Kategori tamu"
                            allLabel="Semua kategori"
                            value={values.category}
                            options={categories}
                            onChange={(value) => set('category', value)}
                        />
                        <SelectFilter
                            label="Bertemu dengan"
                            allLabel="Semua tujuan"
                            value={values.host}
                            options={hosts}
                            onChange={(value) => set('host', value)}
                        />
                        <div className="grid w-full grid-cols-2 gap-2 sm:flex sm:w-auto">
                            <Input
                                type="date"
                                aria-label="Dari tanggal"
                                title="Dari tanggal"
                                value={values.from ?? ''}
                                max={values.to}
                                onChange={(e) =>
                                    set('from', e.target.value || undefined)
                                }
                                className="w-full min-w-0 sm:w-40"
                            />
                            <Input
                                type="date"
                                aria-label="Sampai tanggal"
                                title="Sampai tanggal"
                                value={values.to ?? ''}
                                min={values.from}
                                onChange={(e) =>
                                    set('to', e.target.value || undefined)
                                }
                                className="w-full min-w-0 sm:w-40"
                            />
                        </div>
                    </FilterBar>
                }
                empty={
                    isActive
                        ? {
                              title: 'Tidak ada tamu yang cocok',
                              description: 'Ubah atau reset filter.',
                              icon: BookOpen,
                          }
                        : {
                              title: 'Belum ada tamu',
                              description:
                                  'Tamu yang mengisi buku tamu di website akan muncul di sini.',
                              icon: BookOpen,
                          }
                }
            />

            <GuestVisitDetail
                visit={detail}
                onClose={() => setDetail(null)}
                editBase={can.create ? base : undefined}
            />
            {can.recap && (
                <RecapDialog open={recapOpen} onOpenChange={setRecapOpen} />
            )}
            {dialog}
        </AppLayout>
    );
}
