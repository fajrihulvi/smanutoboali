import { Head, router, useForm } from '@inertiajs/react';
import {
    CheckCircle2,
    FileText,
    RotateCcw,
    UserCheck,
    XCircle,
} from 'lucide-react';
import { useState } from 'react';
import type { FormEvent, ReactNode } from 'react';
import InputError from '@/Components/input-error';
import { PageHeader } from '@/Components/shared/PageHeader';
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
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Spinner } from '@/Components/ui/spinner';
import { Textarea } from '@/Components/ui/textarea';
import AppLayout from '@/Layouts/AppLayout';
import { formatDate, formatDateTime, formatFileSize } from '@/lib/format';
import { toastFirstError } from '@/lib/toast-errors';
import { NativeSelect } from '@/Modules/Academic/Components/NativeSelect';
import type { Option } from '@/Modules/Academic/types';
import { RupiahInput } from '@/Modules/Finance/Components/RupiahInput';
import { ApplicantStatusBadge } from '@/Modules/Ppdb/Components/ApplicantStatusBadge';
import type {
    ApplicantDetail,
    FormOptions,
    Requirement,
} from '@/Modules/Ppdb/types';

type Props = {
    base: string;
    applicant: ApplicantDetail;
    account: { name: string; email: string };
    canManage: boolean;
    options: FormOptions;
    reregistration: {
        suggested_nis: string;
        entry_date: string;
        published: boolean;
        fee_types: (Option & { amount: number })[];
    } | null;
};

function Card({
    title,
    actions,
    children,
}: {
    title: string;
    actions?: ReactNode;
    children: ReactNode;
}) {
    return (
        <section className="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <header className="flex flex-wrap items-center justify-between gap-2">
                <h2 className="text-sm font-semibold text-slate-900">
                    {title}
                </h2>
                {actions}
            </header>
            {children}
        </section>
    );
}

function Item({
    label,
    children,
    wide = false,
}: {
    label: string;
    children: ReactNode;
    wide?: boolean;
}) {
    return (
        <div className={wide ? 'sm:col-span-2' : undefined}>
            <dt className="text-xs text-slate-500">{label}</dt>
            <dd className="break-words text-slate-900">{children || '—'}</dd>
        </div>
    );
}

