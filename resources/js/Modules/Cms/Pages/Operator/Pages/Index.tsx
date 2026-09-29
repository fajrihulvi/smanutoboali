import { Head, Link } from '@inertiajs/react';
import { FileText, Lock, Plus } from 'lucide-react';
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
import type { PageRow } from '@/Modules/Cms/types';
import { SelectFilter } from '@/Modules/People/Components/SelectFilter';
import type { Paginated } from '@/types';

type Props = {
    pages: Paginated<PageRow>;
    filters: { status?: string; q?: string };
    statuses: Option[];
};

const BASE = '/operator/cms/halaman';

export default function CmsPages({ pages, filters, statuses }: Props) {
    const { values, set, reset, isActive } = useFilters(
        { q: filters.q || undefined, status: filters.status || undefined },
        { only: ['pages', 'filters'] },
    );
    const loading = useInertiaLoading();
    const { confirmDelete, dialog } = useConfirmDelete();

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Website', href: BASE },
                { title: 'Halaman', href: BASE },
            ]}
        >
            <Head title="Halaman website" />
            <PageHeader
                title="Halaman website"
                description="Profil, visi misi, sambutan, struktur organisasi, fasilitas, kontak, dan halaman tambahan."
                actions={
                    <Button asChild>
                        <Link href={`${BASE}/tambah`}>
                            <Plus />
                            Tambah halaman
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
                    <ListSkeleton rows={6} />
                ) : pages.data.length === 0 ? (
                    <EmptyState
                        icon={FileText}
                        title="Halaman tidak ditemukan"
                        description="Coba ubah kata kunci atau filter."
                    />
                ) : (
                    <ul className="divide-y divide-slate-100">
                        {pages.data.map((page) => (
                            <li
                                key={page.public_id}
                                className="flex items-center gap-3 px-4 py-3 sm:px-5"
                            >
                                <div className="min-w-0 flex-1 space-y-0.5">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <Link
                                            href={`${BASE}/${page.public_id}/ubah`}
                                            className="font-medium break-words text-slate-900 hover:text-brand-700"
                                        >
                                            {page.title}
                                        </Link>
                                        <ContentStatusBadge
                                            status={page.status}
                                        />
                                        {page.is_system && (
                                            <span className="inline-flex items-center gap-1 text-xs text-slate-400">
                                                <Lock className="size-3" />
                                                Halaman sistem
                                            </span>
                                        )}
                                    </div>
                                    <p className="truncate text-xs text-slate-500">
                                        /{page.slug}
                                        {page.show_in_menu && ' · di menu'}
                                        {' · diubah '}
                                        {formatDate(page.updated_at)}
                                    </p>
                                </div>
                                <StatusMenu
                                    url={`${BASE}/${page.public_id}`}
                                    label={page.title}
                                    status={page.status}
                                />
                                <RowActions
                                    label={page.title}
                                    editHref={`${BASE}/${page.public_id}/ubah`}
                                    onDelete={
                                        page.is_system
                                            ? undefined
                                            : () =>
                                                  confirmDelete({
                                                      url: `${BASE}/${page.public_id}`,
                                                      title: `Hapus ${page.title}?`,
                                                      description:
                                                          'Halaman tidak lagi tampil di website.',
                                                  })
                                    }
                                />
                            </li>
                        ))}
                    </ul>
                )}
                <Pagination paginated={pages} only={['pages', 'filters']} />
            </section>
            {dialog}
        </AppLayout>
    );
}
