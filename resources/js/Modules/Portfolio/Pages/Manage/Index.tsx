import { Head, Link } from '@inertiajs/react';
import { FolderKanban, Plus } from 'lucide-react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { FilterBar } from '@/Components/shared/FilterBar';
import { PageHeader } from '@/Components/shared/PageHeader';
import { Pagination } from '@/Components/shared/Pagination';
import { CardSkeleton } from '@/Components/shared/Skeletons';
import { Button } from '@/Components/ui/button';
import { useFilters } from '@/hooks/use-filters';
import { useInertiaLoading } from '@/hooks/use-inertia-loading';
import AppLayout from '@/Layouts/AppLayout';
import type { Option } from '@/Modules/Academic/types';
import { SelectFilter } from '@/Modules/People/Components/SelectFilter';
import { PortfolioGrid } from '@/Modules/Portfolio/Components/PortfolioGrid';
import type { PortfolioRow } from '@/Modules/Portfolio/types';
import type { Paginated } from '@/types';

type Props = {
    base: string;
    portfolios: Paginated<PortfolioRow>;
    categories: Option[];
    filters: { search?: string; kategori?: string; status?: string };
};

export default function ManagePortfolios({
    base,
    portfolios,
    categories,
    filters,
}: Props) {
    const { values, set, reset, isActive } = useFilters(
        {
            search: filters.search,
            kategori: filters.kategori,
            status: filters.status,
        },
        { only: ['portfolios', 'filters'] },
    );
    const loading = useInertiaLoading();

    return (
        <AppLayout breadcrumbs={[{ title: 'Portofolio', href: base }]}>
            <Head title="Portofolio" />

            <PageHeader
                title="Portofolio"
                description="Karya, tugas, prestasi, dan dokumentasi siswa. Portofolio terbit dapat dilihat orang tua."
                actions={
                    <Button asChild>
                        <Link href={`${base}/tambah`}>
                            <Plus />
                            Tambah portofolio
                        </Link>
                    </Button>
                }
            />

            <div className="mb-4">
                <FilterBar
                    search={values.search}
                    onSearchChange={(value) => set('search', value, true)}
                    searchPlaceholder="Cari judul atau nama siswa…"
                    isActive={isActive}
                    onReset={reset}
                >
                    <SelectFilter
                        label="Filter kategori"
                        allLabel="Semua kategori"
                        value={values.kategori}
                        options={categories}
                        onChange={(v) => set('kategori', v)}
                    />
                    <SelectFilter
                        label="Filter status"
                        allLabel="Semua status"
                        value={values.status}
                        options={[
                            { value: 'PUBLISHED', label: 'Terbit' },
                            { value: 'DRAFT', label: 'Draf' },
                        ]}
                        onChange={(v) => set('status', v)}
                        className="w-36"
                    />
                </FilterBar>
            </div>

            {loading ? (
                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <CardSkeleton />
                    <CardSkeleton />
                    <CardSkeleton />
                </div>
            ) : portfolios.data.length === 0 ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState
                        icon={FolderKanban}
                        title={
                            isActive
                                ? 'Portofolio tidak ditemukan'
                                : 'Belum ada portofolio'
                        }
                        description={
                            isActive
                                ? 'Coba ubah kata kunci atau filter.'
                                : 'Tambahkan karya atau dokumentasi siswa.'
                        }
                    />
                </div>
            ) : (
                <PortfolioGrid
                    portfolios={portfolios.data}
                    editBase={base}
                    showVisibility
                />
            )}

            {portfolios.meta.last_page > 1 && (
                <div className="mt-4 rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <Pagination
                        paginated={portfolios}
                        only={['portfolios', 'filters']}
                    />
                </div>
            )}
        </AppLayout>
    );
}
