import { Head } from '@inertiajs/react';
import { Baby, BookOpen, CalendarX2, ChevronDown, Shapes } from 'lucide-react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { StudentSwitcher } from '@/Components/shared/StudentSwitcher';
import type { SwitcherChild } from '@/Components/shared/StudentSwitcher';
import ParentLayout from '@/Layouts/ParentLayout';
import { MaterialItem } from '@/Modules/Academic/Components/MaterialItem';
import { WeeklySchedule } from '@/Modules/Academic/Components/WeeklySchedule';
import type {
    DayOption,
    MaterialRow,
    ScheduleEntry,
} from '@/Modules/Academic/types';

type SubjectEntry = {
    assignment: string;
    subject: { code: string; name: string };
    teacher: string;
    materials: MaterialRow[];
};

type Props = {
    childOptions: SwitcherChild[];
    activeChild: string | null;
    child: { public_id: string; name: string } | null;
    classroom: { name: string } | null;
    schedules: ScheduleEntry[];
    subjects: SubjectEntry[];
    days: DayOption[];
};

export default function GuardianAcademicIndex({
    childOptions,
    activeChild,
    child,
    classroom,
    schedules,
    subjects,
    days,
}: Props) {
    const today = new Date().getDay();

    return (
        <ParentLayout
            header={
                <StudentSwitcher items={childOptions} active={activeChild} />
            }
        >
            <Head title="Akademik" />

            <div>
                <h1 className="text-xl font-semibold text-slate-900">
                    Akademik
                </h1>
                <p className="text-sm text-slate-500">
                    {child && classroom
                        ? `${child.name} · Rombel ${classroom.name}`
                        : 'Jadwal dan mata pelajaran anak.'}
                </p>
            </div>

            {!child ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState
                        icon={Baby}
                        title="Belum ada anak terhubung"
                        description="Hubungi operator sekolah untuk menautkan data anak ke akun Anda."
                    />
                </div>
            ) : !classroom ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState
                        icon={Shapes}
                        title="Belum masuk rombel"
                        description={`${child.name} belum terdaftar di rombel tahun ajaran aktif.`}
                    />
                </div>
            ) : (
                <>
                    <section aria-labelledby="jadwal">
                        <h2
                            id="jadwal"
                            className="mb-3 text-base font-semibold text-slate-900"
                        >
                            Jadwal pelajaran
                        </h2>
                        {schedules.length === 0 ? (
                            <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                                <EmptyState
                                    icon={CalendarX2}
                                    title="Jadwal belum tersedia"
                                />
                            </div>
                        ) : (
                            <WeeklySchedule
                                days={days}
                                schedules={schedules}
                                detail="teacher"
                                today={today}
                            />
                        )}
                    </section>

                    <section aria-labelledby="mapel">
                        <h2
                            id="mapel"
                            className="mb-3 text-base font-semibold text-slate-900"
                        >
                            Mata pelajaran & materi
                        </h2>
                        {subjects.length === 0 ? (
                            <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                                <EmptyState
                                    icon={BookOpen}
                                    title="Mata pelajaran belum diatur"
                                />
                            </div>
                        ) : (
                            <ul className="space-y-2">
                                {subjects.map((entry) => (
                                    <li key={entry.assignment}>
                                        <details className="group rounded-2xl border border-slate-200 bg-white shadow-sm">
                                            <summary className="flex cursor-pointer list-none items-center gap-3 p-4 [&::-webkit-details-marker]:hidden">
                                                <span className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-xs font-semibold text-brand-700">
                                                    {entry.subject.code.slice(
                                                        0,
                                                        4,
                                                    )}
                                                </span>
                                                <span className="min-w-0 flex-1">
                                                    <span className="block truncate font-medium text-slate-900">
                                                        {entry.subject.name}
                                                    </span>
                                                    <span className="block truncate text-xs text-slate-500">
                                                        {entry.teacher} ·{' '}
                                                        {entry.materials.length}{' '}
                                                        materi
                                                    </span>
                                                </span>
                                                <ChevronDown className="size-5 text-slate-400 transition-transform duration-150 group-open:rotate-180" />
                                            </summary>
                                            <div className="border-t border-slate-100 p-4">
                                                {entry.materials.length ===
                                                0 ? (
                                                    <p className="text-sm text-slate-500">
                                                        Belum ada materi.
                                                    </p>
                                                ) : (
                                                    <ul className="space-y-4">
                                                        {entry.materials.map(
                                                            (material) => (
                                                                <li
                                                                    key={
                                                                        material.public_id
                                                                    }
                                                                >
                                                                    <MaterialItem
                                                                        material={
                                                                            material
                                                                        }
                                                                    />
                                                                </li>
                                                            ),
                                                        )}
                                                    </ul>
                                                )}
                                            </div>
                                        </details>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>
                </>
            )}
        </ParentLayout>
    );
}
