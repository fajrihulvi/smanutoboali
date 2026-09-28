import { Head } from '@inertiajs/react';
import { PageHeader } from '@/Components/shared/PageHeader';
import AppLayout from '@/Layouts/AppLayout';
import { AttendanceCalendar } from '@/Modules/Attendance/Components/AttendanceCalendar';
import { AttendanceDayList } from '@/Modules/Attendance/Components/AttendanceDayList';
import { AttendanceSummary } from '@/Modules/Attendance/Components/AttendanceSummary';
import { MonthPicker } from '@/Modules/Attendance/Components/MonthPicker';
import type {
    CalendarData,
    StatusOption,
    StudentRef,
} from '@/Modules/Attendance/types';

type Props = {
    base: string;
    student: StudentRef;
    month: string;
    calendar: CalendarData;
    statuses: StatusOption[];
};

export default function StudentCalendar({
    base,
    student,
    month,
    calendar,
    statuses,
}: Props) {
    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Presensi', href: base },
                { title: student.name, href: '#' },
            ]}
        >
            <Head title={`Kehadiran ${student.name}`} />

            <PageHeader
                title={`Kehadiran ${student.name}`}
                description={`NIS ${student.nis}`}
                actions={<MonthPicker month={month} />}
            />

            <div className="grid gap-6 lg:grid-cols-5">
                <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-3">
                    <AttendanceCalendar
                        month={month}
                        calendar={calendar}
                        statuses={statuses}
                    />
                </section>
                <div className="space-y-6 lg:col-span-2">
                    <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <AttendanceSummary
                            summary={calendar.summary}
                            statuses={statuses}
                        />
                    </section>
                    <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 className="mb-3 text-sm font-semibold text-slate-900">
                            Tidak hadir penuh
                        </h2>
                        <AttendanceDayList
                            calendar={calendar}
                            statuses={statuses}
                        />
                    </section>
                </div>
            </div>
        </AppLayout>
    );
}
