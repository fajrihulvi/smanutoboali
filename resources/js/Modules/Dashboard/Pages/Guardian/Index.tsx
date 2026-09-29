import { Head, Link, router } from '@inertiajs/react';
import {
    Baby,
    CalendarCheck2,
    ChevronRight,
    FolderKanban,
    HeartPulse,
    History,
    Megaphone,
    MessagesSquare,
    NotebookPen,
    PartyPopper,
    Pin,
    Wallet,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { PersonAvatar } from '@/Components/shared/PersonAvatar';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import { Button } from '@/Components/ui/button';
import { useChatUnread } from '@/hooks/use-chat-unread';
import ParentLayout from '@/Layouts/ParentLayout';
import { formatCurrency, formatDate, formatRelative } from '@/lib/format';
import { cn } from '@/lib/utils';
import { RecentChatsList } from '@/Modules/Dashboard/Components/RecentChatsList';
import type { ChatItem } from '@/Modules/Dashboard/types';
import type { StatusVariant } from '@/types';

type ChildCard = {
    public_id: string;
    name: string;
    photo_url: string | null;
    classroom: string | null;
    attendance: { status: string; label: string } | null;
    outstanding: number;
    active: boolean;
};

type Props = {
    childCards: ChildCard[];
    invoices: {
        public_id: string;
        title: string;
        remaining: number;
        due_date: string;
        overdue: boolean;
    }[];
    recentChats: ChatItem[];
    child: {
        public_id: string;
        name: string;
        classroom: string | null;
        homeroom: string | null;
        photo_url: string | null;
    } | null;
    attendanceToday: { status: string; label: string } | null;
    activitiesToday: {
        public_id: string;
        title: string;
        time: string | null;
        location: string | null;
    }[];
    latestGrades: {
        subject: string;
        component: string;
        score: string;
        published_at: string | null;
    }[];
    latestPortfolio: {
        title: string;
        category: string;
        date: string;
        thumbnail: string | null;
    } | null;
    latestCondition: {
        public_id: string;
        type: string;
        status: string;
        created_at: string;
    } | null;
    announcements: {
        public_id: string;
        title: string;
        pinned: boolean;
        publish_at: string | null;
    }[];
};

const ATTENDANCE_VARIANT: Record<string, StatusVariant> = {
    HADIR: 'success',
    TERLAMBAT: 'warning',
    IZIN: 'info',
    SAKIT: 'info',
    ALPA: 'danger',
};

function Section({
    title,
    icon: Icon,
    href,
    children,
}: {
    title: string;
    icon: LucideIcon;
    href?: string;
    children: ReactNode;
}) {
    return (
        <section className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <header className="mb-3 flex items-center justify-between gap-2">
                <h2 className="flex items-center gap-2 text-sm font-semibold text-slate-900">
                    <Icon className="size-4 text-brand-600" />
                    {title}
                </h2>
                {href && (
                    <Link
                        href={href}
                        className="flex items-center text-xs font-medium text-brand-600 hover:text-brand-700"
                    >
                        Lihat
                        <ChevronRight className="size-3.5" />
                    </Link>
                )}
            </header>
            {children}
        </section>
    );
}

function Muted({ children }: { children: ReactNode }) {
    return <p className="text-sm text-slate-500">{children}</p>;
}

function ChildCards({ items }: { items: ChildCard[] }) {
    return (
        <section
            aria-label="Anak Anda"
            className="-mx-4 overflow-x-auto px-4 sm:mx-0 sm:px-0"
        >
            <ul className="flex gap-3 sm:grid sm:grid-cols-2 lg:grid-cols-3">
                {items.map((item) => (
                    <li
                        key={item.public_id}
                        className="w-64 shrink-0 sm:w-auto"
                    >
                        <button
                            type="button"
                            disabled={item.active}
                            onClick={() =>
                                router.post(
                                    '/orang-tua/anak-aktif',
                                    {
                                        anak: item.public_id,
                                        kembali: '/orang-tua/dashboard',
                                    },
                                    { preserveScroll: true },
                                )
                            }
                            aria-pressed={item.active}
                            className={cn(
                                'flex w-full items-center gap-3 rounded-2xl border bg-white p-3 text-left shadow-sm transition-colors duration-150',
                                item.active
                                    ? 'border-brand-500 ring-1 ring-brand-500'
                                    : 'border-slate-200 hover:border-brand-300',
                            )}
                        >
                            <PersonAvatar
                                name={item.name}
                                photoUrl={item.photo_url}
                                className="size-10"
                            />
                            <span className="min-w-0 flex-1">
                                <span className="block truncate text-sm font-semibold text-slate-900">
                                    {item.name}
                                </span>
                                <span className="block truncate text-xs text-slate-500">
                                    {item.classroom ?? 'Belum ada rombel'}
                                </span>
                                <span className="mt-1 flex flex-wrap items-center gap-1.5">
                                    {item.attendance ? (
                                        <StatusBadge
                                            variant={
                                                ATTENDANCE_VARIANT[
                                                    item.attendance.status
                                                ] ?? 'neutral'
                                            }
                                        >
                                            {item.attendance.label}
                                        </StatusBadge>
                                    ) : (
                                        <span className="text-xs text-slate-400">
                                            Presensi belum diisi
                                        </span>
                                    )}
                                    {item.outstanding > 0 && (
                                        <StatusBadge variant="warning">
                                            {formatCurrency(item.outstanding)}
                                        </StatusBadge>
                                    )}
                                </span>
                            </span>
                        </button>
                    </li>
                ))}
            </ul>
        </section>
    );
}

export default function GuardianDashboard({
    childCards,
    invoices,
    recentChats,
    child,
    attendanceToday,
    activitiesToday,
    latestGrades,
    latestPortfolio,
    latestCondition,
    announcements,
}: Props) {
    const chatUnread = useChatUnread();

    return (
        <ParentLayout>
            <Head title="Beranda" />

            {childCards.length > 1 && <ChildCards items={childCards} />}

            {!child ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState
                        icon={Baby}
                        title="Belum ada anak terhubung"
                        description="Hubungi operator sekolah untuk menautkan akun Anda dengan data anak."
                    />
                </div>
            ) : (
                <>
                    <section className="flex items-center gap-4 rounded-2xl bg-gradient-to-br from-brand-600 to-brand-800 p-4 text-white shadow-sm sm:p-5">
                        <PersonAvatar
                            name={child.name}
                            photoUrl={child.photo_url}
                            className="size-14 ring-2 ring-white/40"
                        />
                        <div className="min-w-0 flex-1">
                            <p className="truncate text-lg font-semibold">
                                {child.name}
                            </p>
                            <p className="truncate text-sm text-white/80">
                                {child.classroom
                                    ? `Kelas ${child.classroom}`
                                    : 'Belum ada rombel aktif'}
                                {child.homeroom &&
                                    ` · Wali kelas ${child.homeroom}`}
                            </p>
                        </div>
                        <Button
                            size="sm"
                            variant="secondary"
                            className="shrink-0"
                            asChild
                        >
                            <Link href="/orang-tua/timeline">
                                <History />
                                <span className="hidden sm:inline">
                                    Timeline
                                </span>
                            </Link>
                        </Button>
                    </section>

                    <div className="grid grid-cols-2 gap-3">
                        <Link
                            href="/orang-tua/presensi"
                            className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition-colors duration-150 hover:border-brand-300"
                        >
                            <CalendarCheck2 className="size-5 text-brand-600" />
                            <p className="mt-2 text-xs text-slate-500">
                                Kehadiran hari ini
                            </p>
                            {attendanceToday ? (
                                <StatusBadge
                                    variant={
                                        ATTENDANCE_VARIANT[
                                            attendanceToday.status
                                        ] ?? 'neutral'
                                    }
                                    className="mt-1"
                                >
                                    {attendanceToday.label}
                                </StatusBadge>
                            ) : (
                                <p className="mt-1 text-sm font-medium text-slate-700">
                                    Belum diisi
                                </p>
                            )}
                        </Link>
                        <Link
                            href="/orang-tua/chat"
                            className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition-colors duration-150 hover:border-brand-300"
                        >
                            <MessagesSquare className="size-5 text-brand-600" />
                            <p className="mt-2 text-xs text-slate-500">
                                Chat belum dibaca
                            </p>
                            <p className="mt-1 text-lg font-semibold text-slate-900 tabular-nums">
                                {chatUnread}
                            </p>
                        </Link>
                    </div>

                    <div className="grid gap-4 md:grid-cols-2">
                        <Section
                            title="Tagihan"
                            icon={Wallet}
                            href="/orang-tua/keuangan"
                        >
                            {invoices.length === 0 ? (
                                <Muted>
                                    Tidak ada tagihan yang belum lunas.
                                </Muted>
                            ) : (
                                <ul className="divide-y divide-slate-100">
                                    {invoices.map((invoice) => (
                                        <li key={invoice.public_id}>
                                            <Link
                                                href={`/orang-tua/keuangan/${invoice.public_id}`}
                                                className="flex items-center justify-between gap-3 py-2 text-sm"
                                            >
                                                <span className="min-w-0">
                                                    <span className="block truncate font-medium text-slate-900">
                                                        {invoice.title}
                                                    </span>
                                                    <span
                                                        className={cn(
                                                            'text-xs',
                                                            invoice.overdue
                                                                ? 'text-rose-600'
                                                                : 'text-slate-500',
                                                        )}
                                                    >
                                                        {invoice.overdue
                                                            ? 'Lewat jatuh tempo'
                                                            : 'Jatuh tempo'}{' '}
                                                        {formatDate(
                                                            invoice.due_date,
                                                        )}
                                                    </span>
                                                </span>
                                                <span className="shrink-0 font-semibold text-slate-900 tabular-nums">
                                                    {formatCurrency(
                                                        invoice.remaining,
                                                    )}
                                                </span>
                                            </Link>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </Section>

                        <Section
                            title="Chat terbaru"
                            icon={MessagesSquare}
                            href="/orang-tua/chat"
                        >
                            <RecentChatsList items={recentChats} />
                        </Section>

                        <Section
                            title="Kegiatan hari ini"
                            icon={PartyPopper}
                            href="/orang-tua/kegiatan"
                        >
                            {activitiesToday.length === 0 ? (
                                <Muted>Tidak ada kegiatan hari ini.</Muted>
                            ) : (
                                <ul className="space-y-2">
                                    {activitiesToday.map((activity) => (
                                        <li
                                            key={activity.public_id}
                                            className="text-sm"
                                        >
                                            <p className="font-medium text-slate-900">
                                                {activity.title}
                                            </p>
                                            <p className="text-xs text-slate-500">
                                                {[
                                                    activity.time,
                                                    activity.location,
                                                ]
                                                    .filter(Boolean)
                                                    .join(' · ') ||
                                                    'Sepanjang hari'}
                                            </p>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </Section>

                        <Section
                            title="Nilai terbaru"
                            icon={NotebookPen}
                            href="/orang-tua/nilai"
                        >
                            {latestGrades.length === 0 ? (
                                <Muted>Belum ada nilai yang diterbitkan.</Muted>
                            ) : (
                                <ul className="divide-y divide-slate-100">
                                    {latestGrades.map((grade, index) => (
                                        <li
                                            key={index}
                                            className="flex items-center justify-between gap-3 py-2 text-sm first:pt-0 last:pb-0"
                                        >
                                            <span className="min-w-0">
                                                <span className="block truncate font-medium text-slate-900">
                                                    {grade.subject}
                                                </span>
                                                <span className="block truncate text-xs text-slate-500">
                                                    {grade.component}
                                                </span>
                                            </span>
                                            <span className="text-lg font-semibold text-brand-700 tabular-nums">
                                                {grade.score}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </Section>

                        <Section
                            title="Kondisi anak"
                            icon={HeartPulse}
                            href="/orang-tua/kondisi-anak"
                        >
                            {latestCondition ? (
                                <Link
                                    href={`/orang-tua/kondisi-anak/${latestCondition.public_id}`}
                                    className="block text-sm"
                                >
                                    <p className="font-medium text-slate-900">
                                        {latestCondition.type}
                                    </p>
                                    <p className="text-xs text-slate-500">
                                        {latestCondition.status} ·{' '}
                                        {formatRelative(
                                            latestCondition.created_at,
                                        )}
                                    </p>
                                </Link>
                            ) : (
                                <Muted>Belum ada laporan.</Muted>
                            )}
                            <Button
                                variant="outline"
                                size="sm"
                                className="mt-3 w-full"
                                asChild
                            >
                                <Link href="/orang-tua/kondisi-anak/lapor">
                                    Laporkan kondisi anak
                                </Link>
                            </Button>
                        </Section>

                        <Section
                            title="Portofolio terbaru"
                            icon={FolderKanban}
                            href="/orang-tua/portofolio"
                        >
                            {latestPortfolio ? (
                                <div className="flex items-center gap-3">
                                    {latestPortfolio.thumbnail ? (
                                        <img
                                            src={latestPortfolio.thumbnail}
                                            alt=""
                                            loading="lazy"
                                            className="size-14 shrink-0 rounded-xl object-cover"
                                        />
                                    ) : (
                                        <span className="flex size-14 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-500">
                                            <FolderKanban className="size-6" />
                                        </span>
                                    )}
                                    <div className="min-w-0 text-sm">
                                        <p className="truncate font-medium text-slate-900">
                                            {latestPortfolio.title}
                                        </p>
                                        <p className="text-xs text-slate-500">
                                            {latestPortfolio.category} ·{' '}
                                            {formatDate(latestPortfolio.date)}
                                        </p>
                                    </div>
                                </div>
                            ) : (
                                <Muted>Belum ada portofolio.</Muted>
                            )}
                        </Section>
                    </div>
                </>
            )}

            <Section
                title="Pengumuman"
                icon={Megaphone}
                href="/orang-tua/pengumuman"
            >
                {announcements.length === 0 ? (
                    <Muted>Belum ada pengumuman.</Muted>
                ) : (
                    <ul className="divide-y divide-slate-100">
                        {announcements.map((announcement) => (
                            <li key={announcement.public_id}>
                                <Link
                                    href={`/orang-tua/pengumuman/${announcement.public_id}`}
                                    className="flex items-start gap-2 py-2 text-sm"
                                >
                                    {announcement.pinned && (
                                        <Pin className="mt-0.5 size-3.5 shrink-0 text-brand-600" />
                                    )}
                                    <span className="min-w-0 flex-1">
                                        <span className="block font-medium text-slate-900">
                                            {announcement.title}
                                        </span>
                                        <span className="text-xs text-slate-500">
                                            {formatRelative(
                                                announcement.publish_at,
                                            )}
                                        </span>
                                    </span>
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
            </Section>
        </ParentLayout>
    );
}
