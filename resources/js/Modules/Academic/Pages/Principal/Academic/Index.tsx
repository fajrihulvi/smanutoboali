import { Head, router } from '@inertiajs/react';
import { CalendarX2, Shapes } from 'lucide-react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { PageHeader } from '@/Components/shared/PageHeader';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import AppLayout from '@/Layouts/AppLayout';
import { cn } from '@/lib/utils';
import { WeeklySchedule } from '@/Modules/Academic/Components/WeeklySchedule';
import { formatDuration } from '@/Modules/Academic/lib/schedule';
import type { DayOption, ScheduleEntry } from '@/Modules/Academic/types';

type ClassroomSummary = {
    public_id: string;
    name: string;
    grade_label: string;
    homeroom_teacher: string | null;
    minutes_per_week: number;
    subjects_count: number;
    unscheduled_subjects: string[];
};

type Props = {
    year: string | null;
    classrooms: ClassroomSummary[];
    classroom: { public_id: string; name: string } | null;
    schedules: ScheduleEntry[];
    days: DayOption[];
};

const URL = '/kepala-sekolah/akademik';

export default function PrincipalAcademicIndex({
    year,
    classrooms,
    classroom,
    schedules,
    days,
}: Props) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Monitoring Akademik', href: URL }]}>
            <Head title="Monitoring Akademik" />

            <PageHeader
                title="Monitoring Akademik"
                description={
                    year
                        ? `Ringkasan jadwal pelajaran TA ${year}.`
                        : 'Belum ada tahun ajaran aktif.'
                }
            />

            {classrooms.length === 0 ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState
                        icon={Shapes}
                        title="Belum ada rombel"
                        description="Rombel tahun ajaran aktif belum dibuat."
                    />
                </div>
            ) : (
                <div className="space-y-6">
                    <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div className="relative overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-slate-50 text-left text-xs font-semibold text-slate-600">
                                    <tr>
                                        <th className="px-4 py-3">Rombel</th>
                                        <th className="px-4 py-3">
                                            Wali kelas
                                        </th>
                                        <th className="px-4 py-3 text-right">
                                            Jam/minggu
                                        </th>
                                        <th className="px-4 py-3">
                                            Mapel belum terjadwal
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {classrooms.map((row) => {
                                        const selected =
                                            row.public_id ===
                                            classroom?.public_id;

                                        return (
                                            <tr
                                                key={row.public_id}
                                                className={cn(
                                                    selected &&
                                                        'bg-brand-50/50',
                                                )}
                                            >
                                                <td className="px-4 py-3">
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            router.get(
                                                                URL,
                                                                {
                                                                    rombel: row.public_id,
                                                                },
                                                                {
                                                                    preserveScroll: true,
                                                                    only: [
                                                                        'classroom',
                                                                        'schedules',
                                                                    ],
                                                                },
                                                            )
                                                        }
                                                        className="font-semibold text-brand-700 hover:underline"
                                                        aria-pressed={selected}
                                                    >
                                                        {row.name}
                                                    </button>
                                                    <p className="text-xs text-slate-500">
                                                        Kelas {row.grade_label}
                                                    </p>
                                                </td>
                                                <td className="px-4 py-3 whitespace-nowrap">
                                                    {row.homeroom_teacher ?? (
                                                        <span className="text-slate-400">
                                                            —
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="px-4 py-3 text-right whitespace-nowrap tabular-nums">
                                                    {formatDuration(
                                                        row.minutes_per_week,
                                                    )}
                                                </td>
                                                <td className="px-4 py-3">
                                                    {row.unscheduled_subjects
                                                        .length === 0 ? (
                                                        <StatusBadge variant="success">
                                                            {row.subjects_count >
                                                            0
                                                                ? 'Semua terjadwal'
                                                                : 'Belum ada mapel'}
                                                        </StatusBadge>
                                                    ) : (
                                                        <div className="flex flex-wrap gap-1">
                                                            {row.unscheduled_subjects.map(
                                                                (name) => (
                                                                    <StatusBadge
                                                                        key={
                                                                            name
                                                                        }
                                                                        variant="warning"
                                                                    >
                                                                        {name}
                                                                    </StatusBadge>
                                                                ),
                                                            )}
                                                        </div>
                                                    )}
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    </section>

                    {classroom && (
                        <section>
                            <h2 className="mb-3 text-base font-semibold text-slate-900">
                                Jadwal {classroom.name}
                            </h2>
                            {schedules.length === 0 ? (
                                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                                    <EmptyState
                                        icon={CalendarX2}
                                        title="Jadwal belum disusun"
                                    />
                                </div>
                            ) : (
                                <WeeklySchedule
                                    days={days}
                                    schedules={schedules}
                                    detail="teacher"
                                />
                            )}
                        </section>
                    )}
                </div>
            )}
        </AppLayout>
    );
}
