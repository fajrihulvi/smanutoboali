import type { LucideIcon } from 'lucide-react';
import { Inbox } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type Props = {
    title: string;
    description?: string;
    icon?: LucideIcon;
    action?: ReactNode;
    className?: string;
};

/**
 * Empty state dengan ilustrasi sederhana satu warna biru (§6.4).
 */
export function EmptyState({
    title,
    description,
    icon: Icon = Inbox,
    action,
    className,
}: Props) {
    return (
        <div
            className={cn(
                'flex flex-col items-center justify-center px-6 py-14 text-center',
                className,
            )}
        >
            <div className="relative mb-5 flex size-24 items-center justify-center">
                <span className="absolute inset-0 rounded-full bg-brand-50" />
                <span className="absolute inset-3 rounded-full bg-brand-100/70" />
                <Icon
                    className="relative size-9 text-brand-500"
                    strokeWidth={1.5}
                />
            </div>
            <h3 className="text-base font-semibold text-slate-900">{title}</h3>
            {description && (
                <p className="mt-1 max-w-sm text-sm text-slate-500">
                    {description}
                </p>
            )}
            {action && <div className="mt-5">{action}</div>}
        </div>
    );
}
