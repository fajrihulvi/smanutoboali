import { Head } from '@inertiajs/react';
import { HeartPulse } from 'lucide-react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { FilterBar } from '@/Components/shared/FilterBar';
import { PageHeader } from '@/Components/shared/PageHeader';
import { Pagination } from '@/Components/shared/Pagination';
import { ListSkeleton } from '@/Components/shared/Skeletons';
import { useFilters } from '@/hooks/use-filters';
import { useInertiaLoading } from '@/hooks/use-inertia-loading';
import AppLayout from '@/Layouts/AppLayout';
import { cn } from '@/lib/utils';
import { ConditionListItem } from '@/Modules/ChildCondition/Components/ConditionListItem';
import type { ConditionRow } from '@/Modules/ChildCondition/types';
import type { Paginated } from '@/types';

type Props = {
    base: string;
    /** null = akun guru tanpa profil guru aktif. */
    conditions: Paginated<ConditionRow> | null;
    filters: { tab: 'aktif' | 'selesai'; q: string };
    newCount: number;
    readOnly: boolean;
};

const TABS = [
    { value: 'aktif', label: 'Aktif' },
    { value: 'selesai', label: 'Selesai' },
] as const;

/**
 * Inbox kondisi anak (guru) / monitoring hanya-baca (kepala sekolah).
 */
export default function ConditionStaffIndex({
    base,
    conditions,
    filters,
    newCount,
    readOnly,
}: Props) {
    const { values, set } = useFilters(
        {
            tab: filters.tab === 'selesai' ? 'selesai' : undefined,
            q: filters.q || undefined,
        },
        { only: ['conditions', 'filters', 'newCount'] },
    );
    const loading = useInertiaLoading();
    const tab = values.tab ?? 'aktif';

    return (
        <AppLayout breadcrumbs={[{ title: 'Kondisi Anak', href: base }]}>
            <Head title="Kondisi Anak" />
            <PageHeader
                title="Kondisi Anak"
                description={
                    readOnly
                        ? 'Laporan kondisi anak dari orang tua (hanya-baca). Data sensitif.'
                        : 'Laporan dari orang tua untuk Anda dan siswa perwalian Anda. Laporan baru tampil paling atas.'
                }
            />

            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div
                    role="tablist"
                    className="inline-flex rounded-lg bg-slate-100 p-1"
                >
                    {TABS.map((item) => (
                        <button
                            key={item.value}
                            type="button"
                            role="tab"
                            aria-selected={tab === item.value}
                            onClick={() =>
                                set(
                                    'tab',
                                    item.value === 'aktif'
                                        ? undefined
                                        : item.value,
                                )
                            }
                            className={cn(
                                'flex items-center gap-1.5 rounded-md px-4 py-1.5 text-sm font-medium transition-colors duration-150',
                                tab === item.value
                                    ? 'bg-white text-slate-900 shadow-sm'
                                    : 'text-slate-500 hover:text-slate-700',
                            )}
                        >
                            {item.label}
                            {item.value === 'aktif' && newCount > 0 && (
                                <span className="rounded-full bg-amber-500 px-1.5 text-xs text-white">
                                    {newCount} baru
                                </span>
                            )}
                        </button>
                    ))}
                </div>
                <FilterBar
                    search={values.q ?? ''}
                    onSearchChange={(value) => set('q', value, true)}
                    searchPlaceholder="Cari nama siswa…"
                />
            </div>

            <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                {conditions === null ? (
                    <EmptyState
                        icon={HeartPulse}
                        title="Profil guru belum tertaut"
                        description="Minta operator menautkan akun Anda ke data guru."
                    />
                ) : loading && conditions.data.length === 0 ? (
                    <ListSkeleton />
                ) : conditions.data.length === 0 ? (
                    <EmptyState
                        icon={HeartPulse}
                        title={
                            values.q
                                ? 'Tidak ditemukan'
                                : tab === 'selesai'
                                  ? 'Belum ada laporan selesai'
                                  : 'Tidak ada laporan aktif'
                        }
                        description={
                            values.q
                                ? 'Coba nama siswa lain.'
                                : 'Laporan kondisi anak dari orang tua akan tampil di sini.'
                        }
                    />
                ) : (
                    <>
                        <ul className="divide-y divide-slate-100">
                            {conditions.data.map((condition) => (
                                <li key={condition.public_id}>
                                    <ConditionListItem
                                        href={`${base}/${condition.public_id}`}
                                        condition={condition}
                                        staff
                                    />
                                </li>
                            ))}
                        </ul>
                        <div className="border-t border-slate-100 px-4 py-3">
                            <Pagination
                                paginated={conditions}
                                only={['conditions', 'filters', 'newCount']}
                            />
                        </div>
                    </>
                )}
            </div>
        </AppLayout>
    );
}
