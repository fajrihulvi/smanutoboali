import { Head, Link, router } from '@inertiajs/react';
import {
    CheckCircle2,
    CircleDashed,
    Download,
    FileDown,
    FileText,
    Printer,
    RefreshCw,
    Undo2,
    Users,
} from 'lucide-react';
import { useState } from 'react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { PageHeader } from '@/Components/shared/PageHeader';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import AppLayout from '@/Layouts/AppLayout';
import { formatDateTime } from '@/lib/format';
import type { Option } from '@/Modules/Academic/types';
import { SemesterSelect } from '@/Modules/Assessment/Components/SemesterSelect';
import { ReportStatusBadge } from '@/Modules/ReportCard/Components/ReportStatusBadge';
import { StatusCounts } from '@/Modules/ReportCard/Components/StatusCounts';
import { TransitionDialog } from '@/Modules/ReportCard/Components/TransitionDialog';
import type {
    ReportCardSummary,
    ReportStatus,
    ReportStatusValue,
    StatusCounts as Counts,
    SubjectConfirmation,
} from '@/Modules/ReportCard/types';

type Role = 'homeroom' | 'operator' | 'principal';

type Props = {
    base: string;
    role: Role;
    classroom: { public_id: string; name: string };
    semester: string;
    semesters: Option[];
    cards: ReportCardSummary[];
    counts: Counts;
    confirmations: SubjectConfirmation[];
    exports: {
        public_id: string;
        status: string;
        total: number;
        error: string | null;
        created_at: string | null;
        url: string | null;
    }[];
};

type BulkAction = {
    from: ReportStatusValue[];
    target: ReportStatus;
    label: string;
};

const ACTIONS: Record<Role, BulkAction[]> = {
    homeroom: [
        {
            from: ['SUBMITTED'],
            target: {
                value: 'HOMEROOM_REVIEWED',
                label: 'Direview wali kelas',
            },
            label: 'Tandai sudah direview',
        },
    ],
    operator: [
        {
            from: ['HOMEROOM_REVIEWED'],
            target: { value: 'VALIDATED', label: 'Divalidasi operator' },
            label: 'Validasi',
        },
        {
            from: ['APPROVED'],
            target: { value: 'PUBLISHED', label: 'Terbit' },
            label: 'Terbitkan ke orang tua',
        },
    ],
    principal: [
        {
            from: ['VALIDATED'],
            target: { value: 'APPROVED', label: 'Disetujui kepala sekolah' },
            label: 'Setujui',
        },
    ],
};

/** Status yang boleh dikembalikan ke revisi oleh peran ini. */
const REVISABLE: Record<Role, ReportStatusValue[]> = {
    homeroom: ['SUBMITTED'],
    operator: ['HOMEROOM_REVIEWED', 'APPROVED', 'PUBLISHED'],
    principal: ['VALIDATED', 'APPROVED'],
};

