import { cn } from '@/lib/utils';

/**
 * Batang persentase kehadiran: hijau ≥ 90, kuning ≥ 75, merah di bawahnya.
 */
export function PercentageBar({ value }: { value: number | null }) {
    if (value === null) {
        return <span className="text-slate-400">—</span>;
    }

    return (
        <div className="flex items-center gap-2">
            <div
                className="h-2 w-24 overflow-hidden rounded-full bg-slate-100"
                aria-hidden="true"
            >
                <div
                    className={cn(
                        'h-full rounded-full',
                        value >= 90
                            ? 'bg-emerald-500'
                            : value >= 75
                              ? 'bg-amber-500'
                              : 'bg-rose-500',
                    )}
                    style={{ width: `${Math.min(100, value)}%` }}
                />
            </div>
            <span className="text-sm font-semibold text-slate-900 tabular-nums">
                {value.toLocaleString('id-ID')}%
            </span>
        </div>
    );
}
