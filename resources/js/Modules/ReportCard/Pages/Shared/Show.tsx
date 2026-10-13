import { Head, useForm } from '@inertiajs/react';
import { Download, Eye, History, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent, ReactNode } from 'react';
import InputError from '@/Components/input-error';
import { PageHeader } from '@/Components/shared/PageHeader';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Spinner } from '@/Components/ui/spinner';
import { Textarea } from '@/Components/ui/textarea';
import AppLayout from '@/Layouts/AppLayout';
import ParentLayout from '@/Layouts/ParentLayout';
import { formatDateTime } from '@/lib/format';
import { cn } from '@/lib/utils';
import { ReportStatusBadge } from '@/Modules/ReportCard/Components/ReportStatusBadge';
import { TransitionDialog } from '@/Modules/ReportCard/Components/TransitionDialog';
import type {
    ReportCardSummary,
    ReportStatus,
} from '@/Modules/ReportCard/types';

type Card = ReportCardSummary & {
    classroom: { public_id: string; name: string };
    homeroom: string | null;
    period: string;
    semester: string;
    homeroom_note: string | null;
    attendance: {
        sick: number;
        permit: number;
        absent: number;
        days: number;
    } | null;
    details: {
        subject: string;
        group: string;
        final_score: number | null;
        predicate: string | null;
        description: string | null;
    }[];
    extracurriculars: {
        name: string;
        predicate: string | null;
        note: string | null;
    }[];
    achievements: { name: string; note: string | null }[];
    logs: {
        from: ReportStatus | null;
        to: ReportStatus;
        actor: string;
        note: string | null;
        at: string | null;
    }[];
    transitions: ReportStatus[];
    canEdit: boolean;
};

type Props = { base: string; card: Card };

const GROUP_LABEL: Record<string, string> = {
    UMUM: 'Umum',
    PILIHAN: 'Pilihan',
    MUATAN_LOKAL: 'Muatan Lokal',
    KEPESANTRENAN: 'Kepesantrenan',
};

function Panel({
    title,
    children,
    className,
}: {
    title: string;
    children: ReactNode;
    className?: string;
}) {
    return (
        <section
            className={cn(
                'rounded-2xl border border-slate-200 bg-white p-5 shadow-sm',
                className,
            )}
        >
            <h2 className="mb-3 text-sm font-semibold text-slate-900">
                {title}
            </h2>
            {children}
        </section>
    );
}

