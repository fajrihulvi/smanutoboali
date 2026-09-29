import { Head, Link, router } from '@inertiajs/react';
import { Calculator, CheckCheck, Megaphone } from 'lucide-react';
import { Fragment, useState } from 'react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { PageHeader } from '@/Components/shared/PageHeader';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Spinner } from '@/Components/ui/spinner';
import AppLayout from '@/Layouts/AppLayout';
import { formatDateTime } from '@/lib/format';
import { toastFirstError } from '@/lib/toast-errors';
import { cn } from '@/lib/utils';
import { NativeSelect } from '@/Modules/Academic/Components/NativeSelect';
import type { Option } from '@/Modules/Academic/types';
import { ApplicantStatusBadge } from '@/Modules/Ppdb/Components/ApplicantStatusBadge';
import type { ApplicantRow } from '@/Modules/Ppdb/types';

type Props = {
    base: string;
    periods: Option[];
    period: {
        public_id: string;
        name: string;
        results_published_at: string | null;
    } | null;
    tracks: (Option & { quota: number })[];
    track: { public_id: string; name: string; quota: number } | null;
    applicants: ApplicantRow[];
    pendingPublish: number;
    can: { manage: boolean; decide: boolean };
};

const post = (url: string, data: Record<string, string> = {}) =>
    router.post(url, data, { preserveScroll: true, onError: toastFirstError });

