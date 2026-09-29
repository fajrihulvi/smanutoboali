import { Head, usePage } from '@inertiajs/react';
import {
    ClipboardList,
    GraduationCap,
    History,
    ListChecks,
    UserRound,
    Users,
    Wallet,
} from 'lucide-react';
import { PageHeader } from '@/Components/shared/PageHeader';
import { StatCard } from '@/Components/shared/StatCard';
import AppLayout from '@/Layouts/AppLayout';
import { formatCompactCurrency, formatDate, formatNumber } from '@/lib/format';
import { AuditFeedList } from '@/Modules/Dashboard/Components/AuditFeedList';
import {
    DashboardPanel,
    PanelEmpty,
} from '@/Modules/Dashboard/Components/DashboardPanel';
import { CountTaskList } from '@/Modules/Dashboard/Components/TaskList';
import type { AuditItem } from '@/Modules/Dashboard/types';

type Props = {
    counts: { students: number; teachers: number; guardians: number };
    incomplete: {
        students_without_guardian: number;
        students_without_classroom: number | null;
        guardians_without_account: number;
        teachers_without_account: number;
    };
    queues: {
        ppdb_to_verify: number | null;
        payments_to_verify: number | null;
        report_cards_to_validate: number | null;
        content_to_review: { draft: number; review: number } | null;
    };
    finance: {
        outstanding: number;
        outstanding_invoices: number;
        overdue: number;
        received_this_month: number;
        pending_verification: number;
    } | null;
    recentActivity: AuditItem[];
};

const BASE = '/operator';

export default function OperatorDashboard({
    counts,
    incomplete,
    queues,
    finance,
    recentActivity,
}: Props) {
    const { auth } = usePage().props;

    return (
        <AppLayout
            breadcrumbs={[{ title: 'Dashboard', href: `${BASE}/dashboard` }]}
        >
            <Head title="Dashboard Operator" />
            <PageHeader
                title="Dashboard Operator"
                description={`${formatDate(new Date(), 'EEEE, d MMMM yyyy')} · Selamat datang, ${auth.user?.name ?? ''}`}
            />

            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    label="Siswa aktif"
                    value={formatNumber(counts.students)}
                    icon={GraduationCap}
                />
                <StatCard
                    label="Guru aktif"
                    value={formatNumber(counts.teachers)}
                    icon={Users}
                />
                <StatCard
                    label="Orang tua"
                    value={formatNumber(counts.guardians)}
                    icon={UserRound}
                />
                <StatCard
                    label="Tunggakan"
                    value={
                        finance
                            ? formatCompactCurrency(finance.outstanding)
                            : '—'
                    }
                    icon={Wallet}
                    hint={
                        finance
                            ? `${formatNumber(finance.outstanding_invoices)} tagihan belum lunas`
                            : undefined
                    }
                />
            </div>

            <div className="grid gap-4 lg:grid-cols-3">
                <DashboardPanel title="Perlu ditindaklanjuti" icon={ListChecks}>
                    <CountTaskList
                        tasks={[
                            {
                                label: 'Pendaftar PPDB menunggu verifikasi',
                                count: queues.ppdb_to_verify,
                                href: `${BASE}/ppdb/pendaftar?status=SUBMITTED`,
                            },
                            {
                                label: 'Pembayaran menunggu verifikasi',
                                count: queues.payments_to_verify,
                                href: `${BASE}/keuangan/verifikasi`,
                            },
                            {
                                label: 'Raport menunggu validasi',
                                count: queues.report_cards_to_validate,
                                href: `${BASE}/raport`,
                            },
                            {
                                label: 'Konten website siap ditinjau',
                                hint: queues.content_to_review
                                    ? `${formatNumber(queues.content_to_review.draft)} konten masih draf`
                                    : undefined,
                                count: queues.content_to_review?.review ?? null,
                                href: `${BASE}/cms/berita?status=REVIEW`,
                            },
                        ]}
                        empty={<PanelEmpty>Tidak ada antrean.</PanelEmpty>}
                    />
                </DashboardPanel>

                <DashboardPanel title="Data belum lengkap" icon={ClipboardList}>
                    <CountTaskList
                        tasks={[
                            {
                                label: 'Siswa tanpa orang tua',
                                count: incomplete.students_without_guardian,
                                href: `${BASE}/master-data/siswa`,
                                tone: 'danger',
                            },
                            {
                                label: 'Siswa belum masuk rombel',
                                hint: 'Tahun ajaran aktif',
                                count: incomplete.students_without_classroom,
                                href: `${BASE}/master-data/kelas`,
                                tone: 'danger',
                            },
                            {
                                label: 'Orang tua belum punya akun',
                                count: incomplete.guardians_without_account,
                                href: `${BASE}/master-data/orang-tua`,
                                tone: 'neutral',
                            },
                            {
                                label: 'Guru belum punya akun',
                                count: incomplete.teachers_without_account,
                                href: `${BASE}/master-data/guru`,
                                tone: 'neutral',
                            },
                        ]}
                    />
                </DashboardPanel>

                <DashboardPanel
                    title="Keuangan"
                    icon={Wallet}
                    href={`${BASE}/keuangan/rekap`}
                    hrefLabel="Rekap"
                >
                    {finance ? (
                        <dl className="space-y-3 text-sm">
                            {(
                                [
                                    [
                                        'Diterima bulan ini',
                                        finance.received_this_month,
                                        'text-emerald-700',
                                    ],
                                    [
                                        'Tunggakan lewat jatuh tempo',
                                        finance.overdue,
                                        'text-rose-700',
                                    ],
                                    [
                                        'Total tunggakan',
                                        finance.outstanding,
                                        'text-slate-900',
                                    ],
                                ] as const
                            ).map(([label, value, tone]) => (
                                <div
                                    key={label}
                                    className="flex items-baseline justify-between gap-3"
                                >
                                    <dt className="text-slate-500">{label}</dt>
                                    <dd
                                        className={`font-semibold tabular-nums ${tone}`}
                                    >
                                        {formatCompactCurrency(value)}
                                    </dd>
                                </div>
                            ))}
                        </dl>
                    ) : (
                        <PanelEmpty>
                            Anda tidak memiliki akses keuangan.
                        </PanelEmpty>
                    )}
                </DashboardPanel>
            </div>

            <DashboardPanel
                title="Aktivitas sistem terbaru"
                icon={History}
                href={`${BASE}/audit-log`}
            >
                <AuditFeedList items={recentActivity} />
            </DashboardPanel>
        </AppLayout>
    );
}
