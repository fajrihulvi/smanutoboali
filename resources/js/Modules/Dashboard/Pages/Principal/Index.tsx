import { Head, Link, usePage } from '@inertiajs/react';
import {
    BookCheck,
    CalendarDays,
    ClipboardCheck,
    GraduationCap,
    History,
    PartyPopper,
    TrendingUp,
    UserRoundPlus,
    Users,
    Wallet,
} from 'lucide-react';
import { PageHeader } from '@/Components/shared/PageHeader';
import { StatCard } from '@/Components/shared/StatCard';
import AppLayout from '@/Layouts/AppLayout';
import {
    formatCompactCurrency,
    formatDate,
    formatNumber,
    formatPercent,
} from '@/lib/format';
import { AttendanceTrendChart } from '@/Modules/Dashboard/Components/AttendanceTrendChart';
import { AuditFeedList } from '@/Modules/Dashboard/Components/AuditFeedList';
import {
    DashboardPanel,
    PanelEmpty,
} from '@/Modules/Dashboard/Components/DashboardPanel';
import { GradeDistributionChart } from '@/Modules/Dashboard/Components/GradeDistributionChart';
import type {
    AttendanceToday,
    AuditItem,
    GradeSummary,
    TrendPoint,
} from '@/Modules/Dashboard/types';

type Props = {
    kpi: {
        students: number;
        teachers: number;
        attendance: AttendanceToday;
        ppdb: {
            period: string;
            applicants: number;
            verified: number;
            accepted: number;
            quota: number;
        } | null;
        finance: { billed: number; paid: number; percentage: number | null };
    };
    attendanceTrend: TrendPoint[];
    grades: GradeSummary | null;
    reportCards: { awaiting: number; published: number; total: number };
    activitiesToday: {
        public_id: string;
        title: string;
        time: string | null;
        location: string | null;
    }[];
    recentActivity: AuditItem[];
};

const BASE = '/kepala-sekolah';

function AbsenceTile({
    label,
    value,
    tone,
}: {
    label: string;
    value: number;
    tone: string;
}) {
    return (
        <div className="rounded-xl bg-slate-50 px-3 py-2.5">
            <p className="flex items-center gap-1.5 text-xs text-slate-500">
                <span className={`size-2 rounded-full ${tone}`} />
                {label}
            </p>
            <p className="mt-0.5 text-xl font-semibold text-slate-900 tabular-nums">
                {formatNumber(value)}
            </p>
        </div>
    );
}

