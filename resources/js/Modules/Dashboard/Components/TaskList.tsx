import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

export type CountTask = {
    label: string;
    count: number | null;
    href: string;
    hint?: string;
    /** Angka > 0 ditandai sebagai perlu tindakan. */
    tone?: 'warning' | 'danger' | 'neutral';
};

const TONES = {
    warning: 'bg-amber-100 text-amber-800',
    danger: 'bg-rose-100 text-rose-700',
    neutral: 'bg-slate-100 text-slate-700',
};

/**
 * Daftar antrean kerja dengan angka. Baris dengan count null (tanpa izin) disembunyikan.
 */
export function CountTaskList({
    tasks,
    empty,
}: {
    tasks: CountTask[];
    empty?: ReactNode;
}) {
    const visible = tasks.filter((task) => task.count !== null);

    if (visible.length === 0) {
        return <>{empty}</>;
    }

    return (
        <ul className="-mx-2 divide-y divide-slate-100">
            {visible.map((task) => (
                <li key={task.label}>
                    <Link
                        href={task.href}
                        className="flex items-center gap-3 rounded-lg px-2 py-2.5 transition-colors duration-150 hover:bg-slate-50"
                    >
                        <span className="min-w-0 flex-1">
                            <span className="block text-sm font-medium text-slate-800">
                                {task.label}
                            </span>
                            {task.hint && (
                                <span className="block text-xs text-slate-500">
                                    {task.hint}
                                </span>
                            )}
                        </span>
                        <span
                            className={cn(
                                'min-w-8 rounded-full px-2 py-0.5 text-center text-sm font-semibold tabular-nums',
                                (task.count ?? 0) > 0
                                    ? TONES[task.tone ?? 'warning']
                                    : 'bg-emerald-50 text-emerald-700',
                            )}
                        >
                            {task.count}
                        </span>
                        <ChevronRight className="size-4 shrink-0 text-slate-300" />
                    </Link>
                </li>
            ))}
        </ul>
    );
}
