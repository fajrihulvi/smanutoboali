import { Head } from '@inertiajs/react';
import { FolderKanban } from 'lucide-react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { PageHeader } from '@/Components/shared/PageHeader';
import { Pagination } from '@/Components/shared/Pagination';
import { useFilters } from '@/hooks/use-filters';
import AppLayout from '@/Layouts/AppLayout';
import type { Option } from '@/Modules/Academic/types';
import { SelectFilter } from '@/Modules/People/Components/SelectFilter';
import { PortfolioGrid } from '@/Modules/Portfolio/Components/PortfolioGrid';
import type { PortfolioRow } from '@/Modules/Portfolio/types';
import type { Paginated } from '@/types';

type Props = {
    portfolios: Paginated<PortfolioRow>;
    categories: Option[];
    classrooms: Option[];
    filters: { kategori?: string; rombel?: string };
};

export default function PrincipalPortfolios({
    portfolios,
    categories,
    classrooms,
    filters,
}: Props) {
    const { values, set } = useFilters(
        { kategori: filters.kategori, rombel: filters.rombel },
        { only: ['portfolios', 'filters'] },
    );

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Portofolio', href: '/kepala-sekolah/portofolio' },
            ]}
        >
            <Head title="Portofolio" />

            <PageHeader
                title="Portofolio"
                description="Portofolio siswa yang sudah diterbitkan guru."
            />

            <div className="mb-4 flex flex-wrap gap-2">
                <SelectFilter
                    label="Filter kategori"
                    allLabel="Semua kategori"
                    value={values.kategori}
                    options={categories}
                    onChange={(v) => set('kategori', v)}
                />
                <SelectFilter
                    label="Filter rombel"
                    allLabel="Semua rombel"
                    value={values.rombel}
                    options={classrooms}
                    onChange={(v) => set('rombel', v)}
                    className="w-40"
                />
            </div>

            {portfolios.data.length === 0 ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState
                        icon={FolderKanban}
                        title="Belum ada portofolio terbit"
                    />
                </div>
            ) : (
                <PortfolioGrid portfolios={portfolios.data} />
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
