import { Head, Link, usePage } from '@inertiajs/react';
import {
    BookCheck,
    CalendarDays,
    CheckCircle2,
    ClipboardCheck,
    GraduationCap,
    MessagesSquare,
    NotebookPen,
    PartyPopper,
} from 'lucide-react';
import { PageHeader } from '@/Components/shared/PageHeader';
import { StatCard } from '@/Components/shared/StatCard';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import { Button } from '@/Components/ui/button';
import { useChatUnread } from '@/hooks/use-chat-unread';
import AppLayout from '@/Layouts/AppLayout';
import { formatDate, formatNumber } from '@/lib/format';
import { TeacherCheckInCard } from '@/Modules/Attendance/Components/TeacherCheckInCard';
import type { SelfAttendance } from '@/Modules/Attendance/Components/TeacherCheckInCard';
import { TodayConditionsCard } from '@/Modules/ChildCondition/Components/TodayConditionsCard';
import type { ConditionRow } from '@/Modules/ChildCondition/types';
import {
    DashboardPanel,
    PanelEmpty,
} from '@/Modules/Dashboard/Components/DashboardPanel';
import { RecentChatsList } from '@/Modules/Dashboard/Components/RecentChatsList';
import { CountTaskList } from '@/Modules/Dashboard/Components/TaskList';
import type { ChatItem, TaskItem } from '@/Modules/Dashboard/types';

type Props = {
    hasTeacherProfile: boolean;
    selfAttendance: SelfAttendance;
    conditions: { today: ConditionRow[]; newCount: number };
    schedule: {
        public_id: string;
        start: string;
        end: string;
        subject: string;
        classroom: string;
        room: string | null;
        recorded: boolean;
    }[];
    attendancePending: TaskItem[];
    studentsCount: number;
    gradesIncomplete: {
        label: string;
        components: number;
        weight: number;
        missing: number;
        url: string;
    }[];
    reportTasks: {
        unconfirmed: { label: string; url: string }[];
        to_review: number;
        revision: number;
        url: string;
    } | null;
    recentChats: ChatItem[];
    recentActivities: {
        public_id: string;
        title: string;
        date: string;
        status: string;
    }[];
};

function gradeHint(row: Props['gradesIncomplete'][number]): string {
    if (row.components === 0) {
        return 'Belum ada komponen nilai';
    }

    if (row.weight !== 100) {
        return `Total bobot ${row.weight}% (harus 100%)`;
    }

    return `${formatNumber(row.missing)} nilai belum diisi`;
}

