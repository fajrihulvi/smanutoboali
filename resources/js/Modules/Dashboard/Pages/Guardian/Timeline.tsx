import { Head, Link } from '@inertiajs/react';
import {
    Baby,
    CalendarCheck2,
    FileText,
    FolderKanban,
    HeartPulse,
    History,
    NotebookPen,
    PartyPopper,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { MonthPicker } from '@/Components/shared/MonthPicker';
import ParentLayout from '@/Layouts/ParentLayout';
import { formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';

type Entry = {
    key: string;
    kind:
        | 'attendance'
        | 'grade'
        | 'activity'
        | 'portfolio'
        | 'report_card'
        | 'condition';
    at: string;
    title: string;
    detail: string | null;
    url: string | null;
    tone: 'success' | 'warning' | 'danger' | 'info' | 'brand';
};

type Props = {
    child: { public_id: string; name: string } | null;
    month: string;
    entries: Entry[];
};

const ICONS: Record<Entry['kind'], LucideIcon> = {
    attendance: CalendarCheck2,
    grade: NotebookPen,
    activity: PartyPopper,
    portfolio: FolderKanban,
    report_card: FileText,
    condition: HeartPulse,
};

const TONES: Record<Entry['tone'], string> = {
    success: 'bg-emerald-100 text-emerald-700',
    warning: 'bg-amber-100 text-amber-700',
    danger: 'bg-rose-100 text-rose-700',
    info: 'bg-sky-100 text-sky-700',
    brand: 'bg-brand-100 text-brand-700',
};

/** Kelompokkan entri per tanggal (urutan dari server: terbaru dulu). */
function byDay(entries: Entry[]): [string, Entry[]][] {
    const groups = new Map<string, Entry[]>();

    for (const entry of entries) {
        const day = entry.at.slice(0, 10);
        groups.set(day, [...(groups.get(day) ?? []), entry]);
    }

    return [...groups.entries()];
}

export default function ChildTimeline({ child, month, entries }: Props) {
    return (
        <ParentLayout>
            <Head title="Timeline Anak" />

            <div className="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 className="text-xl font-semibold text-slate-900">
                        Timeline Anak
                    </h1>
                    <p className="text-sm text-slate-500">
                        {child
                            ? `Perjalanan ${child.name} di sekolah bulan ini.`
                            : 'Riwayat anak di sekolah.'}
                    </p>
                </div>
                {child && <MonthPicker month={month} />}
            </div>

            {!child ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState icon={Baby} title="Belum ada anak terhubung" />
                </div>
            ) : entries.length === 0 ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState
                        icon={History}
                        title="Belum ada catatan bulan ini"
                        description="Presensi (selain hadir), nilai, kegiatan, portofolio, raport, dan laporan kondisi anak akan tampil di sini."
                    />
                </div>
            ) : (
                <ol className="space-y-5">
                    {byDay(entries).map(([day, items]) => (
                        <li key={day}>
                            <h2 className="mb-2 text-xs font-semibold tracking-wide text-slate-500 uppercase">
                                {formatDate(day, 'EEEE, d MMMM')}
                            </h2>
                            <ul className="space-y-2">
                                {items.map((entry) => {
                                    const Icon = ICONS[entry.kind];
                                    const body = (
                                        <>
                                            <span
                                                className={cn(
                                                    'flex size-9 shrink-0 items-center justify-center rounded-xl',
                                                    TONES[entry.tone],
                                                )}
                                            >
                                                <Icon className="size-4" />
                                            </span>
                                            <span className="min-w-0 flex-1">
                                                <span className="block text-sm font-medium break-words text-slate-900">
                                                    {entry.title}
                                                </span>
                                                {entry.detail && (
                                                    <span className="block text-xs break-words text-slate-500">
                                                        {entry.detail}
                                                    </span>
                                                )}
                                            </span>
                                        </>
                                    );

                                    return (
                                        <li key={entry.key}>
                                            {entry.url ? (
                                                <Link
                                                    href={entry.url}
                                                    className="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm transition-colors duration-150 hover:border-brand-300"
                                                >
                                                    {body}
                                                </Link>
                                            ) : (
                                                <div className="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
                                                    {body}
                                                </div>
                                            )}
                                        </li>
                                    );
                                })}
                            </ul>
                        </li>
                    ))}
                </ol>
            )}
        </ParentLayout>
    );
}