export default function ApplicantShow({
    base,
    applicant,
    account,
    canManage,
    options,
    reregistration,
}: Props) {
    const [revising, setRevising] = useState(false);
    const [busy, setBusy] = useState(false);
    const s = applicant.student;
    const label = (list: Option[], value: string | null) =>
        list.find((o) => o.value === value)?.label ?? '—';
    const reviewable =
        canManage && ['SUBMITTED', 'VERIFIED'].includes(applicant.status.value);

    const verify = () => {
        setBusy(true);
        router.post(
            `/operator/ppdb/pendaftar/${applicant.public_id}/verifikasi`,
            {},
            {
                preserveScroll: true,
                onError: toastFirstError,
                onFinish: () => setBusy(false),
            },
        );
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'PPDB', href: `${base}/dashboard` },
                { title: 'Pendaftar', href: `${base}/pendaftar` },
                { title: applicant.registration_number ?? '', href: '#' },
            ]}
        >
            <Head title={`Pendaftar ${applicant.registration_number}`} />
            <PageHeader
                title={applicant.name ?? 'Pendaftar'}
                description={`${applicant.registration_number} · ${applicant.period.name} · Jalur ${applicant.track.name} · dikirim ${formatDateTime(applicant.submitted_at)}`}
                actions={
                    reviewable && (
                        <>
                            <Button
                                variant="outline"
                                onClick={() => setRevising(true)}
                            >
                                <RotateCcw />
                                Kembalikan untuk perbaikan
                            </Button>
                            {applicant.status.value === 'SUBMITTED' && (
                                <Button onClick={verify} disabled={busy}>
                                    {busy ? <Spinner /> : <UserCheck />}
                                    Verifikasi
                                </Button>
                            )}
                        </>
                    )
                }
            />

            <div className="grid gap-6 lg:grid-cols-5">
                <div className="min-w-0 space-y-6 lg:col-span-3">
                    <Card
                        title="Data calon siswa"
                        actions={
                            <ApplicantStatusBadge status={applicant.status} />
                        }
                    >
                        <dl className="grid gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
                            <Item label="NIK / NISN">{`${s.nik ?? '—'} / ${s.nisn ?? '—'}`}</Item>
                            <Item label="Jenis kelamin">
                                {label(options.genders, s.gender)}
                            </Item>
                            <Item label="Tempat, tanggal lahir">{`${s.birth_place ?? '—'}, ${s.birth_date ? formatDate(s.birth_date, 'd MMMM yyyy') : '—'}`}</Item>
                            <Item label="Agama">
                                {label(options.religions, s.religion)}
                            </Item>
                            <Item label="Asal sekolah">
                                {s.previous_school}
                            </Item>
                            <Item label="Akun pendaftar">{`${account.name} · ${account.email}`}</Item>
                            <Item label="Alamat" wide>
                                {s.address}
                            </Item>
                        </dl>
                    </Card>
                    <Card title="Orang tua / wali">
                        <ul className="space-y-2 text-sm">
                            {applicant.guardians.map((g) => (
                                <li key={g.relationship}>
                                    <span className="font-medium text-slate-900">
                                        {label(
                                            options.relationships,
                                            g.relationship,
                                        )}
                                        {g.is_primary && ' (kontak utama)'}:
                                    </span>{' '}
                                    {g.name} · {g.phone ?? '—'}
                                    {g.occupation && ` · ${g.occupation}`}
                                    {g.email && ` · ${g.email}`}
                                </li>
                            ))}
                        </ul>
                    </Card>
                    <Card title="Dokumen">
                        <ul className="space-y-3">
                            {applicant.requirements.map((r) => (
                                <DocumentReview
                                    key={r.public_id}
                                    requirement={r}
                                    reviewable={reviewable}
                                />
                            ))}
                        </ul>
                    </Card>
                </div>

                <aside className="min-w-0 space-y-6 lg:col-span-2">
                    {applicant.revision_note &&
                        applicant.status.value === 'NEEDS_REVISION' && (
                            <Card title="Catatan perbaikan">
                                <p className="text-sm whitespace-pre-wrap text-amber-900">
                                    {applicant.revision_note}
                                </p>
                            </Card>
                        )}
                    {applicant.selection && (
                        <Card title="Seleksi">
                            <dl className="grid grid-cols-2 gap-3 text-sm">
                                <Item label="Skor">
                                    {applicant.selection.score?.toString()}
                                </Item>
                                <Item label="Usulan">
                                    {applicant.selection.proposal?.label}
                                </Item>
                                <Item label="Diputuskan">
                                    {applicant.selection.decided_at
                                        ? formatDateTime(
                                              applicant.selection.decided_at,
                                          )
                                        : ''}
                                </Item>
                                <Item label="Diumumkan ke pendaftar">
                                    {applicant.selection.result_notified_at
                                        ? formatDateTime(
                                              applicant.selection
                                                  .result_notified_at,
                                          )
                                        : ''}
                                </Item>
                                {applicant.selection.note && (
                                    <Item label="Catatan" wide>
                                        {applicant.selection.note}
                                    </Item>
                                )}
                            </dl>
                        </Card>
                    )}
                    {applicant.status.value === 'RE_REGISTERED' && (
                        <Card title="Daftar ulang">
                            <p className="flex items-start gap-2 text-sm text-emerald-800">
                                <CheckCircle2 className="mt-0.5 size-4 shrink-0" />
                                Selesai{' '}
                                {formatDateTime(applicant.reregistered_at)}.
                                Data siswa & orang tua sudah dibuat.
                            </p>
                        </Card>
                    )}
                    {reregistration && (
                        <ReregistrationCard
                            applicant={applicant}
                            data={reregistration}
                        />
                    )}
                </aside>
            </div>

            <ReviseDialog
                open={revising}
                onOpenChange={setRevising}
                applicant={applicant}
            />
        </AppLayout>
    );
}