export default function TeacherDashboard(props: Props) {
    const {
        hasTeacherProfile,
        selfAttendance,
        conditions,
        schedule,
        attendancePending,
        studentsCount,
        gradesIncomplete,
        reportTasks,
        recentChats,
        recentActivities,
    } = props;
    const { auth } = usePage().props;
    const chatUnread = useChatUnread();
    const reportTodo =
        (reportTasks?.unconfirmed.length ?? 0) +
        (reportTasks?.to_review ?? 0) +
        (reportTasks?.revision ?? 0);

    return (
        <AppLayout
            breadcrumbs={[{ title: 'Dashboard', href: '/guru/dashboard' }]}
        >
            <Head title="Dashboard Guru" />
            <PageHeader
                title="Dashboard Guru"
                description={`${formatDate(new Date(), 'EEEE, d MMMM yyyy')} · Selamat datang, ${auth.user?.name ?? ''}`}
            />

            {!hasTeacherProfile && (
                <p className="rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    Akun Anda belum tertaut ke data guru aktif. Hubungi operator
                    agar jadwal dan kelas Anda tampil di sini.
                </p>
            )}

            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    label="Jadwal hari ini"
                    value={formatNumber(schedule.length)}
                    icon={CalendarDays}
                />
                <StatCard
                    label="Presensi belum diisi"
                    value={formatNumber(attendancePending.length)}
                    icon={ClipboardCheck}
                />
                <StatCard
                    label="Siswa diajar"
                    value={formatNumber(studentsCount)}
                    icon={GraduationCap}
                />
                <StatCard
                    label="Chat belum dibaca"
                    value={formatNumber(chatUnread)}
                    icon={MessagesSquare}
                    hint={
                        conditions.newCount > 0
                            ? `${conditions.newCount} kondisi anak baru`
                            : undefined
                    }
                />
            </div>

            <TeacherCheckInCard data={selfAttendance} />

            <div className="grid gap-4 lg:grid-cols-3">
                <DashboardPanel
                    title="Jadwal hari ini"
                    icon={CalendarDays}
                    href="/guru/akademik/jadwal"
                    className="lg:col-span-2"
                >
                    {schedule.length === 0 ? (
                        <PanelEmpty>
                            Tidak ada jadwal mengajar hari ini.
                        </PanelEmpty>
                    ) : (
                        <ol className="divide-y divide-slate-100">
                            {schedule.map((item) => (
                                <li
                                    key={item.public_id}
                                    className="flex items-center gap-4 py-2.5 first:pt-0 last:pb-0"
                                >
                                    <span className="w-24 shrink-0 text-sm font-semibold text-slate-900 tabular-nums">
                                        {item.start}–{item.end}
                                    </span>
                                    <span className="min-w-0 flex-1">
                                        <span className="block truncate text-sm font-medium text-slate-800">
                                            {item.subject}
                                        </span>
                                        <span className="block text-xs text-slate-500">
                                            {item.classroom}
                                            {item.room && ` · ${item.room}`}
                                        </span>
                                    </span>
                                    <StatusBadge
                                        variant={
                                            item.recorded
                                                ? 'success'
                                                : 'neutral'
                                        }
                                        dot
                                    >
                                        {item.recorded
                                            ? 'Presensi terisi'
                                            : 'Belum presensi'}
                                    </StatusBadge>
                                </li>
                            ))}
                        </ol>
                    )}
                </DashboardPanel>

                <DashboardPanel
                    title="Presensi belum diisi"
                    icon={ClipboardCheck}
                    href="/guru/presensi"
                >
                    {attendancePending.length === 0 ? (
                        <p className="flex items-center gap-2 text-sm text-emerald-700">
                            <CheckCircle2 className="size-4" />
                            Semua presensi hari ini sudah diisi.
                        </p>
                    ) : (
                        <ul className="space-y-2">
                            {attendancePending.map((item) => (
                                <li
                                    key={item.url}
                                    className="flex items-center gap-3"
                                >
                                    <span className="min-w-0 flex-1">
                                        <span className="block truncate text-sm font-medium text-slate-800">
                                            {item.label}
                                        </span>
                                        <span className="text-xs text-slate-500">
                                            {item.hint}
                                        </span>
                                    </span>
                                    <Button size="sm" asChild>
                                        <Link href={item.url}>Isi</Link>
                                    </Button>
                                </li>
                            ))}
                        </ul>
                    )}
                </DashboardPanel>
            </div>

            <TodayConditionsCard items={conditions.today} />

            <div className="grid gap-4 lg:grid-cols-2">
                <DashboardPanel
                    title="Nilai belum lengkap"
                    description="Semester aktif"
                    icon={NotebookPen}
                    href="/guru/akademik/nilai"
                >
                    {gradesIncomplete.length === 0 ? (
                        <p className="flex items-center gap-2 text-sm text-emerald-700">
                            <CheckCircle2 className="size-4" />
                            Semua nilai mapel Anda sudah lengkap.
                        </p>
                    ) : (
                        <CountTaskList
                            tasks={gradesIncomplete.map((row) => ({
                                label: row.label,
                                hint: gradeHint(row),
                                count: row.missing,
                                href: row.url,
                            }))}
                        />
                    )}
                </DashboardPanel>

                <DashboardPanel
                    title="Raport"
                    icon={BookCheck}
                    href="/guru/raport"
                >
                    {!reportTasks || reportTodo === 0 ? (
                        <PanelEmpty>
                            Tidak ada tugas raport saat ini.
                        </PanelEmpty>
                    ) : (
                        <CountTaskList
                            tasks={[
                                ...reportTasks.unconfirmed.map((item) => ({
                                    label: `Konfirmasi nilai ${item.label}`,
                                    hint: 'Nilai mapel untuk raport',
                                    count: 1,
                                    href: item.url,
                                })),
                                ...(reportTasks.to_review > 0
                                    ? [
                                          {
                                              label: 'Raport menunggu review wali kelas',
                                              count: reportTasks.to_review,
                                              href: reportTasks.url,
                                          },
                                      ]
                                    : []),
                                ...(reportTasks.revision > 0
                                    ? [
                                          {
                                              label: 'Raport perlu revisi',
                                              count: reportTasks.revision,
                                              href: reportTasks.url,
                                              tone: 'danger' as const,
                                          },
                                      ]
                                    : []),
                            ]}
                        />
                    )}
                </DashboardPanel>

                <DashboardPanel
                    title="Chat terbaru"
                    icon={MessagesSquare}
                    href="/guru/chat"
                >
                    <RecentChatsList items={recentChats} />
                </DashboardPanel>

                <DashboardPanel
                    title="Kegiatan terbaru"
                    icon={PartyPopper}
                    href="/guru/kegiatan"
                >
                    {recentActivities.length === 0 ? (
                        <PanelEmpty>Belum ada kegiatan.</PanelEmpty>
                    ) : (
                        <ul className="divide-y divide-slate-100">
                            {recentActivities.map((activity) => (
                                <li
                                    key={activity.public_id}
                                    className="flex items-center justify-between gap-3 py-2 text-sm first:pt-0 last:pb-0"
                                >
                                    <span className="min-w-0">
                                        <span className="block truncate font-medium text-slate-800">
                                            {activity.title}
                                        </span>
                                        <span className="text-xs text-slate-500">
                                            {formatDate(
                                                activity.date,
                                                'EEEE, d MMM',
                                            )}
                                        </span>
                                    </span>
                                    <StatusBadge
                                        variant={
                                            activity.status === 'Terbit'
                                                ? 'success'
                                                : 'neutral'
                                        }
                                    >
                                        {activity.status}
                                    </StatusBadge>
                                </li>
                            ))}
                        </ul>
                    )}
                </DashboardPanel>
            </div>
        </AppLayout>
    );
}
