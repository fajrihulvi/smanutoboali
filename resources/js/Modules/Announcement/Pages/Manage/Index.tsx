import { Head, Link } from '@inertiajs/react';
import { Megaphone, Pin, Plus } from 'lucide-react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { FilterBar } from '@/Components/shared/FilterBar';
import { PageHeader } from '@/Components/shared/PageHeader';
import { Pagination } from '@/Components/shared/Pagination';
import { RowActions } from '@/Components/shared/RowActions';
import { ListSkeleton } from '@/Components/shared/Skeletons';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import { Button } from '@/Components/ui/button';
import { useConfirmDelete } from '@/hooks/use-confirm-delete';
import { useFilters } from '@/hooks/use-filters';
import { useInertiaLoading } from '@/hooks/use-inertia-loading';
import AppLayout from '@/Layouts/AppLayout';
import { formatDate } from '@/lib/format';
import type { Option } from '@/Modules/Academic/types';
import type {
    AnnouncementRow,
    AnnouncementStatusValue,
} from '@/Modules/Announcement/types';
import { SelectFilter } from '@/Modules/People/Components/SelectFilter';
import type { Paginated, StatusVariant } from '@/types';

type Props = {
    base: string;
    announcements: Paginated<AnnouncementRow>;
    filters: { status?: string; q?: string };
    statuses: Option[];
};

const VARIANTS: Record<AnnouncementStatusValue, StatusVariant> = {
    DRAF: 'neutral',
    TERJADWAL: 'info',
    TERBIT: 'success',
};

export default function ManageAnnouncements({
    base,
    announcements,
    filters,
    statuses,
}: Props) {
    const { values, set, reset, isActive } = useFilters(
        { q: filters.q || undefined, status: filters.status || undefined },
        { only: ['announcements', 'filters'] },
    );
    const loading = useInertiaLoading();
    const { confirmDelete, dialog } = useConfirmDelete();

    return (
        <AppLayout breadcrumbs={[{ title: 'Pengumuman', href: base }]}>
            <Head title="Pengumuman" />
            <PageHeader
                title="Pengumuman"
                description="Pengumuman resmi untuk semua warga sekolah, peran tertentu, atau rombel tertentu. Penerima diberi tahu saat pengumuman terbit."
                actions={
                    <Button asChild>
                        <Link href={`${base}/tambah`}>
                            <Plus />
                            Buat pengumuman
                        </Link>
                    </Button>
                }
            />

            <section className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div className="border-b border-slate-200 p-4">
                    <FilterBar
                        search={values.q}
                        onSearchChange={(value) => set('q', value, true)}
                        searchPlaceholder="Cari judul…"
                        isActive={isActive}
                        onReset={reset}
                    >
                        <SelectFilter
                            label="Filter status"
                            allLabel="Semua status"
                            value={values.status}
                            options={statuses}
                            onChange={(v) => set('status', v)}
                            className="w-40"
                        />
                    </FilterBar>
                </div>

                {loading ? (
                    <ListSkeleton rows={4} />
                ) : announcements.data.length === 0 ? (
                    <EmptyState
                        icon={Megaphone}
                        title={
                            isActive
                                ? 'Pengumuman tidak ditemukan'
                                : 'Belum ada pengumuman'
                        }
                        description={
                            isActive
                                ? 'Coba ubah kata kunci atau filter.'
                                : 'Buat pengumuman pertama untuk warga sekolah.'
                        }
                    />
                ) : (
                    <ul className="divide-y divide-slate-100">
                        {announcements.data.map((announcement) => (
                            <li
                                key={announcement.public_id}
                                className="flex gap-3 p-4 sm:px-5"
                            >
                                <div className="min-w-0 flex-1 space-y-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        {announcement.pinned && (
                                            <Pin
                                                className="size-4 text-brand-600"
                                                aria-label="Disematkan"
                                            />
                                        )}
                                        <Link
                                            href={`${base}/${announcement.public_id}`}
                                            className="font-semibold break-words text-slate-900 hover:text-brand-700"
                                        >
                                            {announcement.title}
                                        </Link>
                                        <StatusBadge
                                            variant={
                                                VARIANTS[
                                                    announcement.status.value
                                                ]
                                            }
                                            dot
                                        >
                                            {announcement.status.label}
                                        </StatusBadge>
                                    </div>
                                    <p className="line-clamp-2 text-sm text-slate-600">
                                        {announcement.body}
                                    </p>
                                    <p className="text-xs text-slate-400">
                                        Untuk {announcement.audience.label}
                                        {announcement.publish_at &&
                                            ` · ${announcement.status.value === 'TERJADWAL' ? 'tayang' : 'terbit'} ${formatDate(announcement.publish_at, 'd MMM yyyy, HH.mm')}`}
                                        {announcement.creator &&
                                            ` · ${announcement.creator}`}
                                    </p>
                                </div>
                                <RowActions
                                    label={announcement.title}
                                    editHref={`${base}/${announcement.public_id}/ubah`}
                                    onDelete={() =>
                                        confirmDelete({
                                            url: `${base}/${announcement.public_id}`,
                                            title: `Hapus "${announcement.title}"?`,
                                            description:
                                                'Pengumuman dan lampirannya dihapus untuk semua penerima.',
                                        })
                                    }
                                />
                            </li>
                        ))}
                    </ul>
                )}

                <Pagination
                    paginated={announcements}
                    only={['announcements', 'filters']}
                />
            </section>
            {dialog}
        </AppLayout>
    );
}
