import { Head, Link, useForm } from '@inertiajs/react';
import {
    AlertTriangle,
    Download,
    FilePen,
    Plus,
    Trash2,
    UserRoundPlus,
} from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import InputError from '@/Components/input-error';
import { EmptyState } from '@/Components/shared/EmptyState';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import { Button } from '@/Components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { Spinner } from '@/Components/ui/spinner';
import { useConfirmDelete } from '@/hooks/use-confirm-delete';
import { formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';
import ApplicantLayout from '@/Modules/Ppdb/Components/ApplicantLayout';
import { StatusStepper } from '@/Modules/Ppdb/Components/StatusStepper';
import type { ApplicantRow } from '@/Modules/Ppdb/types';
import type { StatusVariant } from '@/types';

type OpenPeriod = {
    public_id: string;
    name: string;
    academic_year: string;
    grade: number;
    closes_at: string;
    description: string | null;
    tracks: {
        public_id: string;
        name: string;
        quota: number;
        description: string | null;
    }[];
};

type Props = {
    applicants: ApplicantRow[];
    openPeriods: OpenPeriod[];
    autoStart: boolean;
};

const VARIANT: Record<ApplicantRow['status']['value'], StatusVariant> = {
    DRAFT: 'neutral',
    SUBMITTED: 'info',
    NEEDS_REVISION: 'warning',
    VERIFIED: 'brand',
    IN_SELECTION: 'brand',
    ACCEPTED: 'success',
    REJECTED: 'danger',
    RE_REGISTERED: 'success',
};

export default function ApplicantDashboard({
    applicants,
    openPeriods,
    autoStart,
}: Props) {
    const [open, setOpen] = useState(autoStart);
    const { confirmDelete, dialog } = useConfirmDelete();

    return (
        <ApplicantLayout>
            <Head title="Pendaftaran PPDB" />
            <div className="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 className="text-xl font-semibold text-slate-900">
                        Pendaftaran PPDB
                    </h1>
                    <p className="text-sm text-slate-500">
                        Pantau status pendaftaran calon siswa Anda.
                    </p>
                </div>
                {openPeriods.length > 0 && (
                    <Button onClick={() => setOpen(true)}>
                        <Plus />
                        Daftarkan calon siswa
                    </Button>
                )}
            </div>

            {openPeriods.length === 0 && (
                <p className="rounded-2xl border border-slate-200 bg-white p-4 text-sm text-slate-600 shadow-sm">
                    Saat ini belum ada periode pendaftaran yang dibuka.
                </p>
            )}

            {applicants.length === 0 ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState
                        icon={UserRoundPlus}
                        title="Belum ada pendaftaran"
                        description={
                            openPeriods.length > 0
                                ? 'Klik "Daftarkan calon siswa" untuk mulai mengisi formulir.'
                                : 'Tunggu periode pendaftaran dibuka.'
                        }
                    />
                </div>
            ) : (
                <ul className="space-y-4">
                    {applicants.map((applicant) => (
                        <li
                            key={applicant.public_id}
                            className="space-y-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5"
                        >
                            <div className="flex flex-wrap items-start justify-between gap-2">
                                <div className="min-w-0">
                                    <p className="font-semibold text-slate-900">
                                        {applicant.name ??
                                            'Calon siswa (belum diisi)'}
                                    </p>
                                    <p className="text-sm text-slate-500">
                                        {applicant.period.name} · Jalur{' '}
                                        {applicant.track.name}
                                        {applicant.registration_number &&
                                            ` · ${applicant.registration_number}`}
                                    </p>
                                </div>
                                <StatusBadge
                                    variant={VARIANT[applicant.status.value]}
                                    dot
                                >
                                    {applicant.status.label}
                                </StatusBadge>
                            </div>
                            <StatusStepper applicant={applicant} />
                            {applicant.status.value === 'NEEDS_REVISION' &&
                                applicant.revision_note && (
                                    <p className="flex items-start gap-2 rounded-xl bg-amber-50 p-3 text-sm text-amber-900">
                                        <AlertTriangle className="mt-0.5 size-4 shrink-0" />
                                        <span>
                                            <strong>Perlu perbaikan:</strong>{' '}
                                            {applicant.revision_note}
                                        </span>
                                    </p>
                                )}
                            {applicant.period.announces_at &&
                                [
                                    'SUBMITTED',
                                    'VERIFIED',
                                    'IN_SELECTION',
                                ].includes(applicant.status.value) && (
                                    <p className="text-xs text-slate-500">
                                        Pengumuman hasil:{' '}
                                        {formatDate(
                                            applicant.period.announces_at,
                                            'd MMMM yyyy',
                                        )}
                                    </p>
                                )}
                            <div className="flex flex-wrap gap-2">
                                <Button
                                    size="sm"
                                    variant={
                                        applicant.can.update
                                            ? 'default'
                                            : 'outline'
                                    }
                                    asChild
                                >
                                    <Link
                                        href={`/ppdb/akun/pendaftaran/${applicant.public_id}`}
                                    >
                                        <FilePen />
                                        {applicant.can.update
                                            ? applicant.status.value === 'DRAFT'
                                                ? 'Lanjutkan formulir'
                                                : 'Perbaiki formulir'
                                            : 'Lihat data'}
                                    </Link>
                                </Button>
                                {applicant.registration_number && (
                                    <Button size="sm" variant="outline" asChild>
                                        <a
                                            href={`/ppdb/akun/pendaftaran/${applicant.public_id}/bukti.pdf`}
                                        >
                                            <Download />
                                            Bukti pendaftaran
                                        </a>
                                    </Button>
                                )}
                                {applicant.can.delete && (
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        className="text-rose-600 hover:bg-rose-50 hover:text-rose-700"
                                        onClick={() =>
                                            confirmDelete({
                                                url: `/ppdb/akun/pendaftaran/${applicant.public_id}`,
                                                title: 'Hapus draf pendaftaran?',
                                                description:
                                                    'Data dan dokumen yang sudah diisi akan dihapus.',
                                            })
                                        }
                                    >
                                        <Trash2 />
                                        Hapus draf
                                    </Button>
                                )}
                            </div>
                        </li>
                    ))}
                </ul>
            )}

            <StartDialog
                open={open}
                onOpenChange={setOpen}
                periods={openPeriods}
            />
            {dialog}
        </ApplicantLayout>
    );
}