export default function ReportCardShow({ base, card }: Props) {
    const isGuardian = base.startsWith('/orang-tua');
    const Layout = isGuardian ? ParentLayout : AppLayout;
    const [dialog, setDialog] = useState<{
        open: boolean;
        target: ReportStatus | null;
    }>({ open: false, target: null });
    const groups = card.details.reduce<Record<string, Card['details']>>(
        (acc, detail) => {
            (acc[detail.group] ??= []).push(detail);

            return acc;
        },
        {},
    );
    let number = 0;

    const body = (
        <>
            <Head title={`Raport ${card.student.name}`} />

            <PageHeader
                title={`Raport ${card.student.name}`}
                description={`${card.classroom.name} · ${card.period} · Wali kelas ${card.homeroom ?? '—'}`}
                actions={
                    <>
                        <Button variant="outline" asChild>
                            <a
                                href={`${base}/${card.public_id}/pdf`}
                                target="_blank"
                                rel="noopener"
                            >
                                <Eye />
                                Lihat PDF
                            </a>
                        </Button>
                        <Button variant="outline" asChild>
                            <a href={`${base}/${card.public_id}/pdf?unduh=1`}>
                                <Download />
                                Unduh
                            </a>
                        </Button>
                    </>
                }
            />

            <div className="mb-4 flex flex-wrap items-center gap-2">
                <ReportStatusBadge status={card.status} />
                {card.transitions.map((target) => (
                    <Button
                        key={target.value}
                        size="sm"
                        variant={
                            target.value === 'REVISION' ? 'outline' : 'default'
                        }
                        className={cn(
                            target.value === 'REVISION' &&
                                'text-amber-700 hover:bg-amber-50 hover:text-amber-800',
                        )}
                        onClick={() => setDialog({ open: true, target })}
                    >
                        {target.value === 'REVISION'
                            ? 'Kembalikan revisi'
                            : card.status.value === 'REVISION' &&
                                target.value === 'SUBMITTED'
                              ? 'Ajukan ulang'
                              : `Ubah ke "${target.label}"`}
                    </Button>
                ))}
            </div>

            <div className="grid gap-6 xl:grid-cols-3">
                <div className="min-w-0 space-y-6 xl:col-span-2">
                    <Panel title="Nilai akademik">
                        <div className="relative -mx-5 overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-slate-50 text-left text-xs font-semibold text-slate-600">
                                    <tr>
                                        <th className="px-5 py-2">
                                            Mata pelajaran
                                        </th>
                                        <th className="px-2 py-2 text-center">
                                            Nilai
                                        </th>
                                        <th className="px-2 py-2 text-center">
                                            Pred.
                                        </th>
                                        <th className="px-5 py-2">Capaian</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {Object.entries(groups).map(
                                        ([group, details]) => (
                                            <FragmentRows
                                                key={group}
                                                label={
                                                    GROUP_LABEL[group] ?? group
                                                }
                                            >
                                                {details.map((detail) => (
                                                    <tr
                                                        key={`${group}-${detail.subject}`}
                                                        className="border-t border-slate-100 align-top"
                                                    >
                                                        <td className="px-5 py-2 font-medium text-slate-900">
                                                            <span className="mr-2 text-xs text-slate-400 tabular-nums">
                                                                {++number}
                                                            </span>
                                                            {detail.subject}
                                                        </td>
                                                        <td className="px-2 py-2 text-center font-semibold tabular-nums">
                                                            {detail.final_score ??
                                                                '—'}
                                                        </td>
                                                        <td className="px-2 py-2 text-center">
                                                            {detail.predicate ??
                                                                '—'}
                                                        </td>
                                                        <td className="px-5 py-2 text-slate-600">
                                                            {detail.description ??
                                                                '—'}
                                                        </td>
                                                    </tr>
                                                ))}
                                            </FragmentRows>
                                        ),
                                    )}
                                </tbody>
                            </table>
                        </div>
                        {card.details.length === 0 && (
                            <p className="text-sm text-slate-500">
                                Belum ada nilai. Wali kelas perlu memperbarui
                                draf.
                            </p>
                        )}
                    </Panel>

                    {card.canEdit ? (
                        <ContentForm base={base} card={card} />
                    ) : (
                        <>
                            <Panel title="Catatan wali kelas">
                                <p className="text-sm whitespace-pre-line text-slate-700">
                                    {card.homeroom_note ?? '—'}
                                </p>
                            </Panel>
                            <div className="grid gap-6 md:grid-cols-2">
                                <Panel title="Ekstrakurikuler">
                                    <ul className="space-y-2 text-sm">
                                        {card.extracurriculars.length === 0 && (
                                            <li className="text-slate-500">
                                                —
                                            </li>
                                        )}
                                        {card.extracurriculars.map((row, i) => (
                                            <li key={i}>
                                                <span className="font-medium text-slate-900">
                                                    {row.name}
                                                </span>
                                                {row.predicate && (
                                                    <span className="text-slate-600">
                                                        {' '}
                                                        · {row.predicate}
                                                    </span>
                                                )}
                                                {row.note && (
                                                    <p className="text-xs text-slate-500">
                                                        {row.note}
                                                    </p>
                                                )}
                                            </li>
                                        ))}
                                    </ul>
                                </Panel>
                                <Panel title="Prestasi">
                                    <ul className="space-y-2 text-sm">
                                        {card.achievements.length === 0 && (
                                            <li className="text-slate-500">
                                                —
                                            </li>
                                        )}
                                        {card.achievements.map((row, i) => (
                                            <li key={i}>
                                                <span className="font-medium text-slate-900">
                                                    {row.name}
                                                </span>
                                                {row.note && (
                                                    <p className="text-xs text-slate-500">
                                                        {row.note}
                                                    </p>
                                                )}
                                            </li>
                                        ))}
                                    </ul>
                                </Panel>
                            </div>
                        </>
                    )}
                </div>

                <div className="min-w-0 space-y-6">
                    <Panel title="Ketidakhadiran (semester)">
                        <dl className="grid grid-cols-3 gap-2 text-center">
                            {[
                                ['Sakit', card.attendance?.sick],
                                ['Izin', card.attendance?.permit],
                                ['Tanpa ket.', card.attendance?.absent],
                            ].map(([label, value]) => (
                                <div
                                    key={label as string}
                                    className="rounded-xl bg-slate-50 p-3"
                                >
                                    <dt className="text-xs text-slate-500">
                                        {label}
                                    </dt>
                                    <dd className="text-lg font-semibold text-slate-900 tabular-nums">
                                        {value ?? 0}
                                    </dd>
                                </div>
                            ))}
                        </dl>
                    </Panel>

                    {!isGuardian && (
                        <Panel title="Riwayat">
                            <ol className="space-y-3">
                                {card.logs.length === 0 && (
                                    <li className="text-sm text-slate-500">
                                        Belum ada perpindahan tahap.
                                    </li>
                                )}
                                {card.logs.map((log, i) => (
                                    <li key={i} className="flex gap-3 text-sm">
                                        <History className="mt-0.5 size-4 shrink-0 text-slate-400" />
                                        <div className="min-w-0">
                                            <p className="text-slate-800">
                                                <span className="font-medium">
                                                    {log.to.label}
                                                </span>
                                                <span className="text-slate-500">
                                                    {' '}
                                                    · {log.actor}
                                                </span>
                                            </p>
                                            {log.at && (
                                                <p className="text-xs text-slate-400">
                                                    {formatDateTime(log.at)}
                                                </p>
                                            )}
                                            {log.note && (
                                                <p className="mt-1 rounded-lg bg-slate-50 px-2 py-1 text-xs text-slate-600">
                                                    {log.note}
                                                </p>
                                            )}
                                        </div>
                                    </li>
                                ))}
                            </ol>
                        </Panel>
                    )}
                </div>
            </div>

            <TransitionDialog
                open={dialog.open}
                onOpenChange={(open) =>
                    setDialog((current) => ({ ...current, open }))
                }
                target={dialog.target}
                url={`${base}/${card.public_id}/status`}
            />
        </>
    );

    return isGuardian ? (
        <Layout>{body}</Layout>
    ) : (
        <AppLayout
            breadcrumbs={[
                { title: 'Raport', href: base },
                {
                    title: card.classroom.name,
                    href: `${base}/rombel/${card.classroom.public_id}?semester=${card.semester}`,
                },
                { title: card.student.name, href: '#' },
            ]}
        >
            {body}
        </AppLayout>
    );
}

