import { Head, Link } from '@inertiajs/react';
import { Images, Plus } from 'lucide-react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { FilterBar } from '@/Components/shared/FilterBar';
import { PageHeader } from '@/Components/shared/PageHeader';
import { Pagination } from '@/Components/shared/Pagination';
import { RowActions } from '@/Components/shared/RowActions';
import { ListSkeleton } from '@/Components/shared/Skeletons';
import { Button } from '@/Components/ui/button';
import { useConfirmDelete } from '@/hooks/use-confirm-delete';
import { useFilters } from '@/hooks/use-filters';
import { useInertiaLoading } from '@/hooks/use-inertia-loading';
import AppLayout from '@/Layouts/AppLayout';
import { formatDate } from '@/lib/format';
import type { Option } from '@/Modules/Academic/types';
import { ContentStatusBadge } from '@/Modules/Cms/Components/ContentStatusBadge';
import { StatusMenu } from '@/Modules/Cms/Components/StatusMenu';
import type { GalleryRow } from '@/Modules/Cms/types';
import { SelectFilter } from '@/Modules/People/Components/SelectFilter';
import type { Paginated } from '@/types';

type Props = {
    galleries: Paginated<GalleryRow>;
    filters: { status?: string; q?: string };
    statuses: Option[];
};

const BASE = '/operator/cms/galeri';

export default function CmsGalleries({ galleries, filters, statuses }: Props) {
    const { values, set, reset, isActive } = useFilters(
        { q: filters.q || undefined, status: filters.status || undefined },
        { only: ['galleries', 'filters'] },
    );
    const loading = useInertiaLoading();
    const { confirmDelete, dialog } = useConfirmDelete();

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Website', href: '/operator/cms/halaman' },
                { title: 'Galeri', href: BASE },
            ]}
        >
            <Head title="Galeri" />
            <PageHeader
                title="Galeri"
                description="Album foto kegiatan sekolah. Setiap album berisi banyak foto dengan keterangan."
                actions={
                    <Button asChild>
                        <Link href={`${BASE}/tambah`}>
                            <Plus />
                            Buat album
                        </Link>
                    </Button>
                }
            />

            <div className="mb-4">
                <FilterBar
                    search={values.q}
                    onSearchChange={(value) => set('q', value, true)}
                    searchPlaceholder="Cari album…"
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
            ) : galleries.data.length === 0 ? (
                <section className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState
                        icon={Images}
                        title={
                            isActive
                                ? 'Album tidak ditemukan'
                                : 'Belum ada album'
                        }
                        description={
                            isActive
                                ? 'Coba ubah kata kunci atau filter.'
                                : 'Buat album untuk menampilkan foto kegiatan sekolah.'
                        }
                    />
                </section>
            ) : (
                <ul className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    {galleries.data.map((gallery) => (
                        <li
                            key={gallery.public_id}
                            className="min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
                        >
                            <Link
                                href={`${BASE}/${gallery.public_id}/ubah`}
                                className="block aspect-video bg-slate-100"
                            >
                                {gallery.cover ? (
                                    <img
                                        src={gallery.cover.url}
                                        alt=""
                                        loading="lazy"
                                        className="size-full object-cover"
                                    />
                                ) : (
                                    <span className="flex size-full items-center justify-center text-slate-300">
                                        <Images className="size-10" />
                                    </span>
                                )}
                            </Link>
                            <div className="flex items-start gap-2 p-4">
                                <div className="min-w-0 flex-1 space-y-1">
                                    <Link
                                        href={`${BASE}/${gallery.public_id}/ubah`}
                                        className="line-clamp-2 font-medium text-slate-900 hover:text-brand-700"
                                    >
                                        {gallery.title}
                                    </Link>
                                    <div className="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                        <ContentStatusBadge
                                            status={gallery.status}
                                        />
                                        <span>{gallery.photos_count} foto</span>
                                        {gallery.date && (
                                            <span>
                                                {formatDate(gallery.date)}
                                            </span>
                                        )}
                                    </div>
                                </div>
                                <StatusMenu
                                    url={`${BASE}/${gallery.public_id}`}
                                    label={gallery.title}
                                    status={gallery.status}
                                />
                                <RowActions
                                    label={gallery.title}
                                    onDelete={() =>
                                        confirmDelete({
                                            url: `${BASE}/${gallery.public_id}`,
                                            title: `Hapus album ${gallery.title}?`,
                                            description:
                                                'Album dan fotonya tidak lagi tampil di website.',
                                        })
                                    }
                                />
                            </div>
                        </li>
                    ))}
                </ul>
            )}
            <Pagination paginated={galleries} only={['galleries', 'filters']} />
            {dialog}
        </AppLayout>
    );
}
