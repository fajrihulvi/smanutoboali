import { Head, Link } from '@inertiajs/react';
import { CalendarRange, Pencil, Plus, Trash2 } from 'lucide-react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { PageHeader } from '@/Components/shared/PageHeader';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import { Button } from '@/Components/ui/button';
import { useConfirmDelete } from '@/hooks/use-confirm-delete';
import AppLayout from '@/Layouts/AppLayout';
import { formatDate } from '@/lib/format';
import type { PeriodRow } from '@/Modules/Ppdb/types';

const BASE = '/operator/ppdb/periode';

export default function PeriodsIndex({ periods }: { periods: PeriodRow[] }) {
    const { confirmDelete, dialog } = useConfirmDelete();

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'PPDB', href: '/operator/ppdb' },
                { title: 'Periode', href: BASE },
            ]}
        >
            <Head title="Periode PPDB" />
            <PageHeader
                title="Periode PPDB"
                description="Atur gelombang pendaftaran, jalur & kuota, serta dokumen wajib per jalur."
                actions={
                    <Button asChild>
                        <Link href={`${BASE}/tambah`}>
                            <Plus />
                            Tambah periode
                        </Link>
                    </Button>
                }
            />
            {periods.length === 0 ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState
                        icon={CalendarRange}
                        title="Belum ada periode"
                        description="Buat periode pendaftaran pertama."
                    />
                </div>
            ) : (
                <ul className="space-y-3">
                    {periods.map((period) => (
                        <li
                            key={period.public_id}
                            className="space-y-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                        >
                            <div className="flex flex-wrap items-start justify-between gap-2">
                                <div className="min-w-0">
                                    <p className="font-semibold text-slate-900">
                                        {period.name}
                                    </p>
                                    <p className="text-sm text-slate-500">
                                        TA {period.academic_year.name} · kelas{' '}
                                        {period.grade} ·{' '}
                                        {formatDate(
                                            period.opens_at,
                                            'd MMM yyyy',
                                        )}{' '}
                                        –{' '}
                                        {formatDate(
                                            period.closes_at,
                                            'd MMM yyyy',
                                        )}{' '}
                                        · {period.applicants_count} pendaftar
                                    </p>
                                </div>
                                <div className="flex items-center gap-2">
                                    <StatusBadge
                                        variant={
                                            period.is_open
                                                ? 'success'
                                                : period.is_active
                                                  ? 'info'
                                                  : 'neutral'
                                        }
                                        dot
                                    >
                                        {period.is_open
                                            ? 'Dibuka'
                                            : period.is_active
                                              ? 'Aktif (di luar jadwal)'
                                              : 'Nonaktif'}
                                    </StatusBadge>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        className="size-8"
                                        asChild
                                    >
                                        <Link
                                            href={`${BASE}/${period.public_id}/ubah`}
                                            aria-label={`Ubah ${period.name}`}
                                        >
                                            <Pencil className="size-4" />
                                        </Link>
                                    </Button>
                                    {period.applicants_count === 0 && (
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="size-8 text-slate-400 hover:bg-rose-50 hover:text-rose-600"
                                            aria-label={`Hapus ${period.name}`}
                                            onClick={() =>
                                                confirmDelete({
                                                    url: `${BASE}/${period.public_id}`,
                                                    title: `Hapus ${period.name}?`,
                                                })
                                            }
                                        >
                                            <Trash2 className="size-4" />
                                        </Button>
                                    )}
                                </div>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                {period.tracks.map((track) => (
                                    <span
                                        key={track.public_id}
                                        className="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-700"
                                    >
                                        {track.name} · kuota {track.quota} ·{' '}
                                        {track.requirements.length} dokumen
                                    </span>
                                ))}
                            </div>
                        </li>
                    ))}
                </ul>
            )}
            {dialog}
        </AppLayout>
    );
}