function FragmentRows({
    label,
    children,
}: {
    label: string;
    children: ReactNode;
}) {
    return (
        <>
            <tr className="border-t border-slate-200 bg-slate-50/70">
                <td
                    colSpan={4}
                    className="px-5 py-1.5 text-xs font-semibold text-slate-600"
                >
                    {label}
                </td>
            </tr>
            {children}
        </>
    );
}

function ContentForm({ base, card }: { base: string; card: Card }) {
    const form = useForm({
        homeroom_note: card.homeroom_note ?? '',
        extracurriculars: card.extracurriculars.map((row) => ({
            name: row.name,
            predicate: row.predicate ?? '',
            note: row.note ?? '',
        })),
        achievements: card.achievements.map((row) => ({
            name: row.name,
            note: row.note ?? '',
        })),
    });
    const error = (key: string) => (form.errors as Record<string, string>)[key];

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(`${base}/${card.public_id}/isi`, { preserveScroll: true });
    };

    return (
        <form onSubmit={submit} className="space-y-6">
            <Panel title="Catatan wali kelas">
                <Textarea
                    rows={4}
                    maxLength={1000}
                    value={form.data.homeroom_note}
                    onChange={(event) =>
                        form.setData('homeroom_note', event.target.value)
                    }
                    placeholder="Catatan perkembangan dan motivasi untuk siswa…"
                    aria-label="Catatan wali kelas"
                />
                <InputError message={form.errors.homeroom_note} />
            </Panel>

            <Panel title="Ekstrakurikuler">
                <div className="space-y-3">
                    {form.data.extracurriculars.map((row, i) => (
                        <div
                            key={i}
                            className="grid gap-2 sm:grid-cols-[1fr_7rem_1fr_auto]"
                        >
                            <div>
                                <Label
                                    className="sr-only"
                                    htmlFor={`ekskul-${i}`}
                                >
                                    Kegiatan
                                </Label>
                                <Input
                                    id={`ekskul-${i}`}
                                    placeholder="Pramuka"
                                    value={row.name}
                                    onChange={(event) =>
                                        form.setData(
                                            'extracurriculars',
                                            form.data.extracurriculars.map(
                                                (r, j) =>
                                                    j === i
                                                        ? {
                                                              ...r,
                                                              name: event.target
                                                                  .value,
                                                          }
                                                        : r,
                                            ),
                                        )
                                    }
                                />
                                <InputError
                                    message={error(
                                        `extracurriculars.${i}.name`,
                                    )}
                                />
                            </div>
                            <Input
                                aria-label="Predikat"
                                placeholder="Baik"
                                value={row.predicate}
                                onChange={(event) =>
                                    form.setData(
                                        'extracurriculars',
                                        form.data.extracurriculars.map(
                                            (r, j) =>
                                                j === i
                                                    ? {
                                                          ...r,
                                                          predicate:
                                                              event.target
                                                                  .value,
                                                      }
                                                    : r,
                                        ),
                                    )
                                }
                            />
                            <Input
                                aria-label="Keterangan"
                                placeholder="Keterangan"
                                value={row.note}
                                onChange={(event) =>
                                    form.setData(
                                        'extracurriculars',
                                        form.data.extracurriculars.map(
                                            (r, j) =>
                                                j === i
                                                    ? {
                                                          ...r,
                                                          note: event.target
                                                              .value,
                                                      }
                                                    : r,
                                        ),
                                    )
                                }
                            />
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                aria-label="Hapus baris"
                                onClick={() =>
                                    form.setData(
                                        'extracurriculars',
                                        form.data.extracurriculars.filter(
                                            (_, j) => j !== i,
                                        ),
                                    )
                                }
                            >
                                <Trash2 className="size-4" />
                            </Button>
                        </div>
                    ))}
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        disabled={form.data.extracurriculars.length >= 10}
                        onClick={() =>
                            form.setData('extracurriculars', [
                                ...form.data.extracurriculars,
                                { name: '', predicate: '', note: '' },
                            ])
                        }
                    >
                        <Plus />
                        Tambah kegiatan
                    </Button>
                </div>
            </Panel>

            <Panel title="Prestasi">
                <div className="space-y-3">
                    {form.data.achievements.map((row, i) => (
                        <div
                            key={i}
                            className="grid gap-2 sm:grid-cols-[1fr_1fr_auto]"
                        >
                            <div>
                                <Input
                                    aria-label="Prestasi"
                                    placeholder="Juara 2 Olimpiade Matematika tingkat kabupaten"
                                    value={row.name}
                                    onChange={(event) =>
                                        form.setData(
                                            'achievements',
                                            form.data.achievements.map(
                                                (r, j) =>
                                                    j === i
                                                        ? {
                                                              ...r,
                                                              name: event.target
                                                                  .value,
                                                          }
                                                        : r,
                                            ),
                                        )
                                    }
                                />
                                <InputError
                                    message={error(`achievements.${i}.name`)}
                                />
                            </div>
                            <Input
                                aria-label="Keterangan"
                                placeholder="Keterangan"
                                value={row.note}
                                onChange={(event) =>
                                    form.setData(
                                        'achievements',
                                        form.data.achievements.map((r, j) =>
                                            j === i
                                                ? {
                                                      ...r,
                                                      note: event.target.value,
                                                  }
                                                : r,
                                        ),
                                    )
                                }
                            />
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                aria-label="Hapus baris"
                                onClick={() =>
                                    form.setData(
                                        'achievements',
                                        form.data.achievements.filter(
                                            (_, j) => j !== i,
                                        ),
                                    )
                                }
                            >
                                <Trash2 className="size-4" />
                            </Button>
                        </div>
                    ))}
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        disabled={form.data.achievements.length >= 10}
                        onClick={() =>
                            form.setData('achievements', [
                                ...form.data.achievements,
                                { name: '', note: '' },
                            ])
                        }
                    >
                        <Plus />
                        Tambah prestasi
                    </Button>
                </div>
            </Panel>

            <div className="flex justify-end">
                <Button type="submit" disabled={form.processing}>
                    {form.processing && <Spinner />}
                    Simpan isi raport
                </Button>
            </div>
        </form>
    );
}
