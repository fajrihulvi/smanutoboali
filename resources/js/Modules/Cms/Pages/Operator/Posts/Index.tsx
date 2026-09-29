import { Head, Link } from '@inertiajs/react';
import { CalendarDays, Newspaper, Plus, Star } from 'lucide-react';
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
import type { PostRow, PostTypeInfo } from '@/Modules/Cms/types';
import { SelectFilter } from '@/Modules/People/Components/SelectFilter';
import type { Paginated } from '@/types';

type Props = {
    type: PostTypeInfo;
    base: string;
    posts: Paginated<PostRow>;
    filters: { status?: string; q?: string; category?: string };
    statuses: Option[];
    categories: Option[];
};

export default function CmsPosts({
    type,
    base,
    posts,
    filters,
    statuses,
    categories,
}: Props) {
    const { values, set, reset, isActive } = useFilters(
        {
            q: filters.q || undefined,
            status: filters.status || undefined,
            category: filters.category || undefined,
        },
        { only: ['posts', 'filters'] },
    );
    const loading = useInertiaLoading();
    const { confirmDelete, dialog } = useConfirmDelete();
    const isAgenda = type.value === 'AGENDA';
    const noun = type.label.toLowerCase();
    const Icon = isAgenda ? CalendarDays : Newspaper;

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Website', href: '/operator/cms/halaman' },
                { title: type.label, href: base },
            ]}
        >
            <Head title={type.label} />
            <PageHeader
                title={type.label}
                description={
                    isAgenda
                        ? 'Kegiatan sekolah yang akan datang, lengkap dengan waktu dan lokasi.'
                        : 'Berita dan kabar kegiatan sekolah untuk website.'
                }
                actions={
                    <Button asChild>
                        <Link href={`${base}/tambah`}>
                            <Plus />
                            Tambah {noun}
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
                        {categories.length > 0 && (
                            <SelectFilter
                                label="Filter kategori"
                                allLabel="Semua kategori"
                                value={values.category}
                                options={categories}
                                onChange={(v) => set('category', v)}
                                className="w-44"
                            />
                        )}
                    </FilterBar>
                </div>

                {loading ? (
                    <ListSkeleton rows={5} />
                ) : posts.data.length === 0 ? (
                    <EmptyState
                        icon={Icon}
                        title={
                            isActive
                                ? `${type.label} tidak ditemukan`
                                : `Belum ada ${noun}`
                        }
                        description={
                            isActive
                                ? 'Coba ubah kata kunci atau filter.'
                                : `Tulis ${noun} pertama untuk website sekolah.`
                        }
                    />
                ) : (
                    <ul className="divide-y divide-slate-100">
                        {posts.data.map((post) => (
                            <li
                                key={post.public_id}
                                className="flex items-center gap-3 px-4 py-3 sm:px-5"
                            >
                                {post.image ? (
                                    <img
                                        src={post.image.url}
                                        alt=""
                                        className="hidden size-14 shrink-0 rounded-lg object-cover sm:block"
                                    />
                                ) : (
                                    <span className="hidden size-14 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-400 sm:flex">
                                        <Icon className="size-5" />
                                    </span>
                                )}
                                <div className="min-w-0 flex-1 space-y-0.5">
                                    <div className="flex flex-wrap items-center gap-2">
                                        {post.is_featured && (
                                            <Star
                                                className="size-4 fill-amber-400 text-amber-400"
                                                aria-label="Unggulan"
                                            />
                                        )}
                                        <Link
                                            href={`${base}/${post.public_id}/ubah`}
                                            className="font-medium break-words text-slate-900 hover:text-brand-700"
                                        >
                                            {post.title}
                                        </Link>
                                        <ContentStatusBadge
                                            status={post.status}
                                        />
                                    </div>
                                    <p className="text-xs text-slate-500">
                                        {[
                                            isAgenda && post.event_starts_at
                                                ? formatDate(
                                                      post.event_starts_at,
                                                      'EEEE, d MMM yyyy · HH.mm',
                                                  )
                                                : null,
                                            isAgenda
                                                ? post.event_location
                                                : null,
                                            post.category?.name,
                                            post.published_at
                                                ? `${post.status.scheduled ? 'tayang' : 'terbit'} ${formatDate(post.published_at, 'd MMM yyyy, HH.mm')}`
                                                : `diubah ${formatDate(post.updated_at)}`,
                                            post.author,
                                        ]
                                            .filter(Boolean)
                                            .join(' · ')}
                                    </p>
                                </div>
                                <StatusMenu
                                    url={`${base}/${post.public_id}`}
                                    label={post.title}
                                    status={post.status}
                                />
                                <RowActions
                                    label={post.title}
                                    editHref={`${base}/${post.public_id}/ubah`}
                                    onDelete={() =>
                                        confirmDelete({
                                            url: `${base}/${post.public_id}`,
                                            title: `Hapus ${noun} ini?`,
                                            description: `"${post.title}" tidak lagi tampil di website.`,
                                        })
                                    }
                                />
                            </li>
                        ))}
                    </ul>
                )}
                <Pagination paginated={posts} only={['posts', 'filters']} />
            </section>
            {dialog}
        </AppLayout>
    );
}
