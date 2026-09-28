import { Head, Link } from '@inertiajs/react';
import { CalendarRange, Users } from 'lucide-react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { PageHeader } from '@/Components/shared/PageHeader';
import { Button } from '@/Components/ui/button';
import AppLayout from '@/Layouts/AppLayout';
import { PercentageBar } from '@/Modules/Attendance/Components/PercentageBar';
import { RangePicker } from '@/Modules/Attendance/Components/RangePicker';
import type {
    AttendanceStatusValue,
    StatusOption,
    StudentRef,
    Summary,
} from '@/Modules/Attendance/types';

type Props = {
    classroom: { public_id: string; name: string };
    range: { from: string; to: string };
    students: { student: StudentRef; summary: Summary }[];
    statuses: StatusOption[];
};

const BASE = '/kepala-sekolah/presensi';
const ABSENCES: AttendanceStatusValue[] = [
    'TERLAMBAT',
    'IZIN',
    'SAKIT',
    'ALPA',
];

export default function PrincipalAttendanceClassroom({
    classroom,
    range,
    students,
    statuses,
}: Props) {
    const label = (value: string) =>
        statuses.find((status) => status.value === value)?.label ?? value;
    const sorted = [...students].sort(
        (a, b) => (a.summary.percentage ?? 101) - (b.summary.percentage ?? 101),
    );

    return (
        <AppLayout
            breadcrumbs={[
                {
                    title: 'Monitoring Presensi',
                    href: `${BASE}?dari=${range.from}&sampai=${range.to}`,
                },
                { title: classroom.name, href: '#' },
            ]}
        >
            <Head title={`Presensi ${classroom.name}`} />

            <PageHeader
                title={`Presensi ${classroom.name}`}
                description="Diurutkan dari persentase kehadiran terendah."
                actions={
                    <>
                        <RangePicker from={range.from} to={range.to} />
                        <Button variant="outline" asChild>
                            <Link
                                href={`${BASE}/rombel/${classroom.public_id}/rekap?bulan=${range.to.slice(0, 7)}`}
                            >
                                <CalendarRange />
                                Rekap bulanan
                            </Link>
                        </Button>
                    </>
                }
            />

            {students.length === 0 ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState
                        icon={Users}
                        title="Rombel belum punya siswa aktif"
                    />
                </div>
            ) : (
                <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div className="relative overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-slate-50 text-left text-xs font-semibold text-slate-600">
                                <tr>
                                    <th className="px-4 py-3">Siswa</th>
                                    <th className="px-4 py-3">Kehadiran</th>
                                    {ABSENCES.map((status) => (
                                        <th
                                            key={status}
                                            className="px-3 py-3 text-right"
                                        >
                                            {label(status)}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {sorted.map(({ student, summary }) => (
                                    <tr key={student.public_id}>
                                        <td className="px-4 py-3">
                                            <Link
                                                href={`${BASE}/siswa/${student.public_id}?bulan=${range.to.slice(0, 7)}`}
                                                className="font-medium text-slate-900 hover:text-brand-700"
                                            >
                                                {student.name}
                                            </Link>
                                            <p className="text-xs text-slate-500 tabular-nums">
                                                NIS {student.nis}
                                            </p>
                                        </td>
                                        <td className="px-4 py-3">
                                            <PercentageBar
                                                value={summary.percentage}
                                            />
                                        </td>
                                        {ABSENCES.map((status) => (
                                            <td
                                                key={status}
                                                className="px-3 py-3 text-right tabular-nums"
                                            >
                                                {summary.counts[status]}
                                            </td>
                                        ))}
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
