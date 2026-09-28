import { cn } from '@/lib/utils';
import { STATUS_CELL, STATUS_ORDER } from '@/Modules/Attendance/lib/status';
import type { StatusOption, Summary } from '@/Modules/Attendance/types';

type Props = {
    summary: Summary;
    statuses: StatusOption[];
    /** Satuan hitungan, mis. "hari". */
    unit?: string;
};

/**
 * Persentase kehadiran + jumlah per status.
 */
export function AttendanceSummary({ summary, statuses, unit = 'hari' }: Props) {
    const label = (value: string) =>
        statuses.find((status) => status.value === value)?.label ?? value;

    return (
        <div className="grid gap-4 sm:grid-cols-[auto_1fr] sm:items-center">
            <div>
                <p className="text-xs font-medium text-slate-500">Kehadiran</p>
                <p className="text-3xl font-semibold text-slate-900 tabular-nums">
                    {summary.percentage === null
                        ? '—'
                        : `${summary.percentage.toLocaleString('id-ID')}%`}
                </p>
                <p className="text-xs text-slate-500 tabular-nums">
                    {summary.days} {unit} tercatat
                </p>
            </div>
            <dl className="grid grid-cols-3 gap-2 sm:grid-cols-5">
                {STATUS_ORDER.map((status) => (
                    <div
                        key={status}
                        className={cn(
                            'rounded-xl px-3 py-2',
                            STATUS_CELL[status],
                        )}
                    >
                        <dt className="text-xs font-medium">{label(status)}</dt>
                        <dd className="text-lg font-semibold tabular-nums">
                            {summary.counts[status]}
                        </dd>
                    </div>
                ))}
            </dl>
        </div>
    );
}
