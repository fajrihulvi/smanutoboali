import { Head, Link } from '@inertiajs/react';
import { ChevronRight, Shapes } from 'lucide-react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { PageHeader } from '@/Components/shared/PageHeader';
import AppLayout from '@/Layouts/AppLayout';
import { AttendanceSummary } from '@/Modules/Attendance/Components/AttendanceSummary';
import { PercentageBar } from '@/Modules/Attendance/Components/PercentageBar';
import { RangePicker } from '@/Modules/Attendance/Components/RangePicker';
import type {
    AttendanceStatusValue,
    StatusOption,
    Summary,
} from '@/Modules/Attendance/types';

type Props = {
    range: { from: string; to: string };
    classrooms: {
        public_id: string;
        name: string;
        students: number;
        summary: Summary;
    }[];
    statuses: StatusOption[];
};

const BASE = '/kepala-sekolah/presensi';
const ABSENCES: AttendanceStatusValue[] = [
    'TERLAMBAT',
    'IZIN',
    'SAKIT',
    'ALPA',
];

export default function PrincipalAttendanceIndex({
    range,
    classrooms,
    statuses,
}: Props) {
    const label = (value: string) =>
        statuses.find((status) => status.value === value)?.label ?? value;

    const total: Summary = classrooms.reduce<Summary>(
        (acc, row) => {
            const counts = { ...acc.counts };
            (Object.keys(counts) as AttendanceStatusValue[]).forEach((key) => {
                counts[key] += row.summary.counts[key];
            });
            const days = acc.days + row.summary.days;
            const present = counts.HADIR + counts.TERLAMBAT;

            return {
                counts,
                days,
                percentage:
                    days > 0 ? Math.round((present / days) * 1000) / 10 : null,
            };
        },
        {
            counts: { HADIR: 0, SAKIT: 0, IZIN: 0, ALPA: 0, TERLAMBAT: 0 },
            days: 0,
            percentage: null,
        },
    );

    const query = `dari=${range.from}&sampai=${range.to}`;

    return (
        <AppLayout breadcrumbs={[{ title: 'Monitoring Presensi', href: BASE }]}>
            <Head title="Monitoring Presensi" />

            <PageHeader
                title="Monitoring Presensi"
                description="Kehadiran siswa per rombel pada periode terpilih. Pilih rombel untuk melihat per siswa."
                actions={<RangePicker from={range.from} to={range.to} />}
            />

            <section className="mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <AttendanceSummary
                    summary={total}
                    statuses={statuses}
                    unit="hari-siswa"
                />
            </section>

            {classrooms.length === 0 ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState
                        icon={Shapes}
                        title="Belum ada rombel tahun ajaran aktif"
                    />
                </div>
            ) : (
                <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div className="relative overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-slate-50 text-left text-xs font-semibold text-slate-600">
                                <tr>
                                    <th className="px-4 py-3">Rombel</th>
                                    <th className="px-4 py-3">Kehadiran</th>
                                    {ABSENCES.map((status) => (
                                        <th
                                            key={status}
                                            className="px-3 py-3 text-right"
                                        >
                                            {label(status)}
                                        </th>
                                    ))}
                                    <th className="px-4 py-3">
                                        <span className="sr-only">Detail</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {classrooms.map((row) => (
                                    <tr key={row.public_id}>
                                        <td className="px-4 py-3">
                                            <p className="font-semibold text-slate-900">
                                                {row.name}
                                            </p>
                                            <p className="text-xs text-slate-500">
                                                {row.students} siswa
                                            </p>
                                        </td>
                                        <td className="px-4 py-3">
                                            <PercentageBar
                                                value={row.summary.percentage}
                                            />
                                        </td>
                                        {ABSENCES.map((status) => (
                                            <td
                                                key={status}
                                                className="px-3 py-3 text-right tabular-nums"
                                            >
                                                {row.summary.counts[status]}
                                            </td>
                                        ))}
                                        <td className="px-4 py-3 text-right">
                                            <Link
                                                href={`${BASE}/rombel/${row.public_id}?${query}`}
                                                className="inline-flex items-center gap-1 text-sm font-medium text-brand-700 hover:underline"
                                            >
                                                Siswa
                                                <ChevronRight className="size-4" />
                                            </Link>
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
