import { StatusBadge } from '@/Components/shared/StatusBadge';
import {
    REPORT_FLOW,
    REPORT_STATUS_SHORT,
    REPORT_STATUS_VARIANT,
} from '@/Modules/ReportCard/Components/ReportStatusBadge';
import type { StatusCounts as Counts } from '@/Modules/ReportCard/types';

/** Chip jumlah raport per status (hanya status yang > 0). */
export function StatusCounts({ counts }: { counts: Counts }) {
    const shown = REPORT_FLOW.filter((status) => counts[status] > 0);

    if (shown.length === 0) {
        return <span className="text-xs text-slate-400">Belum ada raport</span>;
    }

    return (
        <div className="flex flex-wrap gap-1">
            {shown.map((status) => (
                <StatusBadge
                    key={status}
                    variant={REPORT_STATUS_VARIANT[status]}
                >
                    {REPORT_STATUS_SHORT[status]} {counts[status]}
                </StatusBadge>
            ))}
        </div>
    );
}