function DocumentReview({
    requirement,
    reviewable,
}: {
    requirement: Requirement;
    reviewable: boolean;
}) {
    const doc = requirement.document;
    const [rejecting, setRejecting] = useState(false);
    const form = useForm({ status: 'INVALID', note: doc?.note ?? '' });
    const url = doc ? `/operator/ppdb/dokumen/${doc.public_id}/periksa` : '';

    const markValid = () =>
        router.post(
            url,
            { status: 'VALID' },
            { preserveScroll: true, onError: toastFirstError },
        );
    const submitInvalid = (event: FormEvent) => {
        event.preventDefault();
        form.post(url, {
            preserveScroll: true,
            onSuccess: () => setRejecting(false),
        });
    };

    return (
        <li className="space-y-2 rounded-xl border border-slate-200 p-3">
            <div className="flex flex-wrap items-start justify-between gap-2">
                <p className="text-sm font-medium text-slate-900">
                    {requirement.name}
                    {requirement.is_required ? (
                        <span className="text-rose-600"> *</span>
                    ) : (
                        <span className="text-xs font-normal text-slate-500">
                            {' '}
                            (opsional)
                        </span>
                    )}
                </p>
                {doc ? (
                    <StatusBadge
                        variant={
                            doc.status.value === 'VALID'
                                ? 'success'
                                : doc.status.value === 'INVALID'
                                  ? 'danger'
                                  : 'neutral'
                        }
                        dot
                    >
                        {doc.status.label}
                    </StatusBadge>
                ) : (
                    <StatusBadge variant="neutral">Tidak diunggah</StatusBadge>
                )}
            </div>
            {doc && (
                <>
                    {doc.file.is_image ? (
                        <a
                            href={doc.file.url}
                            target="_blank"
                            rel="noreferrer"
                            className="block w-fit overflow-hidden rounded-lg border border-slate-200"
                        >
                            <img
                                src={doc.file.url}
                                alt={requirement.name}
                                loading="lazy"
                                className="max-h-48 object-contain"
                            />
                        </a>
                    ) : (
                        <a
                            href={doc.file.url}
                            target="_blank"
                            rel="noreferrer"
                            className="flex items-center gap-1 text-sm text-brand-600 hover:text-brand-700"
                        >
                            <FileText className="size-4" />
                            {doc.file.name} ({formatFileSize(doc.file.size)})
                        </a>
                    )}
                    {doc.note && (
                        <p className="rounded-lg bg-rose-50 px-2 py-1 text-xs text-rose-700">
                            {doc.note}
                        </p>
                    )}
                    {reviewable && (
                        <div className="flex flex-wrap gap-2">
                            <Button
                                size="sm"
                                variant={
                                    doc.status.value === 'VALID'
                                        ? 'default'
                                        : 'outline'
                                }
                                onClick={markValid}
                            >
                                <CheckCircle2 />
                                Valid
                            </Button>
                            <Button
                                size="sm"
                                variant="outline"
                                className="text-rose-600 hover:bg-rose-50 hover:text-rose-700"
                                onClick={() => setRejecting(true)}
                            >
                                <XCircle />
                                Tidak valid
                            </Button>
                        </div>
                    )}
                </>
            )}
            <Dialog open={rejecting} onOpenChange={setRejecting}>
                <DialogContent className="sm:max-w-md">
                    <form onSubmit={submitInvalid} className="space-y-4">
                        <DialogHeader>
                            <DialogTitle>
                                {requirement.name} tidak valid
                            </DialogTitle>
                            <DialogDescription>
                                Alasan ini ditampilkan ke pendaftar.
                            </DialogDescription>
                        </DialogHeader>
                        <Textarea
                            rows={3}
                            maxLength={255}
                            value={form.data.note}
                            onChange={(e) =>
                                form.setData('note', e.target.value)
                            }
                            placeholder="Mis. foto buram, halaman terpotong"
                            aria-label="Alasan"
                        />
                        <InputError
                            message={
                                form.errors.note ??
                                (form.errors as Record<string, string>).document
                            }
                        />
                        <DialogFooter className="gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setRejecting(false)}
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                variant="destructive"
                                disabled={form.processing}
                            >
                                {form.processing && <Spinner />}
                                Simpan
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </li>
    );
}

