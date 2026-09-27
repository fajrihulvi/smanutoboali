import { Head } from '@inertiajs/react';
import {
    CalendarCheck,
    CalendarX2,
    Clock,
    MapPin,
    UserRoundX,
} from 'lucide-react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { PageHeader } from '@/Components/shared/PageHeader';
import AppLayout from '@/Layouts/AppLayout';
import { WeeklySchedule } from '@/Modules/Academic/Components/WeeklySchedule';
import { formatDuration, totalMinutes } from '@/Modules/Academic/lib/schedule';
import type { DayOption, ScheduleEntry } from '@/Modules/Academic/types';

type Props = {
    hasTeacherProfile: boolean;
    today: DayOption | null;
    schedules: ScheduleEntry[];
    days: DayOption[];
};

export default function TeacherSchedulesIndex({
    hasTeacherProfile,
    today,
    schedules,
    days,
}: Props) {
    const todays = today
        ? schedules.filter((entry) => entry.day === today.value)
        : [];

    return (
        <AppLayout
            breadcrumbs={[{ title: 'Jadwal', href: '/guru/akademik/jadwal' }]}
        >
            <Head title="Jadwal Saya" />

            <PageHeader
                title="Jadwal Saya"
                description={`Jadwal mengajar tahun ajaran aktif · ${formatDuration(totalMinutes(schedules))} per minggu`}
            />

            {!hasTeacherProfile ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState
                        icon={UserRoundX}
                        title="Akun belum terhubung ke data guru"
                        description="Hubungi operator sekolah untuk menautkan akun Anda ke data guru."
                    />
                </div>
            ) : schedules.length === 0 ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState
                        icon={CalendarX2}
                        title="Belum ada jadwal"
                        description="Operator belum menyusun jadwal untuk mapel yang Anda ampu."
                    />
                </div>
            ) : (
                <div className="space-y-6">
                    <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 className="flex items-center gap-2 text-base font-semibold text-slate-900">
                            <CalendarCheck className="size-5 text-brand-600" />
                            Hari ini{today ? ` · ${today.label}` : ''}
                        </h2>
                        {todays.length === 0 ? (
                            <p className="mt-3 text-sm text-slate-500">
                                Tidak ada jadwal mengajar hari ini.
                            </p>
                        ) : (
                            <ol className="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                {todays.map((entry) => (
                                    <li
                                        key={entry.public_id}
                                        className="rounded-xl border border-brand-100 bg-brand-50/50 p-4"
                                    >
                                        <p className="flex items-center gap-1.5 text-sm font-medium text-brand-700 tabular-nums">
                                            <Clock className="size-4" />
                                            {entry.start_time}–{entry.end_time}
                                        </p>
                                        <p className="mt-1 font-semibold text-slate-900">
                                            {entry.subject.name}
                                        </p>
                                        <p className="text-sm text-slate-600">
                                            {entry.classroom.name}
                                        </p>
                                        {entry.room && (
                                            <p className="mt-1 flex items-center gap-1 text-xs text-slate-500">
                                                <MapPin className="size-3" />
                                                {entry.room}
                                            </p>
                                        )}
                                    </li>
                                ))}
                            </ol>
                        )}
                    </section>

                    <section>
                        <h2 className="mb-3 text-base font-semibold text-slate-900">
                            Mingguan
                        </h2>
                        <WeeklySchedule
                            days={days}
                            schedules={schedules}
                            detail="classroom"
                            today={today?.value}
                        />
                    </section>
                </div>
            )}
        </AppLayout>
    );
}
