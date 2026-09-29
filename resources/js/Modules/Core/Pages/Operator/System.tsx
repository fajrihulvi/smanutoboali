import { Head, Link, router } from '@inertiajs/react';
import {
    Activity,
    Archive,
    Database,
    FileText,
    HardDrive,
    Info,
    ListRestart,
    RefreshCw,
    RotateCcw,
    Server,
    Trash2,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import { PageHeader } from '@/Components/shared/PageHeader';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import { Button } from '@/Components/ui/button';
import { Spinner } from '@/Components/ui/spinner';
import { useConfirmDelete } from '@/hooks/use-confirm-delete';
import AppLayout from '@/Layouts/AppLayout';
import {
    formatDate,
    formatFileSize,
    formatNumber,
    formatRelative,
} from '@/lib/format';
import { toastFirstError } from '@/lib/toast-errors';

type Props = {
    app: {
        name: string;
        version: string;
        environment: string;
        debug: boolean;
        url: string;
        php: string;
        laravel: string;
        database: string;
        drivers: Record<string, string>;
        timezone: string;
    };
    health: {
        status: 'ok' | 'degraded' | 'down';
        checks: {
            scheduler: { ok: boolean; last_run_at: string | null };
            queue: { last_processed_at?: string | null };
        };
    };
    queue: {
        pending: number;
        reserved: number;
        oldest_pending_at: string | null;
        failed_total: number;
        failed: {
            uuid: string;
            queue: string;
            name: string;
            error: string;
            failed_at: string;
        }[];
    };
    backups: {
        type: 'database' | 'files';
        disk: string;
        reachable: boolean;
        count: number;
        total_bytes: number;
        newest_at: string | null;
        newest_bytes: number | null;
        healthy: boolean;
    }[];
    storage: {
        folders: { label: string; bytes: number }[];
        free_bytes: number | null;
        measured_at: string;
    };
    logs: { name: string; bytes: number; modified_at: string }[];
};

const BASE = '/operator/sistem';

function Panel({
    title,
    icon: Icon,
    action,
    children,
}: {
    title: string;
    icon: LucideIcon;
    action?: ReactNode;
    children: ReactNode;
}) {
    return (
        <section className="min-w-0 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <header className="mb-4 flex flex-wrap items-center justify-between gap-2">
                <h2 className="flex items-center gap-2 text-sm font-semibold text-slate-900">
                    <Icon className="size-4 text-brand-600" />
                    {title}
                </h2>
                {action}
            </header>
            {children}
        </section>
    );
}

function Row({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div className="flex items-baseline justify-between gap-4 py-1.5 text-sm">
            <dt className="shrink-0 text-slate-500">{label}</dt>
            <dd className="min-w-0 truncate text-right font-medium text-slate-900">
                {children}
            </dd>
        </div>
    );
}

export default function SystemPage({
    app,
    health,
    queue,
    backups,
    storage,
    logs,
}: Props) {
    const [busy, setBusy] = useState<string | null>(null);
    const { confirmDelete, dialog } = useConfirmDelete();
    const scheduler = health.checks.scheduler;

    const post = (url: string, key: string) =>
        router.post(
            url,
            {},
            {
                preserveScroll: true,
                onStart: () => setBusy(key),
                onFinish: () => setBusy(null),
                onError: (errors) => toastFirstError(errors),
            },
        );

    return (
        <AppLayout breadcrumbs={[{ title: 'Sistem', href: BASE }]}>
            <Head title="Sistem" />
            <PageHeader
                title="Sistem"
                description="Kondisi aplikasi, antrean, backup, dan penyimpanan. Pemeriksaan ini tidak mengubah data."
            />

            <div className="grid gap-4 lg:grid-cols-3">
                <Panel title="Aplikasi" icon={Info}>
                    <dl className="divide-y divide-slate-100">
                        <Row label="Versi">{app.version}</Row>
                        <Row label="Lingkungan">
                            {app.environment}
                            {app.debug && (
                                <StatusBadge variant="warning" className="ml-2">
                                    debug aktif
                                </StatusBadge>
                            )}
                        </Row>
                        <Row label="PHP">{app.php}</Row>
                        <Row label="Laravel">{app.laravel}</Row>
                        <Row label="Database">{app.database}</Row>
                        <Row label="Cache · antrean · sesi">
                            {app.drivers.cache} · {app.drivers.queue} ·{' '}
                            {app.drivers.session}
                        </Row>
                        <Row label="Email · realtime">
                            {app.drivers.mail} · {app.drivers.broadcast}
                        </Row>
                        <Row label="Zona waktu">{app.timezone}</Row>
                    </dl>
                </Panel>

                <Panel title="Kesehatan" icon={Activity}>
                    <div className="space-y-3 text-sm">
                        <div className="flex items-center justify-between gap-3">
                            <span className="text-slate-500">
                                Status keseluruhan
                            </span>
                            <StatusBadge
                                variant={
                                    health.status === 'ok'
                                        ? 'success'
                                        : health.status === 'degraded'
                                          ? 'warning'
                                          : 'danger'
                                }
                                dot
                            >
                                {health.status === 'ok'
                                    ? 'Normal'
                                    : health.status === 'degraded'
                                      ? 'Terganggu'
                                      : 'Mati'}
                            </StatusBadge>
                        </div>
                        <div className="rounded-xl bg-slate-50 p-3">
                            <p className="font-medium text-slate-900">
                                Scheduler (cron)
                            </p>
                            <p
                                className={
                                    scheduler.ok
                                        ? 'text-xs text-emerald-700'
                                        : 'text-xs text-rose-700'
                                }
                            >
                                {scheduler.last_run_at
                                    ? `Terakhir berjalan ${formatRelative(scheduler.last_run_at)}`
                                    : 'Belum pernah berjalan — periksa cron di cPanel.'}
                            </p>
                        </div>
                        <div className="rounded-xl bg-slate-50 p-3">
                            <p className="font-medium text-slate-900">
                                Pemrosesan antrean
                            </p>
                            <p className="text-xs text-slate-500">
                                {health.checks.queue.last_processed_at
                                    ? `Job terakhir diproses ${formatRelative(health.checks.queue.last_processed_at)}`
                                    : 'Belum ada job yang diproses.'}
                            </p>
                        </div>
                    </div>
                </Panel>

                <Panel
                    title="Penyimpanan"
                    icon={HardDrive}
                    action={
                        <Link
                            href={`${BASE}?segarkan=1`}
                            preserveScroll
                            className="text-xs font-medium text-brand-600 hover:text-brand-700"
                        >
                            Hitung ulang
                        </Link>
                    }
                >
                    <dl className="divide-y divide-slate-100">
                        {storage.folders.map((f) => (
                            <Row key={f.label} label={f.label}>
                                {formatFileSize(f.bytes)}
                            </Row>
                        ))}
                        <Row label="Sisa ruang disk">
                            {storage.free_bytes === null
                                ? 'Tidak tersedia'
                                : formatFileSize(storage.free_bytes)}
                        </Row>
                    </dl>
                    <p className="mt-2 text-xs text-slate-400">
                        Diukur {formatRelative(storage.measured_at)}
                    </p>
                </Panel>
            </div>

            <Panel
                title="Antrean"
                icon={ListRestart}
                action={
                    queue.failed_total > 0 && (
                        <Button
                            size="sm"
                            variant="outline"
                            disabled={busy !== null}
                            onClick={() =>
                                post(`${BASE}/antrean/ulang-semua`, 'all')
                            }
                        >
                            {busy === 'all' ? <Spinner /> : <RotateCcw />}
                            Ulang semua ({formatNumber(queue.failed_total)})
                        </Button>
                    )
                }
            >
                <div className="mb-4 grid grid-cols-3 gap-3 text-center">
                    {(
                        [
                            ['Menunggu', queue.pending],
                            ['Diproses', queue.reserved],
                            ['Gagal', queue.failed_total],
                        ] as const
                    ).map(([label, value]) => (
                        <div key={label} className="rounded-xl bg-slate-50 p-3">
                            <p className="text-xs text-slate-500">{label}</p>
                            <p className="text-xl font-semibold text-slate-900 tabular-nums">
                                {formatNumber(value)}
                            </p>
                        </div>
                    ))}
                </div>
                {queue.oldest_pending_at && (
                    <p className="mb-3 text-xs text-slate-500">
                        Job tertua menunggu sejak{' '}
                        {formatRelative(queue.oldest_pending_at)}.
                    </p>
                )}
                {queue.failed.length === 0 ? (
                    <p className="text-sm text-emerald-700">
                        Tidak ada job gagal.
                    </p>
                ) : (
                    <ul className="divide-y divide-slate-100">
                        {queue.failed.map((job) => (
                            <li
                                key={job.uuid}
                                className="flex flex-wrap items-start gap-3 py-3"
                            >
                                <span className="min-w-0 flex-1">
                                    <span className="block text-sm font-medium text-slate-900">
                                        {job.name}
                                        <span className="ml-2 text-xs font-normal text-slate-400">
                                            {job.queue} ·{' '}
                                            {formatDate(
                                                job.failed_at,
                                                'd MMM yyyy, HH.mm',
                                            )}
                                        </span>
                                    </span>
                                    <span className="block text-xs break-words text-rose-700">
                                        {job.error}
                                    </span>
                                </span>
                                <span className="flex gap-1">
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        disabled={busy !== null}
                                        onClick={() =>
                                            post(
                                                `${BASE}/antrean/${job.uuid}/ulang`,
                                                job.uuid,
                                            )
                                        }
                                    >
                                        {busy === job.uuid ? (
                                            <Spinner />
                                        ) : (
                                            <RotateCcw />
                                        )}
                                        Ulang
                                    </Button>
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        className="size-8 text-slate-400 hover:bg-rose-50 hover:text-rose-600"
                                        aria-label={`Hapus job ${job.name}`}
                                        onClick={() =>
                                            confirmDelete({
                                                url: `${BASE}/antrean/${job.uuid}`,
                                                title: 'Hapus job gagal?',
                                                description:
                                                    'Job tidak akan dijalankan lagi.',
                                            })
                                        }
                                    >
                                        <Trash2 className="size-4" />
                                    </Button>
                                </span>
                            </li>
                        ))}
                    </ul>
                )}
            </Panel>

            <div className="grid gap-4 lg:grid-cols-2">
                <Panel
                    title="Backup"
                    icon={Archive}
                    action={
                        <Button
                            size="sm"
                            disabled={busy !== null}
                            onClick={() => post(`${BASE}/backup`, 'backup')}
                        >
                            {busy === 'backup' ? <Spinner /> : <Database />}
                            Backup database sekarang
                        </Button>
                    }
                >
                    <ul className="divide-y divide-slate-100">
                        {backups.map((b) => (
                            <li
                                key={`${b.type}-${b.disk}`}
                                className="flex items-center justify-between gap-3 py-2.5"
                            >
                                <span className="min-w-0">
                                    <span className="block text-sm font-medium text-slate-900">
                                        {b.type === 'database'
                                            ? 'Database (harian)'
                                            : 'File unggahan (mingguan)'}
                                        <span className="ml-2 text-xs font-normal text-slate-400">
                                            disk {b.disk}
                                        </span>
                                    </span>
                                    <span className="block text-xs text-slate-500">
                                        {!b.reachable
                                            ? 'Tujuan backup tidak bisa diakses.'
                                            : b.newest_at
                                              ? `Terakhir ${formatDate(b.newest_at, 'd MMM yyyy, HH.mm')} · ${formatFileSize(b.newest_bytes ?? 0)} · ${b.count} arsip (${formatFileSize(b.total_bytes)})`
                                              : 'Belum ada backup.'}
                                    </span>
                                </span>
                                <StatusBadge
                                    variant={b.healthy ? 'success' : 'danger'}
                                    dot
                                >
                                    {b.healthy ? 'Sehat' : 'Perlu dicek'}
                                </StatusBadge>
                            </li>
                        ))}
                    </ul>
                    <p className="mt-3 text-xs text-slate-500">
                        Otomatis: database tiap hari 02.30, file tiap Minggu
                        03.00. Retensi 7 hari, 4 minggu, 3 bulan. Email dikirim
                        bila backup gagal.
                    </p>
                </Panel>

                <Panel title="Log" icon={FileText}>
                    {logs.length === 0 ? (
                        <p className="text-sm text-slate-500">
                            Belum ada file log.
                        </p>
                    ) : (
                        <ul className="divide-y divide-slate-100 text-sm">
                            {logs.map((log) => (
                                <li
                                    key={log.name}
                                    className="flex items-center justify-between gap-3 py-2"
                                >
                                    <span className="flex min-w-0 items-center gap-2">
                                        <Server className="size-4 shrink-0 text-slate-400" />
                                        <span className="truncate font-mono text-xs text-slate-700">
                                            {log.name}
                                        </span>
                                    </span>
                                    <span className="shrink-0 text-xs text-slate-500">
                                        {formatFileSize(log.bytes)} ·{' '}
                                        {formatRelative(log.modified_at)}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}
                    <p className="mt-3 flex items-center gap-1.5 text-xs text-slate-500">
                        <RefreshCw className="size-3" />
                        Log umum disimpan 14 hari, log pembayaran & login 30
                        hari. Isi log dibuka lewat File Manager cPanel.
                    </p>
                </Panel>
            </div>
            {dialog}
        </AppLayout>
    );
}