function ReviseDialog({
    open,
    onOpenChange,
    applicant,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    applicant: ApplicantDetail;
}) {
    const invalid = applicant.requirements
        .filter((r) => r.document?.status.value === 'INVALID')
        .map((r) => `- ${r.name}: ${r.document?.note ?? ''}`);
    const missing = applicant.requirements
        .filter((r) => r.is_required && !r.document)
        .map((r) => `- ${r.name}: belum diunggah`);
    const form = useForm({ note: [...invalid, ...missing].join('\n') });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(`/operator/ppdb/pendaftar/${applicant.public_id}/perbaikan`, {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-lg">
                <form onSubmit={submit} className="space-y-4">
                    <DialogHeader>
                        <DialogTitle>Kembalikan untuk perbaikan</DialogTitle>
                        <DialogDescription>
                            Pendaftar diberi tahu lewat email dan portal, lalu
                            bisa mengirim ulang.
                        </DialogDescription>
                    </DialogHeader>
                    <Textarea
                        rows={6}
                        maxLength={2000}
                        value={form.data.note}
                        onChange={(e) => form.setData('note', e.target.value)}
                        aria-label="Catatan perbaikan"
                    />
                    <InputError
                        message={
                            form.errors.note ??
                            (form.errors as Record<string, string>).status
                        }
                    />
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
                            disabled={
                                form.processing || form.data.note.trim() === ''
                            }
                        >
                            {form.processing && <Spinner />}
                            Kembalikan
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function ReregistrationCard({
    applicant,
    data,
}: {
    applicant: ApplicantDetail;
    data: NonNullable<Props['reregistration']>;
}) {
    const form = useForm({
        nis: data.suggested_nis,
        entry_date: data.entry_date,
        fee_type: '',
        fee_amount: '' as number | '',
        fee_due_date: data.entry_date,
    });
    const errors = form.errors as Record<string, string | undefined>;

    const pickFee = (value: string) =>
        form.setData((d) => ({
            ...d,
            fee_type: value,
            fee_amount:
                data.fee_types.find((f) => f.value === value)?.amount ?? '',
        }));

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.transform((d) =>
            d.fee_type ? d : { nis: d.nis, entry_date: d.entry_date },
        );
        form.post(
            `/operator/ppdb/pendaftar/${applicant.public_id}/daftar-ulang`,
            { preserveScroll: true },
        );
    };

    if (!data.published) {
        return (
            <Card title="Daftar ulang">
                <p className="text-sm text-slate-500">
                    Umumkan hasil seleksi dulu (menu Seleksi) sebelum konfirmasi
                    daftar ulang.
                </p>
            </Card>
        );
    }

    return (
        <Card title="Konfirmasi daftar ulang">
            <p className="text-sm text-slate-500">
                {applicant.reregistration_submitted_at
                    ? `Pendaftar mengirim data daftar ulang ${formatDateTime(applicant.reregistration_submitted_at)}.`
                    : 'Pendaftar belum mengirim data daftar ulang dari portal; konfirmasi tetap bisa dilakukan bila daftar ulang di sekolah.'}{' '}
                Konfirmasi akan membuat data siswa & orang tua dan mengubah akun
                pendaftar menjadi akun orang tua.
            </p>
            <form onSubmit={submit} className="space-y-4">
                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="grid gap-2">
                        <Label htmlFor="rr-nis">NIS</Label>
                        <Input
                            id="rr-nis"
                            maxLength={20}
                            value={form.data.nis}
                            onChange={(e) =>
                                form.setData('nis', e.target.value)
                            }
                            aria-invalid={!!errors.nis}
                        />
                        <InputError message={errors.nis ?? errors.status} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="rr-entry">Tanggal masuk</Label>
                        <Input
                            id="rr-entry"
                            type="date"
                            value={form.data.entry_date}
                            onChange={(e) =>
                                form.setData('entry_date', e.target.value)
                            }
                        />
                        <InputError message={errors.entry_date} />
                    </div>
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="rr-fee">
                        Tagihan daftar ulang (opsional)
                    </Label>
                    <NativeSelect
                        id="rr-fee"
                        value={form.data.fee_type}
                        onChange={(e) => pickFee(e.target.value)}
                    >
                        <option value="">Tanpa tagihan</option>
                        {data.fee_types.map((f) => (
                            <option key={f.value} value={f.value}>
                                {f.label}
                            </option>
                        ))}
                    </NativeSelect>
                </div>
                {form.data.fee_type && (
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="rr-amount">Nominal</Label>
                            <RupiahInput
                                id="rr-amount"
                                value={form.data.fee_amount}
                                onChange={(v) => form.setData('fee_amount', v)}
                                invalid={!!errors.fee_amount}
                            />
                            <InputError message={errors.fee_amount} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="rr-due">Jatuh tempo</Label>
                            <Input
                                id="rr-due"
                                type="date"
                                value={form.data.fee_due_date}
                                onChange={(e) =>
                                    form.setData('fee_due_date', e.target.value)
                                }
                            />
                            <InputError message={errors.fee_due_date} />
                        </div>
                    </div>
                )}
                <Button
                    type="submit"
                    className="w-full"
                    disabled={form.processing}
                >
                    {form.processing && <Spinner />}
                    Konfirmasi daftar ulang
                </Button>
            </form>
        </Card>
    );
}
