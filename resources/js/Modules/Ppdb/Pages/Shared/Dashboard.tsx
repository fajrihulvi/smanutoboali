import { Head, Link, router } from '@inertiajs/react';
import { Users } from 'lucide-react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { PageHeader } from '@/Components/shared/PageHeader';
import AppLayout from '@/Layouts/AppLayout';
import { formatDate } from '@/lib/format';
import { NativeSelect } from '@/Modules/Academic/Components/NativeSelect';
import type { Option } from '@/Modules/Academic/types';

type Props = {
    base: string;
    periods: Option[];
    period: {
        public_id: string;
        name: string;
        is_open: boolean;
        closes_at: string;
        results_published_at: string | null;
    } | null;
    stats: {
        statuses: { value: string; label: string; total: number }[];
        tracks: {
            public_id: string;
            name: string;
            quota: number;
            registered: number;
            verified: number;
            accepted: number;
        }[];
        daily: { date: string; total: number }[];
    } | null;
};

export default function PpdbDashboard({ base, periods, period, stats }: Props) {
    const max = Math.max(1, ...(stats?.daily.map((d) => d.total) ?? [1]));

    return (
        <AppLayout breadcrumbs={[{ title: 'PPDB', href: `${base}/dashboard` }]}>
            <Head title="Dashboard PPDB" />
            <PageHeader
                title="Dashboard PPDB"
                description={
                    period
                        ? `${period.name} · ${period.is_open ? `dibuka sampai ${formatDate(period.closes_at, 'd MMM yyyy')}` : 'tidak sedang dibuka'} · ${period.results_published_at ? 'hasil sudah diumumkan' : 'hasil belum diumumkan'}`
                        : undefined
                }
                actions={
                    periods.length > 1 && (
                        <NativeSelect
                            aria-label="Pilih periode"
                            value={period?.public_id ?? ''}
                            onChange={(e) =>
                                router.get(`${base}/dashboard`, {
                                    periode: e.target.value,
                                })
                            }
                            className="w-64"
                        >
                            {periods.map((p) => (
                                <option key={p.value} value={p.value}>
                                    {p.label}
                                </option>
                            ))}
                        </NativeSelect>
                    )
                }
            />

            {!period || !stats ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState icon={Users} title="Belum ada periode PPDB" />
                </div>
            ) : (
                <>
                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        {stats.statuses.map((s) => (
                            <Link
                                key={s.value}
                                href={`${base}/pendaftar`}
                                data={{
                                    periode: period.public_id,
                                    status: s.value,
                                }}
                                className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition-colors duration-150 hover:border-brand-300"
                            >
                                <p className="text-xs text-slate-500">
                                    {s.label}
                                </p>
                                <p className="mt-1 text-2xl font-semibold text-slate-900 tabular-nums">
                                    {s.total}
                                </p>
                            </Link>
                        ))}
                    </div>

                    <section className="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 className="text-sm font-semibold text-slate-900">
                            Per jalur
                        </h2>
                        <ul className="space-y-4">
                            {stats.tracks.map((t) => (
                                <li key={t.public_id} className="space-y-1.5">
                                    <div className="flex flex-wrap justify-between gap-2 text-sm">
                                        <span className="font-medium text-slate-900">
                                            {t.name}
                                        </span>
                                        <span className="text-slate-500 tabular-nums">
                                            {t.registered} pendaftar ·{' '}
                                            {t.verified} terverifikasi ·{' '}
                                            {t.accepted}/{t.quota} diterima
                                        </span>
                                    </div>
                                    <div
                                        className="h-2.5 overflow-hidden rounded-full bg-slate-100"
                                        title={`${t.accepted} dari kuota ${t.quota}`}
                                    >
                                        <div
                                            className="h-full rounded-full bg-emerald-500"
                                            style={{
                                                width: `${Math.min(100, (t.accepted / Math.max(1, t.quota)) * 100)}%`,
                                            }}
                                        />
                                    </div>
                                </li>
                            ))}
                        </ul>
                    </section>

                    <section className="space-y-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 className="text-sm font-semibold text-slate-900">
                            Pendaftar terkirim per hari (30 hari)
                        </h2>
                        <div
                            className="flex h-36 items-end gap-0.5"
                            role="img"
                            aria-label="Grafik pendaftar harian"
                        >
                            {stats.daily.map((d) => (
                                <div
                                    key={d.date}
                                    className="flex h-full flex-1 flex-col justify-end"
                                    title={`${formatDate(d.date, 'd MMM')}: ${d.total}`}
                                >
                                    <div
                                        className="rounded-t bg-brand-500"
                                        style={{
                                            height: `${(d.total / max) * 100}%`,
                                            minHeight: d.total > 0 ? 3 : 0,
                                        }}
                                    />
                                </div>
                            ))}
                        </div>
                        <div className="flex justify-between text-xs text-slate-400">
                            <span>
                                {formatDate(stats.daily[0]?.date, 'd MMM')}
                            </span>
                            <span>
                                {formatDate(
                                    stats.daily[stats.daily.length - 1]?.date,
                                    'd MMM',
                                )}
                            </span>
                        </div>
                    </section>
                </>
            )}
        </AppLayout>
    );
}
