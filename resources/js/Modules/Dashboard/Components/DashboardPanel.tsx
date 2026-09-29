import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type Props = {
    title: string;
    icon: LucideIcon;
    /** Tautan "Lihat semua" di pojok kanan. */
    href?: string;
    hrefLabel?: string;
    description?: string;
    className?: string;
    children: ReactNode;
};

/**
 * Kartu widget dashboard: judul + ikon, tautan opsional, isi bebas.
 */
export function DashboardPanel({
    title,
    icon: Icon,
    href,
    hrefLabel = 'Lihat semua',
    description,
    className,
    children,
}: Props) {
    return (
        <section
            className={cn(
                'min-w-0 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm',
                className,
            )}
        >
            <header className="mb-4 flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <h2 className="flex items-center gap-2 text-sm font-semibold text-slate-900">
                        <Icon className="size-4 shrink-0 text-brand-600" />
                        {title}
                    </h2>
                    {description && (
                        <p className="mt-0.5 text-xs text-slate-500">
                            {description}
                        </p>
                    )}
                </div>
                {href && (
                    <Link
                        href={href}
                        className="flex shrink-0 items-center text-xs font-medium text-brand-600 hover:text-brand-700"
                    >
                        {hrefLabel}
                        <ChevronRight className="size-3.5" />
                    </Link>
                )}
            </header>
            {children}
        </section>
    );
}

export function PanelEmpty({ children }: { children: ReactNode }) {
    return <p className="text-sm text-slate-500">{children}</p>;
}
