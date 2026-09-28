import { Head } from '@inertiajs/react';
import { Users } from 'lucide-react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { PageHeader } from '@/Components/shared/PageHeader';
import AppLayout from '@/Layouts/AppLayout';
import type { Option } from '@/Modules/Academic/types';
import { PercentageBar } from '@/Modules/Attendance/Components/PercentageBar';
import { RangePicker } from '@/Modules/Attendance/Components/RangePicker';

type Props = {
    base: string;
    range: { from: string; to: string };
    rows: {
        teacher: { public_id: string; name: string; nip: string | null };
        summary: {
            counts: Record<string, number>;
            days: number;
            percentage: number | null;
        };
    }[];
    statuses: Option[];
};

export default function TeacherRecap({ base, range, rows, statuses }: Props) {
    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Presensi Guru', href: base },
                { title: 'Rekap', href: '#' },
            ]}
        >
            <Head title="Rekap Presensi Guru" />

            <PageHeader
                title="Rekap Presensi Guru"
                description="Jumlah hari per status. Hari tanpa catatan tidak dihitung."
                actions={<RangePicker from={range.from} to={range.to} />}
            />

            {rows.length === 0 ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState icon={Users} title="Belum ada guru" />
                </div>
            ) : (
                <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div className="relative overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-slate-50 text-left text-xs font-semibold text-slate-600">
                                <tr>
                                    <th className="px-4 py-3">Guru</th>
                                    <th className="px-4 py-3">Kehadiran</th>
                                    {statuses.map((status) => (
                                        <th
                                            key={status.value}
                                            className="px-3 py-3 text-right whitespace-nowrap"
                                        >
                                            {status.label}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {rows.map(({ teacher, summary }) => (
                                    <tr key={teacher.public_id}>
                                        <td className="px-4 py-3">
                                            <p className="font-medium whitespace-nowrap text-slate-900">
                                                {teacher.name}
                                            </p>
                                            <p className="text-xs text-slate-500 tabular-nums">
                                                {summary.days} hari tercatat
                                            </p>
                                        </td>
                                        <td className="px-4 py-3">
                                            <PercentageBar
                                                value={summary.percentage}
                                            />
                                        </td>
                                        {statuses.map((status) => (
                                            <td
                                                key={status.value}
                                                className="px-3 py-3 text-right tabular-nums"
                                            >
                                                {summary.counts[status.value] ??
                                                    0}
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
