import { Head, Link } from '@inertiajs/react';
import { CalendarX2 } from 'lucide-react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { MonthPicker } from '@/Components/shared/MonthPicker';
import { PageHeader } from '@/Components/shared/PageHeader';
import AppLayout from '@/Layouts/AppLayout';
import { cn } from '@/lib/utils';
import { AttendanceSummary } from '@/Modules/Attendance/Components/AttendanceSummary';
import {
    STATUS_CELL,
    STATUS_ORDER,
    STATUS_SHORT,
} from '@/Modules/Attendance/lib/status';
import type {
    AttendanceStatusValue,
    StatusOption,
    StudentRef,
    Summary,
} from '@/Modules/Attendance/types';

type Props = {
    base: string;
    classroom: { public_id: string; name: string };
    month: string;
    recap: {
        dates: string[];
        rows: {
            student: StudentRef;
            days: Record<string, AttendanceStatusValue>;
            summary: Summary;
        }[];
        summary: Summary;
    };
    statuses: StatusOption[];
};

export default function ClassroomRecap({
    base,
    classroom,
    month,
    recap,
    statuses,
}: Props) {
    const label = (value: string) =>
        statuses.find((status) => status.value === value)?.label ?? value;

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Presensi', href: base },
                { title: `Rekap ${classroom.name}`, href: '#' },
            ]}
        >
            <Head title={`Rekap Presensi ${classroom.name}`} />

            <PageHeader
                title={`Rekap Presensi ${classroom.name}`}
                description="Status harian per siswa. Klik nama siswa untuk melihat kalender kehadirannya."
                actions={<MonthPicker month={month} />}
            />

            <section className="mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <AttendanceSummary
                    summary={recap.summary}
                    statuses={statuses}
                    unit="hari-siswa"
                />
            </section>

            {recap.dates.length === 0 ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState
                        icon={CalendarX2}
                        title="Belum ada presensi bulan ini"
                    />
                </div>
            ) : (
                <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div className="relative overflow-x-auto">
                        <table className="w-full border-separate border-spacing-0 text-sm">
                            <thead>
                                <tr className="text-xs font-semibold text-slate-600">
                                    <th className="sticky left-0 z-10 border-b border-slate-200 bg-slate-50 px-4 py-2 text-left">
                                        Siswa
                                    </th>
                                    {recap.dates.map((date) => (
                                        <th
                                            key={date}
                                            className="border-b border-slate-200 bg-slate-50 px-1 py-2 text-center tabular-nums"
                                            title={date}
                                        >
                                            {Number(date.slice(8))}
                                        </th>
                                    ))}
                                    {STATUS_ORDER.map((status) => (
                                        <th
                                            key={status}
                                            className="border-b border-slate-200 bg-slate-50 px-2 py-2 text-center"
                                            title={label(status)}
                                        >
                                            {STATUS_SHORT[status]}
                                        </th>
                                    ))}
                                    <th className="border-b border-slate-200 bg-slate-50 px-3 py-2 text-right">
                                        %
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {recap.rows.map((row) => (
                                    <tr key={row.student.public_id}>
                                        <th
                                            scope="row"
                                            className="sticky left-0 z-10 border-b border-slate-100 bg-white px-4 py-2 text-left font-medium whitespace-nowrap"
                                        >
                                            <Link
                                                href={`${base}/siswa/${row.student.public_id}?bulan=${month}`}
                                                className="text-slate-900 hover:text-brand-700"
                                            >
                                                {row.student.name}
                                            </Link>
                                        </th>
                                        {recap.dates.map((date) => {
                                            const status = row.days[date];

                                            return (
                                                <td
                                                    key={date}
                                                    className="border-b border-slate-100 px-1 py-1 text-center"
                                                >
                                                    {status ? (
                                                        <span
                                                            className={cn(
                                                                'inline-flex size-6 items-center justify-center rounded text-xs font-semibold',
                                                                STATUS_CELL[
                                                                    status
                                                                ],
                                                            )}
                                                            title={label(
                                                                status,
                                                            )}
                                                        >
                                                            {
                                                                STATUS_SHORT[
                                                                    status
                                                                ]
                                                            }
                                                        </span>
                                                    ) : (
                                                        <span className="text-slate-300">
                                                            ·
                                                        </span>
                                                    )}
                                                </td>
                                            );
                                        })}
                                        {STATUS_ORDER.map((status) => (
                                            <td
                                                key={status}
                                                className="border-b border-slate-100 px-2 py-2 text-center text-slate-600 tabular-nums"
                                            >
                                                {row.summary.counts[status]}
                                            </td>
                                        ))}
                                        <td className="border-b border-slate-100 px-3 py-2 text-right font-semibold tabular-nums">
                                            {row.summary.percentage === null
                                                ? '—'
                                                : row.summary.percentage.toLocaleString(
                                                      'id-ID',
                                                  )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </section>
            )}
        </AppLayout>
    );
}
