import type { LucideIcon } from 'lucide-react';
import { ArrowDownRight, ArrowRight, ArrowUpRight } from 'lucide-react';
import { cn } from '@/lib/utils';

type Trend = {
    value: string;
    direction: 'up' | 'down' | 'flat';
    /** Naik = baik (default) atau naik = buruk (misal jumlah alpa). */
    positive?: 'up' | 'down';
};

type Props = {
    label: string;
    value: string | number;
    icon: LucideIcon;
    trend?: Trend;
    hint?: string;
};

const trendIcon = {
    up: ArrowUpRight,
    down: ArrowDownRight,
    flat: ArrowRight,
};

export function StatCard({ label, value, icon: Icon, trend, hint }: Props) {
    const TrendIcon = trend ? trendIcon[trend.direction] : null;
    const isGood =
        trend && trend.direction !== 'flat'
            ? trend.direction === (trend.positive ?? 'up')
            : null;

    return (
        <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div className="flex items-start justify-between gap-4">
                <p className="text-sm font-medium text-slate-500">{label}</p>
                <span className="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-600">
                    <Icon className="size-5" />
                </span>
            </div>
            <p className="mt-2 text-3xl font-semibold text-slate-900 tabular-nums">
                {value}
            </p>
            {(trend || hint) && (
                <div className="mt-2 flex items-center gap-1.5 text-xs">
                    {trend && TrendIcon && (
                        <span
                            className={cn(
                                'inline-flex items-center gap-0.5 font-medium',
                                isGood === true && 'text-emerald-600',
                                isGood === false && 'text-rose-600',
                                isGood === null && 'text-slate-500',
                            )}
                        >
                            <TrendIcon className="size-3.5" />
                            {trend.value}
                        </span>
                    )}
                    {hint && <span className="text-slate-500">{hint}</span>}
                </div>
            )}
        </div>
    );
}