export default function ReportCardClassroom({
    base,
    role,
    classroom,
    semester,
    semesters,
    cards,
    counts,
    confirmations,
    exports,
}: Props) {
    const [selected, setSelected] = useState<Set<string>>(new Set());
    const [dialog, setDialog] = useState<{
        open: boolean;
        target: ReportStatus | null;
        cards: string[];
    }>({
        open: false,
        target: null,
        cards: [],
    });
    const q = `?semester=${semester}`;
    const confirmed = confirmations.filter((row) => row.confirmed_at).length;

    const eligible = (from: ReportStatusValue[]) => {
        const pool =
            selected.size > 0
                ? cards.filter((card) => selected.has(card.public_id))
                : cards;

        return pool
            .filter((card) => from.includes(card.status.value))
            .map((card) => card.public_id);
    };

    const openBulk = (target: ReportStatus, ids: string[]) =>
        setDialog({ open: true, target, cards: ids });

    const toggle = (id: string, checked: boolean) =>
        setSelected((current) => {
            const next = new Set(current);

            if (checked) {
                next.add(id);
            } else {
                next.delete(id);
            }

            return next;
        });

    const revisable = eligible(REVISABLE[role]);

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Raport', href: `${base}${q}` },
                { title: classroom.name, href: '#' },
            ]}
        >
            <Head title={`Raport ${classroom.name}`} />

            <PageHeader
                title={`Raport ${classroom.name}`}
                description={
                    selected.size > 0
                        ? `${selected.size} raport dipilih — aksi hanya untuk yang dipilih.`
                        : 'Aksi massal berlaku untuk semua raport yang statusnya sesuai.'
                }
                actions={
                    <SemesterSelect semesters={semesters} value={semester} />
                }
            />

            <div className="mb-4 flex flex-wrap items-center gap-2">
                {role === 'homeroom' && (
                    <Button
                        variant="outline"
                        onClick={() =>
                            router.post(
                                `${base}/rombel/${classroom.public_id}/generate${q}`,
                                {},
                                { preserveScroll: true },
                            )
                        }
                    >
                        <RefreshCw />
                        {cards.length === 0
                            ? 'Buat draf raport'
                            : 'Perbarui draf'}
                    </Button>
                )}
                {ACTIONS[role].map((action) => {
                    const ids = eligible(action.from);

                    return (
                        <Button
                            key={action.target.value}
                            disabled={ids.length === 0}
                            onClick={() => openBulk(action.target, ids)}
                        >
                            {action.label} ({ids.length})
                        </Button>
                    );
                })}
                <Button
                    variant="outline"
                    className="text-amber-700 hover:bg-amber-50 hover:text-amber-800"
                    disabled={revisable.length === 0}
                    onClick={() =>
                        openBulk(
                            { value: 'REVISION', label: 'Perlu revisi' },
                            revisable,
                        )
                    }
                >
                    <Undo2 />
                    Kembalikan revisi ({revisable.length})
                </Button>
                {role === 'operator' && (
                    <Button
                        variant="outline"
                        className="ml-auto"
                        disabled={cards.length === 0}
                        onClick={() =>
                            router.post(
                                `${base}/rombel/${classroom.public_id}/cetak${q}`,
                                {},
                                { preserveScroll: true },
                            )
                        }
                    >
                        <Printer />
                        Cetak massal (ZIP)
                    </Button>
                )}
            </div>

            <div className="mb-4">
                <StatusCounts counts={counts} />
            </div>

            <div className="grid gap-6 xl:grid-cols-3">
                <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm xl:col-span-2">
                    {cards.length === 0 ? (
                        <EmptyState
                            icon={Users}
                            title="Belum ada raport"
                            description={
                                role === 'homeroom'
                                    ? 'Buat draf raport dari nilai & presensi semester ini.'
                                    : 'Wali kelas belum membuat draf raport.'
                            }
                        />
                    ) : (
                        <ul className="divide-y divide-slate-100">
                            {cards.map((card) => (
                                <li
                                    key={card.public_id}
                                    className="flex flex-wrap items-center gap-3 px-4 py-3"
                                >
                                    <Checkbox
                                        checked={selected.has(card.public_id)}
                                        onCheckedChange={(checked) =>
                                            toggle(
                                                card.public_id,
                                                checked === true,
                                            )
                                        }
                                        aria-label={`Pilih ${card.student.name}`}
                                    />
                                    <Link
                                        href={`${base}/${card.public_id}`}
                                        className="min-w-0 flex-1 basis-40"
                                    >
                                        <p className="truncate font-medium text-slate-900 hover:text-brand-700">
                                            {card.student.name}
                                        </p>
                                        <p className="text-xs text-slate-500 tabular-nums">
                                            NIS {card.student.nis}
                                            {!card.has_note &&
                                                ' · catatan wali belum diisi'}
                                        </p>
                                    </Link>
                                    <ReportStatusBadge status={card.status} />
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        className="size-8"
                                        asChild
                                    >
                                        <a
                                            href={`${base}/${card.public_id}/pdf`}
                                            target="_blank"
                                            rel="noopener"
                                            aria-label={`PDF ${card.student.name}`}
                                        >
                                            <FileText className="size-4" />
                                        </a>
                                    </Button>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                <aside className="space-y-6">
                    <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 className="text-sm font-semibold text-slate-900">
                            Konfirmasi mapel{' '}
                            <span className="font-normal text-slate-500 tabular-nums">
                                {confirmed}/{confirmations.length}
                            </span>
                        </h2>
                        <ul className="mt-3 space-y-2">
                            {confirmations.map((row) => (
                                <li
                                    key={row.assignment}
                                    className="flex items-start gap-2 text-sm"
                                >
                                    {row.confirmed_at ? (
                                        <CheckCircle2 className="mt-0.5 size-4 shrink-0 text-emerald-500" />
                                    ) : (
                                        <CircleDashed className="mt-0.5 size-4 shrink-0 text-slate-300" />
                                    )}
                                    <span className="min-w-0">
                                        <span className="block text-slate-800">
                                            {row.subject}
                                        </span>
                                        <span className="block text-xs text-slate-500">
                                            {row.teacher}
                                        </span>
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </section>

                    {role === 'operator' && exports.length > 0 && (
                        <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <h2 className="mb-3 text-sm font-semibold text-slate-900">
                                Cetak massal (7 hari terakhir)
                            </h2>
                            <ul className="space-y-2 text-sm">
                                {exports.map((row) => (
                                    <li
                                        key={row.public_id}
                                        className="flex items-center justify-between gap-2"
                                    >
                                        <span className="text-slate-600">
                                            {row.created_at &&
                                                formatDateTime(row.created_at)}
                                        </span>
                                        {row.url ? (
                                            <a
                                                href={row.url}
                                                className="flex items-center gap-1 font-medium text-brand-700 hover:underline"
                                            >
                                                <Download className="size-4" />{' '}
                                                ZIP ({row.total})
                                            </a>
                                        ) : (
                                            <StatusBadge
                                                variant={
                                                    row.status === 'GAGAL'
                                                        ? 'danger'
                                                        : 'neutral'
                                                }
                                            >
                                                {row.status === 'GAGAL'
                                                    ? 'Gagal'
                                                    : row.status === 'DIPROSES'
                                                      ? 'Diproses…'
                                                      : 'Menunggu antrean'}
                                            </StatusBadge>
                                        )}
                                    </li>
                                ))}
                            </ul>
                            {exports.some(
                                (row) => !row.url && row.status !== 'GAGAL',
                            ) && (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    className="mt-3"
                                    onClick={() =>
                                        router.reload({ only: ['exports'] })
                                    }
                                >
                                    <FileDown />
                                    Periksa lagi
                                </Button>
                            )}
                        </section>
                    )}
                </aside>
            </div>

            <TransitionDialog
                open={dialog.open}
                onOpenChange={(open) =>
                    setDialog((current) => ({ ...current, open }))
                }
                target={dialog.target}
                url={`${base}/rombel/${classroom.public_id}/status`}
                cards={dialog.cards}
            />
        </AppLayout>
    );
}
