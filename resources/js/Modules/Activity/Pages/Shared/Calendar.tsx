import { Head, Link } from '@inertiajs/react';
import { CalendarX2, List, Plus } from 'lucide-react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { MonthPicker } from '@/Components/shared/MonthPicker';
import { PageHeader } from '@/Components/shared/PageHeader';
import { Button } from '@/Components/ui/button';
import AppLayout from '@/Layouts/AppLayout';
import ParentLayout from '@/Layouts/ParentLayout';
import { formatDate } from '@/lib/format';
import { monthGrid } from '@/lib/month';
import { cn } from '@/lib/utils';
import { ActivityDetailDialog } from '@/Modules/Activity/Components/ActivityDetailDialog';
import { timeLabel } from '@/Modules/Activity/Components/ActivityMeta';
import type { ActivityRow } from '@/Modules/Activity/types';

type Props = {
    base: string;
    month: string;
    activities: ActivityRow[];
    canManage: boolean;
};

const WEEKDAYS = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];

const CHIP: Record<ActivityRow['type']['value'], string> = {
    SEKOLAH: 'bg-brand-100 text-brand-800',
    KELAS: 'bg-sky-100 text-sky-800',
    HARIAN: 'bg-slate-100 text-slate-700',
};

export default function ActivityCalendar({
    base,
    month,
    activities,
    canManage,
}: Props) {
    const [selected, setSelected] = useState<ActivityRow | null>(null);
    const isGuardian = base.startsWith('/orang-tua');
    const today = new Date().toLocaleDateString('en-CA');
    const byDate = activities.reduce<Record<string, ActivityRow[]>>(
        (acc, activity) => {
            (acc[activity.date] ??= []).push(activity);

            return acc;
        },
        {},
    );

    const body = (
        <>
            <Head title="Kalender Kegiatan" />

            {isGuardian ? (
                <div className="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-semibold text-slate-900">
                            Kalender kegiatan
                        </h1>
                        <p className="text-sm text-slate-500">
                            Kegiatan sekolah & kelas anak.
                        </p>
                    </div>
                    <Button variant="outline" size="sm" asChild>
                        <Link href={base}>
                            <List />
                            Feed
                        </Link>
                    </Button>
                </div>
            ) : (
                <PageHeader
                    title="Kalender Kegiatan"
                    description="Kegiatan harian, kelas, dan sekolah."
                    actions={
                        canManage && (
                            <>
                                <Button variant="outline" asChild>
                                    <Link href={base}>
                                        <List />
                                        Daftar
                                    </Link>
                                </Button>
                                <Button asChild>
                                    <Link href={`${base}/tambah`}>
                                        <Plus />
                                        Tambah
                                    </Link>
                                </Button>
                            </>
                        )
                    }
                />
            )}

            <section className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div className="mb-3 flex justify-center">
                    <MonthPicker month={month} />
                </div>
                <div className="grid grid-cols-7 gap-1 text-center text-xs font-medium text-slate-500">
                    {WEEKDAYS.map((day) => (
                        <div key={day} className="py-1">
                            {day}
                        </div>
                    ))}
                </div>
                <div className="grid grid-cols-7 gap-1">
                    {monthGrid(month)
                        .flat()
                        .map((date, i) =>
                            date === null ? (
                                <div key={`e-${i}`} aria-hidden="true" />
                            ) : (
                                <div
                                    key={date}
                                    className={cn(
                                        'min-h-12 rounded-lg border p-1 sm:min-h-24',
                                        date === today
                                            ? 'border-brand-300 bg-brand-50/40'
                                            : 'border-slate-100',
                                    )}
                                >
                                    <p className="text-xs font-medium text-slate-500 tabular-nums">
                                        {Number(date.slice(8))}
                                    </p>
                                    {/* Layar kecil: titik; layar lebar: chip judul. */}
                                    <div className="mt-1 flex flex-wrap gap-0.5 sm:hidden">
                                        {(byDate[date] ?? []).map(
                                            (activity) => (
                                                <button
                                                    key={activity.public_id}
                                                    type="button"
                                                    onClick={() =>
                                                        setSelected(activity)
                                                    }
                                                    className={cn(
                                                        'size-2.5 rounded-full',
                                                        activity.type.value ===
                                                            'SEKOLAH'
                                                            ? 'bg-brand-500'
                                                            : activity.type
                                                                    .value ===
                                                                'KELAS'
                                                              ? 'bg-sky-500'
                                                              : 'bg-slate-400',
                                                    )}
                                                    aria-label={activity.title}
                                                />
                                            ),
                                        )}
                                    </div>
                                    <ul className="mt-1 hidden space-y-0.5 sm:block">
                                        {(byDate[date] ?? [])
                                            .slice(0, 3)
                                            .map((activity) => (
                                                <li key={activity.public_id}>
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            setSelected(
                                                                activity,
                                                            )
                                                        }
                                                        className={cn(
                                                            'w-full truncate rounded px-1.5 py-0.5 text-left text-xs font-medium',
                                                            CHIP[
                                                                activity.type
                                                                    .value
                                                            ],
                                                            activity.status
                                                                .value ===
                                                                'DRAF' &&
                                                                'opacity-60',
                                                        )}
                                                    >
                                                        {activity.title}
                                                    </button>
                                                </li>
                                            ))}
                                        {(byDate[date]?.length ?? 0) > 3 && (
                                            <li className="px-1.5 text-xs text-slate-500">
                                                +
                                                {(byDate[date]?.length ?? 0) -
                                                    3}{' '}
                                                lagi
                                            </li>
                                        )}
                                    </ul>
                                </div>
                            ),
                        )}
                </div>
            </section>

            <section>
                <h2 className="mb-3 text-base font-semibold text-slate-900">
                    Agenda bulan ini
                </h2>
                {activities.length === 0 ? (
                    <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <EmptyState
                            icon={CalendarX2}
                            title="Tidak ada kegiatan bulan ini"
                        />
                    </div>
                ) : (
                    <ul className="divide-y divide-slate-100 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        {activities.map((activity) => (
                            <li key={activity.public_id}>
                                <button
                                    type="button"
                                    onClick={() => setSelected(activity)}
                                    className="flex w-full items-center gap-3 px-4 py-3 text-left hover:bg-slate-50"
                                >
                                    <span className="w-14 shrink-0 text-center">
                                        <span className="block text-lg font-semibold text-slate-900 tabular-nums">
                                            {Number(activity.date.slice(8))}
                                        </span>
                                        <span className="block text-xs text-slate-500">
                                            {formatDate(activity.date, 'EEE')}
                                        </span>
                                    </span>
                                    <span className="min-w-0 flex-1">
                                        <span className="block truncate font-medium text-slate-900">
                                            {activity.title}
                                        </span>
                                        <span className="block truncate text-xs text-slate-500">
                                            {activity.type.label}
                                            {timeLabel(activity) &&
                                                ` · ${timeLabel(activity)}`}
                                            {activity.location &&
                                                ` · ${activity.location}`}
                                            {activity.status.value === 'DRAF' &&
                                                ' · Draf'}
                                        </span>
                                    </span>
                                </button>
                            </li>
                        ))}
                    </ul>
                )}
            </section>

            <ActivityDetailDialog
                activity={selected}
                onClose={() => setSelected(null)}
                manageBase={canManage ? base : undefined}
            />
        </>
    );

    return isGuardian ? (
        <ParentLayout>{body}</ParentLayout>
    ) : (
        <AppLayout
            breadcrumbs={[
                { title: 'Kegiatan', href: base },
                ...(canManage ? [{ title: 'Kalender', href: '#' }] : []),
            ]}
        >
            <div className="space-y-6">{body as ReactNode}</div>
        </AppLayout>
    );
}
