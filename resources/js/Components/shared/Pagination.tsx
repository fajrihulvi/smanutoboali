import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import type { ReactNode } from 'react';
import { formatNumber } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types';

type Props = {
    paginated: Pick<Paginated<unknown>, 'links' | 'meta'>;
    /** Prop Inertia yang dimuat ulang saat pindah halaman. */
    only?: string[];
};

function PageLink({
    href,
    active = false,
    disabled = false,
    only,
    label,
    children,
}: {
    href: string | null;
    active?: boolean;
    disabled?: boolean;
    only?: string[];
    label?: string;
    children: ReactNode;
}) {
    const className = cn(
        'inline-flex h-9 min-w-9 items-center justify-center rounded-lg px-3 text-sm font-medium tabular-nums transition-colors duration-150',
        active
            ? 'bg-brand-600 text-white'
            : 'text-slate-600 hover:bg-brand-50 hover:text-brand-700',
        disabled && 'pointer-events-none opacity-40',
    );

    if (!href || disabled) {
        return (
            <span className={className} aria-disabled="true" aria-label={label}>
                {children}
            </span>
        );
    }

    return (
        <Link
            href={href}
            only={only}
            preserveScroll
            preserveState
            className={className}
            aria-current={active ? 'page' : undefined}
            aria-label={label}
        >
            {children}
        </Link>
    );
}

export function Pagination({ paginated, only }: Props) {
    const { meta, links } = paginated;

    if (meta.total === 0) {
        return null;
    }

    // meta.links = [prev, ...angka/"...", next]
    const pages = meta.links.slice(1, -1);

    return (
        <nav
            className="flex flex-col items-center justify-between gap-3 border-t border-slate-200 px-4 py-3 sm:flex-row"
            aria-label="Paginasi"
        >
            <p className="text-xs text-slate-500">
                Menampilkan{' '}
                <span className="font-medium text-slate-700 tabular-nums">
                    {formatNumber(meta.from)}–{formatNumber(meta.to)}
                </span>{' '}
                dari{' '}
                <span className="font-medium text-slate-700 tabular-nums">
                    {formatNumber(meta.total)}
                </span>
            </p>

            <div className="flex items-center gap-1">
                <PageLink
                    href={links.prev}
                    disabled={!links.prev}
                    only={only}
                    label="Halaman sebelumnya"
                >
                    <ChevronLeft className="size-4" />
                </PageLink>

                <span className="px-2 text-sm text-slate-600 tabular-nums sm:hidden">
                    {meta.current_page} / {meta.last_page}
                </span>

                <div className="hidden items-center gap-1 sm:flex">
                    {pages.map((link, index) =>
                        link.url === null ? (
                            <span
                                key={`gap-${index}`}
                                className="px-2 text-slate-400"
                            >
                                …
                            </span>
                        ) : (
                            <PageLink
                                key={link.label}
                                href={link.url}
                                active={link.active}
                                only={only}
                            >
                                {link.label}
                            </PageLink>
                        ),
                    )}
                </div>

                <PageLink
                    href={links.next}
                    disabled={!links.next}
                    only={only}
                    label="Halaman berikutnya"
                >
                    <ChevronRight className="size-4" />
                </PageLink>
            </div>
        </nav>
    );
}
