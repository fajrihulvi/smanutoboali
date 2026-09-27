import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';
import type { StatusVariant } from '@/types';

const variants: Record<StatusVariant, string> = {
    success: 'bg-emerald-100 text-emerald-700',
    warning: 'bg-amber-100 text-amber-700',
    danger: 'bg-rose-100 text-rose-700',
    info: 'bg-sky-100 text-sky-700',
    neutral: 'bg-slate-100 text-slate-700',
    brand: 'bg-brand-100 text-brand-700',
};

const dots: Record<StatusVariant, string> = {
    success: 'bg-emerald-500',
    warning: 'bg-amber-500',
    danger: 'bg-rose-500',
    info: 'bg-sky-500',
    neutral: 'bg-slate-400',
    brand: 'bg-brand-500',
};

type Props = {
    variant?: StatusVariant;
    dot?: boolean;
    className?: string;
    children: ReactNode;
};

export function StatusBadge({
    variant = 'neutral',
    dot = false,
    className,
    children,
}: Props) {
    return (
        <span
            className={cn(
                'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap',
                variants[variant],
                className,
            )}
        >
            {dot && (
                <span className={cn('size-1.5 rounded-full', dots[variant])} />
            )}
            {children}
        </span>
    );
}
