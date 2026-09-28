import { StatusBadge } from '@/Components/shared/StatusBadge';
import { formatDate } from '@/lib/format';
import { STATUS_VARIANT } from '@/Modules/Attendance/lib/status';
import type { CalendarData, StatusOption } from '@/Modules/Attendance/types';

type Props = {
    calendar: CalendarData;
    statuses: StatusOption[];
};

/**
 * Hari yang bukan HADIR penuh, dengan rincian mapel & catatan guru.
 */
export function AttendanceDayList({ calendar, statuses }: Props) {
    const label = (value: string) =>
        statuses.find((status) => status.value === value)?.label ?? value;
    const days = Object.entries(calendar.days)
        .filter(([, day]) => day.status !== 'HADIR')
        .sort(([a], [b]) => b.localeCompare(a));

    if (days.length === 0) {
        return (
            <p className="text-sm text-slate-500">
                Hadir penuh di semua hari tercatat bulan ini.
            </p>
        );
    }

    return (
        <ul className="space-y-3">
            {days.map(([date, day]) => (
                <li key={date} className="text-sm">
                    <div className="flex items-center justify-between gap-2">
                        <span className="font-medium text-slate-900">
                            {formatDate(date, 'EEEE, d MMM')}
                        </span>
                        <StatusBadge variant={STATUS_VARIANT[day.status]}>
                            {label(day.status)}
                        </StatusBadge>
                    </div>
                    {day.entries
                        .filter(
                            (entry) => entry.status !== 'HADIR' || entry.note,
                        )
                        .map((entry, index) => (
                            <p
                                key={index}
                                className="mt-0.5 text-xs text-slate-500"
                            >
                                {entry.subject && `${entry.subject}: `}
                                {label(entry.status)}
                                {entry.note && ` — ${entry.note}`}
                            </p>
                        ))}
                </li>
            ))}
        </ul>
    );
}
