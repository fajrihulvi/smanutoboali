import { Head, router } from '@inertiajs/react';
import { Baby, FolderKanban } from 'lucide-react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { Pagination } from '@/Components/shared/Pagination';
import { StudentSwitcher } from '@/Components/shared/StudentSwitcher';
import type { SwitcherChild } from '@/Components/shared/StudentSwitcher';
import ParentLayout from '@/Layouts/ParentLayout';
import { cn } from '@/lib/utils';
import type { Option } from '@/Modules/Academic/types';
import { PortfolioGrid } from '@/Modules/Portfolio/Components/PortfolioGrid';
import type { PortfolioRow } from '@/Modules/Portfolio/types';
import type { Paginated } from '@/types';

type Props = {
    childOptions: SwitcherChild[];
    activeChild: string | null;
    child: { public_id: string; name: string } | null;
    portfolios: Paginated<PortfolioRow> | null;
    categories: Option[];
    category: string | null;
};

export default function GuardianPortfolios({
    childOptions,
    activeChild,
    child,
    portfolios,
    categories,
    category,
}: Props) {
    const choose = (value: string | null) =>
        router.get(
            '/orang-tua/portofolio',
            { anak: activeChild ?? undefined, kategori: value ?? undefined },
            { preserveScroll: true },
        );

    return (
        <ParentLayout
            header={
                <StudentSwitcher items={childOptions} active={activeChild} />
            }
        >
            <Head title="Portofolio" />

            <div>
                <h1 className="text-xl font-semibold text-slate-900">
                    Portofolio
                </h1>
                <p className="text-sm text-slate-500">
                    {child
                        ? `Karya dan dokumentasi ${child.name}`
                        : 'Karya dan dokumentasi anak.'}
                </p>
            </div>

            {child && (
                <div
                    className="-mx-4 flex gap-1.5 overflow-x-auto px-4 pb-1 sm:mx-0 sm:px-0"
                    role="tablist"
                    aria-label="Kategori"
                >
                    {[{ value: null, label: 'Semua' }, ...categories].map(
                        (option) => {
                            const active =
                                (option.value ?? null) === (category ?? null);

                            return (
                                <button
                                    key={option.value ?? 'all'}
                                    type="button"
                                    role="tab"
                                    aria-selected={active}
                                    onClick={() => choose(option.value)}
                                    className={cn(
                                        'shrink-0 rounded-full border px-3.5 py-1.5 text-sm font-medium transition-colors duration-150',
                                        active
                                            ? 'border-brand-600 bg-brand-600 text-white'
                                            : 'border-slate-200 bg-white text-slate-600',
                                    )}
                                >
                                    {option.label}
                                </button>
                            );
                        },
                    )}
                </div>
            )}

            {!child ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState icon={Baby} title="Belum ada anak terhubung" />
                </div>
            ) : !portfolios || portfolios.data.length === 0 ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState
                        icon={FolderKanban}
                        title="Belum ada portofolio"
                        description="Portofolio tampil setelah guru menerbitkannya."
                    />
                </div>
            ) : (
                <>
                    <PortfolioGrid portfolios={portfolios.data} />
                    {portfolios.meta.last_page > 1 && (
                        <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                            <Pagination
                                paginated={portfolios}
                                only={['portfolios']}
                            />
                        </div>
                    )}
                </>
            )}
        </ParentLayout>
    );
}