export default function Selection({
    base,
    periods,
    period,
    tracks,
    track,
    applicants,
    pendingPublish,
    can,
}: Props) {
    const [busy, setBusy] = useState<string | null>(null);
    const detailBase = base.replace('/seleksi', '');
    const undecided = applicants.filter(
        (a) => a.status.value === 'IN_SELECTION' && a.selection?.proposal,
    ).length;

    const go = (params: Record<string, string>) =>
        router.get(
            base,
            {
                periode: period?.public_id ?? '',
                jalur: track?.public_id ?? '',
                ...params,
            },
            { preserveScroll: true },
        );

    const run = (key: string, url: string) => {
        setBusy(key);
        router.post(
            url,
            {},
            {
                preserveScroll: true,
                onError: toastFirstError,
                onFinish: () => setBusy(null),
            },
        );
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'PPDB', href: `${detailBase}/dashboard` },
                { title: 'Seleksi', href: base },
            ]}
        >
            <Head title="Seleksi PPDB" />
            <PageHeader
                title="Seleksi"
                description={
                    can.manage
                        ? 'Isi skor pendaftar terverifikasi, hitung usulan sesuai kuota, lalu kepala sekolah memutuskan. Umumkan hasil setelah diputuskan.'
                        : 'Tinjau usulan operator lalu putuskan: setujui semua usulan jalur ini atau terima/tolak satu per satu.'
                }
                actions={
                    <>
                        {can.manage && period && (
                            <Button
                                onClick={() =>
                                    run(
                                        'publish',
                                        `/operator/ppdb/periode/${period.public_id}/umumkan`,
                                    )
                                }
                                disabled={busy !== null || pendingPublish === 0}
                            >
                                {busy === 'publish' ? (
                                    <Spinner />
                                ) : (
                                    <Megaphone />
                                )}
                                Umumkan hasil
                                {pendingPublish > 0 && ` (${pendingPublish})`}
                            </Button>
                        )}
                    </>
                }
            />

            <div className="flex flex-wrap items-center gap-3">
                {periods.length > 1 && (
                    <NativeSelect
                        aria-label="Periode"
                        value={period?.public_id ?? ''}
                        onChange={(e) =>
                            router.get(base, { periode: e.target.value })
                        }
                        className="w-60"
                    >
                        {periods.map((p) => (
                            <option key={p.value} value={p.value}>
                                {p.label}
                            </option>
                        ))}
                    </NativeSelect>
                )}
                <div
                    role="tablist"
                    className="flex flex-wrap gap-1 rounded-lg bg-slate-100 p-1"
                >
                    {tracks.map((t) => (
                        <button
                            key={t.value}
                            type="button"
                            role="tab"
                            aria-selected={track?.public_id === t.value}
                            onClick={() => go({ jalur: t.value })}
                            className={cn(
                                'rounded-md px-3 py-1.5 text-sm font-medium',
                                track?.public_id === t.value
                                    ? 'bg-white text-slate-900 shadow-sm'
                                    : 'text-slate-500 hover:text-slate-700',
                            )}
                        >
                            {t.label}{' '}
                            <span className="text-xs text-slate-400">
                                · {t.quota}
                            </span>
                        </button>
                    ))}
                </div>
                {period?.results_published_at && (
                    <span className="text-xs text-emerald-700">
                        Hasil diumumkan{' '}
                        {formatDateTime(period.results_published_at)}
                    </span>
                )}
            </div>

            {track && (
                <div className="flex flex-wrap gap-2">
                    {can.manage && (
                        <Button
                            variant="outline"
                            onClick={() =>
                                run(
                                    'propose',
                                    `/operator/ppdb/seleksi/jalur/${track.public_id}/usulan`,
                                )
                            }
                            disabled={busy !== null}
                        >
                            {busy === 'propose' ? <Spinner /> : <Calculator />}
                            Hitung usulan (kuota {track.quota})
                        </Button>
                    )}
                    {can.decide && (
                        <Button
                            onClick={() =>
                                run(
                                    'approve',
                                    `/kepala-sekolah/ppdb/seleksi/jalur/${track.public_id}/setujui`,
                                )
                            }
                            disabled={busy !== null || undecided === 0}
                        >
                            {busy === 'approve' ? <Spinner /> : <CheckCheck />}
                            Setujui semua usulan ({undecided})
                        </Button>
                    )}
                </div>
            )}

            <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                {applicants.length === 0 ? (
                    <EmptyState
                        title="Belum ada pendaftar terverifikasi di jalur ini"
                        description="Pendaftar muncul di sini setelah diverifikasi."
                    />
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[44rem] text-sm">
                            <thead>
                                <tr className="border-b border-slate-200 bg-slate-50 text-left text-xs text-slate-500">
                                    <th className="px-4 py-2 font-medium">#</th>
                                    <th className="px-4 py-2 font-medium">
                                        Pendaftar
                                    </th>
                                    <th className="px-4 py-2 font-medium">
                                        Skor
                                    </th>
                                    <th className="px-4 py-2 font-medium">
                                        Usulan
                                    </th>
                                    <th className="px-4 py-2 font-medium">
                                        Status
                                    </th>
                                    {can.decide && (
                                        <th className="px-4 py-2 font-medium">
                                            Keputusan
                                        </th>
                                    )}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {applicants.map((a, i) => (
                                    <Fragment key={a.public_id}>
                                        {track && i === track.quota && (
                                            <tr>
                                                <td
                                                    colSpan={6}
                                                    className="bg-amber-50 px-4 py-1 text-xs font-medium text-amber-800"
                                                >
                                                    Batas kuota ({track.quota})
                                                </td>
                                            </tr>
                                        )}
                                        <Row
                                            index={i}
                                            applicant={a}
                                            detailBase={detailBase}
                                            can={can}
                                        />
                                    </Fragment>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </section>
        </AppLayout>
    );
}

function Row({
    index,
    applicant,
    detailBase,
    can,
}: {
    index: number;
    applicant: ApplicantRow;
    detailBase: string;
    can: Props['can'];
}) {
    const editable =
        can.manage &&
        ['VERIFIED', 'IN_SELECTION'].includes(applicant.status.value);
    const [score, setScore] = useState(
        applicant.selection?.score?.toString() ?? '',
    );
    const save = (patch: Record<string, string>) =>
        router.put(
            `/operator/ppdb/seleksi/pendaftar/${applicant.public_id}`,
            {
                score,
                note: applicant.selection?.note ?? '',
                proposal: applicant.selection?.proposal?.value ?? '',
                ...patch,
            },
            { preserveScroll: true, onError: toastFirstError },
        );

    return (
        <tr>
            <td className="px-4 py-2 text-slate-400 tabular-nums">
                {index + 1}
            </td>
            <td className="px-4 py-2">
                <Link
                    href={`${detailBase}/pendaftar/${applicant.public_id}`}
                    className="font-medium text-slate-900 hover:text-brand-700"
                >
                    {applicant.name}
                </Link>
                <p className="font-mono text-xs text-slate-500">
                    {applicant.registration_number}
                </p>
            </td>
            <td className="px-4 py-2">
                {editable ? (
                    <Input
                        type="number"
                        min={0}
                        max={100}
                        step="0.01"
                        value={score}
                        onChange={(e) => setScore(e.target.value)}
                        onBlur={() =>
                            score !==
                                (applicant.selection?.score?.toString() ??
                                    '') && save({ score })
                        }
                        className="h-8 w-24 tabular-nums"
                        aria-label={`Skor ${applicant.name}`}
                    />
                ) : (
                    <span className="tabular-nums">
                        {applicant.selection?.score ?? '—'}
                    </span>
                )}
            </td>
            <td className="px-4 py-2">
                {editable ? (
                    <NativeSelect
                        aria-label={`Usulan ${applicant.name}`}
                        value={applicant.selection?.proposal?.value ?? ''}
                        onChange={(e) => save({ proposal: e.target.value })}
                        className="h-8 w-40"
                    >
                        <option value="">Belum diusulkan</option>
                        <option value="DITERIMA">Usul diterima</option>
                        <option value="TIDAK">Usul tidak diterima</option>
                    </NativeSelect>
                ) : (
                    <span className="text-slate-600">
                        {applicant.selection?.proposal?.label ?? '—'}
                    </span>
                )}
            </td>
            <td className="px-4 py-2">
                <ApplicantStatusBadge status={applicant.status} />
            </td>
            {can.decide && (
                <td className="px-4 py-2">
                    {applicant.status.value === 'IN_SELECTION' ? (
                        <div className="flex gap-1">
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() =>
                                    post(
                                        `/kepala-sekolah/ppdb/seleksi/pendaftar/${applicant.public_id}/putuskan`,
                                        { decision: 'ACCEPTED' },
                                    )
                                }
                            >
                                Terima
                            </Button>
                            <Button
                                size="sm"
                                variant="outline"
                                className="text-rose-600"
                                onClick={() =>
                                    post(
                                        `/kepala-sekolah/ppdb/seleksi/pendaftar/${applicant.public_id}/putuskan`,
                                        { decision: 'REJECTED' },
                                    )
                                }
                            >
                                Tolak
                            </Button>
                        </div>
                    ) : (
                        <span className="text-xs text-slate-400">—</span>
                    )}
                </td>
            )}
        </tr>
    );
}
