import { usePage } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import { Construction } from 'lucide-react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { PageHeader } from '@/Components/shared/PageHeader';
import { StatCard } from '@/Components/shared/StatCard';

export type PlaceholderStat = {
    label: string;
    value: string;
    icon: LucideIcon;
    hint?: string;
};

type Props = {
    title: string;
    stats: PlaceholderStat[];
};

/**
 * Kerangka dashboard sementara (S04); data nyata di S30.
 */
export function DashboardPlaceholder({ title, stats }: Props) {
    const { auth } = usePage().props;
    const firstName = auth.user?.name.split(' ')[0] ?? '';

    return (
        <>
            <PageHeader
                title={title}
                description={`Selamat datang, ${firstName}.`}
            />

            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                {stats.map((stat) => (
                    <StatCard key={stat.label} {...stat} />
                ))}
            </div>

            <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <EmptyState
                    icon={Construction}
                    title="Dashboard sedang disiapkan"
                    description="Ringkasan data akan tampil di sini setelah modul master data dan pembelajaran selesai."
                />
            </div>
        </>
    );
}
