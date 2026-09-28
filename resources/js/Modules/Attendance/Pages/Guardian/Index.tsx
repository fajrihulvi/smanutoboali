import { Head } from '@inertiajs/react';
import { Baby, CalendarCheck } from 'lucide-react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import { StudentSwitcher } from '@/Components/shared/StudentSwitcher';
import type { SwitcherChild } from '@/Components/shared/StudentSwitcher';
import ParentLayout from '@/Layouts/ParentLayout';
import { formatDate } from '@/lib/format';
import { AttendanceCalendar } from '@/Modules/Attendance/Components/AttendanceCalendar';
import { AttendanceDayList } from '@/Modules/Attendance/Components/AttendanceDayList';
import { AttendanceSummary } from '@/Modules/Attendance/Components/AttendanceSummary';
import { MonthPicker } from '@/Modules/Attendance/Components/MonthPicker';
import { STATUS_VARIANT } from '@/Modules/Attendance/lib/status';
import type {
    AttendanceStatusValue,
    CalendarData,
    StatusOption,
} from '@/Modules/Attendance/types';

type Props = {
    childOptions: SwitcherChild[];
    activeChild: string | null;
    child: { public_id: string; name: string } | null;
    today: { date: string; status: AttendanceStatusValue | null };
    month: string;
    calendar: CalendarData | null;
    statuses: StatusOption[];
};

export default function GuardianAttendanceIndex({
    childOptions,
    activeChild,
    child,
    today,
    month,
    calendar,
    statuses,
}: Props) {
    const label = (value: string) =>
        statuses.find((status) => status.value === value)?.label ?? value;

    return (
        <ParentLayout
            header={
                <StudentSwitcher items={childOptions} active={activeChild} />
            }
        >
            <Head title="Presensi" />

            <div>
                <h1 className="text-xl font-semibold text-slate-900">
                    Presensi
                </h1>
                <p className="text-sm text-slate-500">
                    {child ? child.name : 'Kehadiran anak di sekolah.'}
                </p>
            </div>

            {!child || !calendar ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState
                        icon={Baby}
                        title="Belum ada anak terhubung"
                        description="Hubungi operator sekolah untuk menautkan data anak ke akun Anda."
                    />
                </div>
            ) : (
                <>
                    <section className="flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <span className="flex size-12 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                            <CalendarCheck className="size-6" />
                        </span>
                        <div className="min-w-0 flex-1">
                            <p className="text-xs font-medium text-slate-500">
                                Hari ini ·{' '}
                                {formatDate(today.date, 'EEEE, d MMM')}
                            </p>
                            {today.status ? (
                                <StatusBadge
                                    variant={STATUS_VARIANT[today.status]}
                                    className="mt-1"
                                >
                                    {label(today.status)}
                                </StatusBadge>
                            ) : (
                                <p className="text-sm font-medium text-slate-700">
                                    Belum dipresensi
                                </p>
                            )}
                        </div>
                    </section>

                    <section className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div className="mb-4 flex justify-center">
                            <MonthPicker
                                month={month}
                                query={{ anak: activeChild ?? undefined }}
                            />
                        </div>
                        <AttendanceCalendar
                            month={month}
                            calendar={calendar}
                            statuses={statuses}
                            today={today.date}
                        />
                    </section>

                    <section className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <AttendanceSummary
                            summary={calendar.summary}
                            statuses={statuses}
                        />
                    </section>

                    <section className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <h2 className="mb-3 text-sm font-semibold text-slate-900">
                            Tidak hadir penuh
                        </h2>
                        <AttendanceDayList
                            calendar={calendar}
                            statuses={statuses}
                        />
                    </section>
                </>
            )}
        </ParentLayout>
    );
}