function StartDialog({
    open,
    onOpenChange,
    periods,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    periods: OpenPeriod[];
}) {
    const form = useForm({ track: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/ppdb/akun/pendaftaran', {
            onSuccess: () => onOpenChange(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90dvh] overflow-y-auto sm:max-w-lg">
                <form onSubmit={submit} className="space-y-4">
                    <DialogHeader>
                        <DialogTitle>Pilih periode & jalur</DialogTitle>
                        <DialogDescription>
                            Setiap calon siswa didaftarkan terpisah.
                        </DialogDescription>
                    </DialogHeader>
                    {periods.map((period) => (
                        <fieldset key={period.public_id} className="space-y-2">
                            <legend className="text-sm font-semibold text-slate-900">
                                {period.name}
                                <span className="block text-xs font-normal text-slate-500">
                                    TA {period.academic_year} · kelas{' '}
                                    {period.grade} · ditutup{' '}
                                    {formatDate(
                                        period.closes_at,
                                        'd MMM yyyy, HH.mm',
                                    )}
                                </span>
                            </legend>
                            {period.tracks.map((track) => (
                                <label
                                    key={track.public_id}
                                    className={cn(
                                        'block cursor-pointer rounded-xl border p-3 text-sm transition-colors duration-150',
                                        form.data.track === track.public_id
                                            ? 'border-brand-600 bg-brand-50'
                                            : 'border-slate-200 hover:border-brand-300',
                                    )}
                                >
                                    <input
                                        type="radio"
                                        name="track"
                                        className="sr-only"
                                        checked={
                                            form.data.track === track.public_id
                                        }
                                        onChange={() =>
                                            form.setData(
                                                'track',
                                                track.public_id,
                                            )
                                        }
                                    />
                                    <span className="block font-medium text-slate-900">
                                        Jalur {track.name}{' '}
                                        <span className="font-normal text-slate-500">
                                            · kuota {track.quota}
                                        </span>
                                    </span>
                                    {track.description && (
                                        <span className="block text-xs text-slate-500">
                                            {track.description}
                                        </span>
                                    )}
                                </label>
                            ))}
                        </fieldset>
                    ))}
                    <InputError message={form.errors.track} />
                    <DialogFooter className="gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                        >
                            Batal
                        </Button>
                        <Button
                            type="submit"
                            disabled={!form.data.track || form.processing}
                        >
                            {form.processing && <Spinner />}
                            Mulai isi formulir
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