export default function PrincipalDashboard({
    kpi,
    attendanceTrend,
    grades,
    reportCards,
    activitiesToday,
    recentActivity,
}: Props) {
    const { auth } = usePage().props;
    const attendance = kpi.attendance;

    return (
        <AppLayout
            breadcrumbs={[{ title: 'Dashboard', href: `${BASE}/dashboard` }]}
        >
            <Head title="Dashboard Kepala Sekolah" />
            <PageHeader
                title="Dashboard Kepala Sekolah"
                description={`${formatDate(new Date(), 'EEEE, d MMMM yyyy')} · Selamat datang, ${auth.user?.name ?? ''}`}
            />

            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    label="Siswa aktif"
                    value={formatNumber(kpi.students)}
                    icon={GraduationCap}
                />
                <StatCard
                    label="Guru aktif"
                    value={formatNumber(kpi.teachers)}
                    icon={Users}
                />
                <StatCard
                    label="Kehadiran hari ini"
                    value={formatPercent(attendance.percentage)}
                    icon={ClipboardCheck}
                    hint={
                        attendance.recorded === 0
                            ? 'Presensi hari ini belum diisi'
                            : `${formatNumber(attendance.recorded)} tercatat · ${formatNumber(attendance.unrecorded)} belum`
                    }
                />
                <StatCard
                    label="Tagihan terbayar"
                    value={formatPercent(kpi.finance.percentage)}
                    icon={Wallet}
                    hint={`${formatCompactCurrency(kpi.finance.paid)} dari ${formatCompactCurrency(kpi.finance.billed)}`}
                />
            </div>

            <div className="grid gap-4 lg:grid-cols-3">
                <DashboardPanel
                    title="Tren kehadiran siswa"
                    description="30 hari terakhir, hari yang ada presensinya"
                    icon={TrendingUp}
                    href={`${BASE}/presensi`}
                    className="lg:col-span-2"
                >
                    <AttendanceTrendChart data={attendanceTrend} />
                </DashboardPanel>

                <div className="grid min-w-0 content-start gap-4">
                    <DashboardPanel
                        title="Tidak hadir hari ini"
                        icon={ClipboardCheck}
                    >
                        <div className="grid grid-cols-3 gap-2">
                            <AbsenceTile
                                label="Sakit"
                                value={attendance.counts.SAKIT}
                                tone="bg-sky-500"
                            />
                            <AbsenceTile
                                label="Izin"
                                value={attendance.counts.IZIN}
                                tone="bg-amber-500"
                            />
                            <AbsenceTile
                                label="Alpa"
                                value={attendance.counts.ALPA}
                                tone="bg-rose-500"
                            />
                        </div>
                        {attendance.counts.TERLAMBAT > 0 && (
                            <p className="mt-2 text-xs text-slate-500">
                                {formatNumber(attendance.counts.TERLAMBAT)}{' '}
                                siswa terlambat.
                            </p>
                        )}
                    </DashboardPanel>

                    <DashboardPanel
                        title="PPDB"
                        icon={UserRoundPlus}
                        href={`${BASE}/ppdb/dashboard`}
                    >
                        {kpi.ppdb ? (
                            <>
                                <p className="text-xs text-slate-500">
                                    {kpi.ppdb.period}
                                </p>
                                <dl className="mt-2 grid grid-cols-3 gap-2 text-center">
                                    {(
                                        [
                                            ['Pendaftar', kpi.ppdb.applicants],
                                            [
                                                'Terverifikasi',
                                                kpi.ppdb.verified,
                                            ],
                                            ['Diterima', kpi.ppdb.accepted],
                                        ] as const
                                    ).map(([label, value]) => (
                                        <div
                                            key={label}
                                            className="rounded-xl bg-slate-50 px-2 py-2"
                                        >
                                            <dt className="text-[11px] text-slate-500">
                                                {label}
                                            </dt>
                                            <dd className="text-lg font-semibold text-slate-900 tabular-nums">
                                                {formatNumber(value)}
                                            </dd>
                                        </div>
                                    ))}
                                </dl>
                                <p className="mt-2 text-xs text-slate-500">
                                    Kuota {formatNumber(kpi.ppdb.quota)} siswa
                                </p>
                            </>
                        ) : (
                            <PanelEmpty>
                                Tidak ada periode PPDB aktif.
                            </PanelEmpty>
                        )}
                    </DashboardPanel>
                </div>
            </div>

            <div className="grid gap-4 lg:grid-cols-3">
                <DashboardPanel
                    title="Distribusi nilai"
                    description={
                        grades?.semester
                            ? `Nilai akhir semester ${grades.semester}`
                            : undefined
                    }
                    icon={BookCheck}
                    href={`${BASE}/nilai`}
                    className="lg:col-span-2"
                >
                    <GradeDistributionChart summary={grades} />
                </DashboardPanel>

                <DashboardPanel
                    title="Raport"
                    icon={BookCheck}
                    href={`${BASE}/raport`}
                >
                    <Link
                        href={`${BASE}/raport`}
                        className="block rounded-xl bg-amber-50 p-4 transition-colors duration-150 hover:bg-amber-100"
                    >
                        <p className="text-xs font-medium text-amber-800">
                            Menunggu persetujuan Anda
                        </p>
                        <p className="text-3xl font-semibold text-amber-900 tabular-nums">
                            {formatNumber(reportCards.awaiting)}
                        </p>
                    </Link>
                    <p className="mt-3 text-xs text-slate-500">
                        {formatNumber(reportCards.published)} dari{' '}
                        {formatNumber(reportCards.total)} raport semester ini
                        sudah terbit.
                    </p>
                </DashboardPanel>
            </div>

            <div className="grid gap-4 lg:grid-cols-3">
                <DashboardPanel
                    title="Kegiatan hari ini"
                    icon={PartyPopper}
                    href={`${BASE}/kegiatan`}
                >
                    {activitiesToday.length === 0 ? (
                        <PanelEmpty>Tidak ada kegiatan hari ini.</PanelEmpty>
                    ) : (
                        <ul className="space-y-3">
                            {activitiesToday.map((activity) => (
                                <li
                                    key={activity.public_id}
                                    className="flex gap-3"
                                >
                                    <CalendarDays className="mt-0.5 size-4 shrink-0 text-brand-500" />
                                    <span className="min-w-0 text-sm">
                                        <span className="block font-medium text-slate-900">
                                            {activity.title}
                                        </span>
                                        <span className="text-xs text-slate-500">
                                            {[activity.time, activity.location]
                                                .filter(Boolean)
                                                .join(' · ') ||
                                                'Sepanjang hari'}
                                        </span>
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}
                </DashboardPanel>

                <DashboardPanel
                    title="Aktivitas sistem terbaru"
                    icon={History}
                    href={`${BASE}/audit-log`}
                    className="lg:col-span-2"
                >
                    <AuditFeedList items={recentActivity} />
                </DashboardPanel>
            </div>
        </AppLayout>
    );
}
