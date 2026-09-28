import { Head, Link } from '@inertiajs/react';
import { CalendarDays, PartyPopper, Pencil, Plus } from 'lucide-react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { FilterBar } from '@/Components/shared/FilterBar';
import { PageHeader } from '@/Components/shared/PageHeader';
import { Pagination } from '@/Components/shared/Pagination';
import { ListSkeleton } from '@/Components/shared/Skeletons';
import { Button } from '@/Components/ui/button';
import { useFilters } from '@/hooks/use-filters';
import { useInertiaLoading } from '@/hooks/use-inertia-loading';
import AppLayout from '@/Layouts/AppLayout';
import type { Option } from '@/Modules/Academic/types';
import { ActivityMeta } from '@/Modules/Activity/Components/ActivityMeta';
import type { ActivityRow } from '@/Modules/Activity/types';
import { SelectFilter } from '@/Modules/People/Components/SelectFilter';
import type { Paginated } from '@/types';

type Props = {
    base: string;
    activities: Paginated<ActivityRow>;
    types: Option[];
    filters: { search?: string; jenis?: string; status?: string };
};

export default function ManageActivities({
    base,
    activities,
    types,
    filters,
}: Props) {
    const { values, set, reset, isActive } = useFilters(
        {
            search: filters.search,
            jenis: filters.jenis,
            status: filters.status,
        },
        { only: ['activities', 'filters'] },
    );
    const loading = useInertiaLoading();

    return (
        <AppLayout breadcrumbs={[{ title: 'Kegiatan', href: base }]}>
            <Head title="Kegiatan" />

            <PageHeader
                title="Kegiatan"
                description="Dokumentasi kegiatan harian, kelas, dan sekolah. Kegiatan terbit tampil di feed orang tua."
                actions={
                    <>
                        <Button variant="outline" asChild>
                            <Link href={`${base}/kalender`}>
                                <CalendarDays />
                                Kalender
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href={`${base}/tambah`}>
                                <Plus />
                                Tambah kegiatan
                            </Link>
                        </Button>
                    </>
                }
            />

            <section className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div className="border-b border-slate-200 p-4">
                    <FilterBar
                        search={values.search}
                        onSearchChange={(value) => set('search', value, true)}
                        searchPlaceholder="Cari judul kegiatan…"
                        isActive={isActive}
                        onReset={reset}
                    >
                        <SelectFilter
                            label="Filter jenis"
                            allLabel="Semua jenis"
                            value={values.jenis}
                            options={types}
                            onChange={(v) => set('jenis', v)}
                            className="w-36"
                        />
                        <SelectFilter
                            label="Filter status"
                            allLabel="Semua status"
                            value={values.status}
                            options={[
                                { value: 'TERBIT', label: 'Terbit' },
                                { value: 'DRAF', label: 'Draf' },
                            ]}
                            onChange={(v) => set('status', v)}
                            className="w-36"
                        />
                    </FilterBar>
                </div>

                {loading ? (
                    <ListSkeleton rows={4} />
                ) : activities.data.length === 0 ? (
                    <EmptyState
                        icon={PartyPopper}
                        title={
                            isActive
                                ? 'Kegiatan tidak ditemukan'
                                : 'Belum ada kegiatan'
                        }
                        description={
                            isActive
                                ? 'Coba ubah kata kunci atau filter.'
                                : 'Tambahkan dokumentasi kegiatan pertama.'
                        }
                    />
                ) : (
                    <ul className="divide-y divide-slate-100">
                        {activities.data.map((activity) => (
                            <li
                                key={activity.public_id}
                                className="flex gap-4 p-4 sm:px-5"
                            >
                                {activity.photos[0] ? (
                                    <img
                                        src={activity.photos[0].url}
                                        alt=""
                                        loading="lazy"
                                        className="size-20 shrink-0 rounded-xl object-cover"
                                    />
                                ) : (
                                    <span className="flex size-20 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-500">
                                        <PartyPopper className="size-7" />
                                    </span>
                                )}
                                <div className="min-w-0 flex-1 space-y-1">
                                    <p className="font-semibold text-slate-900">
                                        {activity.title}
                                    </p>
                                    <ActivityMeta activity={activity} />
                                </div>
                                {activity.can.update && (
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        className="size-8 shrink-0"
                                        asChild
                                    >
                                        <Link
                                            href={`${base}/${activity.public_id}/ubah`}
                                            aria-label={`Ubah ${activity.title}`}
                                        >
                                            <Pencil className="size-4" />
                                        </Link>
                                    </Button>
                                )}
                            </li>
                        ))}
                    </ul>
                )}

                <Pagination
                    paginated={activities}
                    only={['activities', 'filters']}
                />
            </section>
        </AppLayout>
    );
}
