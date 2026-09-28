import { monthGrid } from '@/lib/month';
import { cn } from '@/lib/utils';
import { STATUS_CELL, STATUS_SHORT } from '@/Modules/Attendance/lib/status';
import type { CalendarData, StatusOption } from '@/Modules/Attendance/types';

type Props = {
    month: string;
    calendar: CalendarData;
    statuses: StatusOption[];
    today?: string;
};

const WEEKDAYS = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];

/**
 * Kalender bulanan kehadiran (Senin–Minggu); sel diwarnai status harian.
 */
export function AttendanceCalendar({
    month,
    calendar,
    statuses,
    today,
}: Props) {
    const label = (value: string) =>
        statuses.find((status) => status.value === value)?.label ?? value;

    return (
        <div>
            <div className="grid grid-cols-7 gap-1 text-center text-xs font-medium text-slate-500">
                {WEEKDAYS.map((day) => (
                    <div key={day} className="py-1">
                        {day}
                    </div>
                ))}
            </div>
            <div className="grid grid-cols-7 gap-1">
                {monthGrid(month)
                    .flat()
                    .map((date, index) => {
                        if (date === null) {
                            return (
                                <div
                                    key={`empty-${index}`}
                                    aria-hidden="true"
                                />
                            );
                        }

                        const day = calendar.days[date];
                        const dayNumber = Number(date.slice(8));

                        return (
                            <div
                                key={date}
                                title={day ? label(day.status) : undefined}
                                className={cn(
                                    'flex aspect-square flex-col items-center justify-center rounded-lg text-sm tabular-nums',
                                    day
                                        ? STATUS_CELL[day.status]
                                        : 'text-slate-400',
                                    date === today && 'ring-2 ring-brand-500',
                                )}
                            >
                                <span className="font-medium">{dayNumber}</span>
                                {day && (
                                    <span
                                        className="text-[10px] leading-none font-semibold"
                                        aria-label={label(day.status)}
                                    >
                                        {STATUS_SHORT[day.status]}
                                    </span>
                                )}
                            </div>
                        );
                    })}
            </div>
        </div>
    );
}
