import { Head, router, useForm } from '@inertiajs/react';
import { Calculator, FilePlus2, History } from 'lucide-react';
import { useEffect, useState } from 'react';
import type { FormEvent, ReactNode } from 'react';
import InputError from '@/Components/input-error';
import { PageHeader } from '@/Components/shared/PageHeader';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Spinner } from '@/Components/ui/spinner';
import AppLayout from '@/Layouts/AppLayout';
import { formatCurrency, formatDate, formatDateTime } from '@/lib/format';
import { errorMessage, HttpError, sendJson } from '@/lib/http';
import { cn } from '@/lib/utils';
import { NativeSelect } from '@/Modules/Academic/Components/NativeSelect';
import type { Option } from '@/Modules/Academic/types';
import { RupiahInput } from '@/Modules/Finance/Components/RupiahInput';
import type { BatchRow, FeeTypeRow } from '@/Modules/Finance/types';
import type { StatusVariant } from '@/types';

type Props = {
    feeTypes: FeeTypeRow[];
    classrooms: (Option & { grade: number })[];
    grades: number[];
    batches: BatchRow[];
    defaults: { month: string; due_date: string; period_label: string };
};

type Preview = {
    label: string;
    students: number;
    skipped: number;
    to_create: number;
    discounted: number;
    gross: number;
    total: number;
};

const BASE = '/operator/keuangan';
const SCOPES = [
    { value: 'SEMUA', label: 'Semua siswa aktif' },
    { value: 'TINGKAT', label: 'Satu tingkat' },
    { value: 'ROMBEL', label: 'Rombel tertentu' },
] as const;
const BATCH_VARIANT: Record<BatchRow['status']['value'], StatusVariant> = {
    QUEUED: 'neutral',
    RUNNING: 'info',
    DONE: 'success',
    FAILED: 'danger',
};

function Card({
    title,
    icon: Icon,
    children,
}: {
    title: string;
    icon: typeof History;
    children: ReactNode;
}) {
    return (
        <section className="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 className="flex items-center gap-2 text-sm font-semibold text-slate-900">
                <Icon className="size-4 text-brand-600" />
                {title}
            </h2>
            {children}
        </section>
    );
}

export default function GenerateInvoices({
    feeTypes,
    classrooms,
    grades,
    batches,
    defaults,
}: Props) {
    const first = feeTypes[0];
    const form = useForm({
        fee_type: first?.public_id ?? '',
        month: defaults.month,
        period_label: defaults.period_label,
        amount: (first?.default_amount ?? '') as number | '',
        due_date: defaults.due_date,
        scope: 'SEMUA' as (typeof SCOPES)[number]['value'],
        grade: String(grades[0] ?? ''),
        classrooms: [] as string[],
    });
    const [preview, setPreview] = useState<Preview | null>(null);
    const [previewing, setPreviewing] = useState(false);
    const feeType = feeTypes.find((f) => f.public_id === form.data.fee_type);
    const monthly = feeType?.frequency.value === 'BULANAN';
    const busy = batches.some(
        (b) => b.status.value === 'QUEUED' || b.status.value === 'RUNNING',
    );

    // Riwayat batch diperbarui berkala selama masih ada yang diproses.
    useEffect(() => {
        if (!busy) {
            return;
        }

        const timer = window.setInterval(
            () => router.reload({ only: ['batches'] }),
            5000,
        );

        return () => window.clearInterval(timer);
    }, [busy]);

    const change = (patch: Partial<typeof form.data>) => {
        form.setData((data) => ({ ...data, ...patch }));
        setPreview(null);
    };

    const pickFeeType = (publicId: string) => {
        const next = feeTypes.find((f) => f.public_id === publicId);
        form.setData((data) => ({
            ...data,
            fee_type: publicId,
            amount: next?.default_amount ?? data.amount,
        }));
        setPreview(null);
    };

    const runPreview = () => {
        setPreviewing(true);
        form.clearErrors();
        sendJson<Preview>(`${BASE}/buat/preview`, 'POST', { ...form.data })
            .then(setPreview)
            .catch((error: unknown) => {
                if (error instanceof HttpError) {
                    form.setError('fee_type', error.message);
                } else {
                    form.setError('fee_type', errorMessage(0, null));
                }
            })
            .finally(() => setPreviewing(false));
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(`${BASE}/buat`, {
            preserveScroll: true,
            onSuccess: () => setPreview(null),
        });
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Keuangan', href: `${BASE}/tagihan` },
                { title: 'Buat tagihan', href: `${BASE}/buat` },
            ]}
        >
            <Head title="Buat tagihan" />
            <PageHeader
                title="Buat tagihan massal"
                description="Siswa yang sudah punya tagihan untuk jenis & periode yang sama otomatis dilewati. Potongan siswa diterapkan otomatis."
            />

            {feeTypes.length === 0 ? (
                <p className="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-600 shadow-sm">
                    Belum ada jenis biaya aktif. Tambahkan dulu di menu Jenis
                    Biaya.
                </p>
            ) : (
                <form onSubmit={submit} className="grid gap-6 lg:grid-cols-5">
                    <div className="min-w-0 space-y-6 lg:col-span-3">
                        <Card title="Tagihan" icon={FilePlus2}>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2 sm:col-span-2">
                                    <Label htmlFor="gen-fee">Jenis biaya</Label>
                                    <NativeSelect
                                        id="gen-fee"
                                        value={form.data.fee_type}
                                        onChange={(e) =>
                                            pickFeeType(e.target.value)
                                        }
                                    >
                                        {feeTypes.map((f) => (
                                            <option
                                                key={f.public_id}
                                                value={f.public_id}
                                            >
                                                {f.name} · {f.frequency.label}
                                            </option>
                                        ))}
                                    </NativeSelect>
                                    <InputError
                                        message={form.errors.fee_type}
                                    />
                                </div>
                                {monthly ? (
                                    <div className="grid gap-2">
                                        <Label htmlFor="gen-month">Bulan</Label>
                                        <Input
                                            id="gen-month"
                                            type="month"
                                            value={form.data.month}
                                            onChange={(e) =>
                                                change({
                                                    month: e.target.value,
                                                })
                                            }
                                        />
                                        <InputError
                                            message={form.errors.month}
                                        />
                                    </div>
                                ) : (
                                    <div className="grid gap-2">
                                        <Label htmlFor="gen-period">
                                            Periode
                                        </Label>
                                        <Input
                                            id="gen-period"
                                            maxLength={40}
                                            value={form.data.period_label}
                                            onChange={(e) =>
                                                change({
                                                    period_label:
                                                        e.target.value,
                                                })
                                            }
                                            placeholder="2026/2027"
                                        />
                                        <InputError
                                            message={form.errors.period_label}
                                        />
                                    </div>
                                )}
                                <div className="grid gap-2">
                                    <Label htmlFor="gen-due">Jatuh tempo</Label>
                                    <Input
                                        id="gen-due"
                                        type="date"
                                        value={form.data.due_date}
                                        onChange={(e) =>
                                            change({ due_date: e.target.value })
                                        }
                                    />
                                    <InputError
                                        message={form.errors.due_date}
                                    />
                                </div>
                                <div className="grid gap-2 sm:col-span-2">
                                    <Label htmlFor="gen-amount">
                                        Nominal per siswa (sebelum potongan)
                                    </Label>
                                    <RupiahInput
                                        id="gen-amount"
                                        value={form.data.amount}
                                        onChange={(v) => change({ amount: v })}
                                        invalid={!!form.errors.amount}
                                    />
                                    <InputError message={form.errors.amount} />
                                </div>
                            </div>
                        </Card>

                        <Card title="Sasaran" icon={Calculator}>
                            <div
                                role="radiogroup"
                                className="grid gap-2 sm:grid-cols-3"
                            >
                                {SCOPES.map((scope) => (
                                    <label
                                        key={scope.value}
                                        className={cn(
                                            'cursor-pointer rounded-xl border p-3 text-sm font-medium transition-colors duration-150',
                                            form.data.scope === scope.value
                                                ? 'border-brand-600 bg-brand-50 text-brand-800'
                                                : 'border-slate-200 text-slate-700 hover:border-brand-300',
                                        )}
                                    >
                                        <input
                                            type="radio"
                                            name="scope"
                                            className="sr-only"
                                            checked={
                                                form.data.scope === scope.value
                                            }
                                            onChange={() =>
                                                change({ scope: scope.value })
                                            }
                                        />
                                        {scope.label}
                                    </label>
                                ))}
                            </div>
                            {form.data.scope === 'TINGKAT' && (
                                <div className="grid max-w-xs gap-2">
                                    <Label htmlFor="gen-grade">Tingkat</Label>
                                    <NativeSelect
                                        id="gen-grade"
                                        value={form.data.grade}
                                        onChange={(e) =>
                                            change({ grade: e.target.value })
                                        }
                                    >
                                        {grades.map((g) => (
                                            <option key={g} value={g}>
                                                Kelas {g}
                                            </option>
                                        ))}
                                    </NativeSelect>
                                    <InputError message={form.errors.grade} />
                                </div>
                            )}
                            {form.data.scope === 'ROMBEL' && (
                                <div className="space-y-2">
                                    <div className="grid max-h-56 grid-cols-2 gap-2 overflow-y-auto sm:grid-cols-4">
                                        {classrooms.map((c) => (
                                            <label
                                                key={c.value}
                                                className="flex items-center gap-2 text-sm"
                                            >
                                                <Checkbox
                                                    checked={form.data.classrooms.includes(
                                                        c.value,
                                                    )}
                                                    onCheckedChange={() =>
                                                        change({
                                                            classrooms:
                                                                form.data.classrooms.includes(
                                                                    c.value,
                                                                )
                                                                    ? form.data.classrooms.filter(
                                                                          (v) =>
                                                                              v !==
                                                                              c.value,
                                                                      )
                                                                    : [
                                                                          ...form
                                                                              .data
                                                                              .classrooms,
                                                                          c.value,
                                                                      ],
                                                        })
                                                    }
                                                />
                                                {c.label}
                                            </label>
                                        ))}
                                    </div>
                                    <InputError
                                        message={form.errors.classrooms}
                                    />
                                </div>
                            )}
                        </Card>
                    </div>

                    <aside className="min-w-0 space-y-4 lg:col-span-2">
                        <section className="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                            <h2 className="text-sm font-semibold text-slate-900">
                                Pratinjau
                            </h2>
                            {preview ? (
                                <dl className="space-y-2 text-sm">
                                    <Row label="Tagihan">{preview.label}</Row>
                                    <Row label="Siswa sasaran">
                                        {preview.students}
                                    </Row>
                                    <Row label="Dilewati (sudah ada)">
                                        {preview.skipped}
                                    </Row>
                                    <Row label="Mendapat potongan">
                                        {preview.discounted}
                                    </Row>
                                    <Row label="Tagihan dibuat">
                                        <span className="font-semibold text-slate-900">
                                            {preview.to_create}
                                        </span>
                                    </Row>
                                    <Row label="Total sebelum potongan">
                                        {formatCurrency(preview.gross)}
                                    </Row>
                                    <div className="flex justify-between gap-4 border-t border-slate-200 pt-2 text-base font-semibold text-brand-700">
                                        <dt>Total tagihan</dt>
                                        <dd className="tabular-nums">
                                            {formatCurrency(preview.total)}
                                        </dd>
                                    </div>
                                </dl>
                            ) : (
                                <p className="text-sm text-slate-500">
                                    Hitung pratinjau sebelum membuat tagihan.
                                </p>
                            )}
                            <div className="flex flex-col gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={runPreview}
                                    disabled={previewing}
                                >
                                    {previewing ? <Spinner /> : <Calculator />}
                                    Hitung pratinjau
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={
                                        !preview ||
                                        preview.to_create === 0 ||
                                        form.processing
                                    }
                                >
                                    {form.processing && <Spinner />}
                                    Buat {preview?.to_create ?? ''} tagihan
                                </Button>
                            </div>
                        </section>
                    </aside>
                </form>
            )}

            <Card title="Riwayat pembuatan" icon={History}>
                {batches.length === 0 ? (
                    <p className="text-sm text-slate-500">Belum ada.</p>
                ) : (
                    <ul className="divide-y divide-slate-100">
                        {batches.map((batch) => (
                            <li
                                key={batch.public_id}
                                className="flex flex-col gap-1 py-3 text-sm first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div className="min-w-0">
                                    <p className="font-medium text-slate-900">
                                        {batch.title}
                                    </p>
                                    <p className="text-xs text-slate-500">
                                        {batch.scope} · jatuh tempo{' '}
                                        {formatDate(batch.due_date)} ·{' '}
                                        {formatDateTime(batch.created_at)}
                                        {batch.creator && ` · ${batch.creator}`}
                                    </p>
                                    {batch.error && (
                                        <p className="text-xs text-rose-600">
                                            {batch.error}
                                        </p>
                                    )}
                                </div>
                                <div className="flex shrink-0 items-center gap-3">
                                    <span className="text-xs text-slate-500 tabular-nums">
                                        {batch.created_count} dibuat ·{' '}
                                        {batch.skipped_count} dilewati ·{' '}
                                        {formatCurrency(batch.total_amount)}
                                    </span>
                                    <StatusBadge
                                        variant={
                                            BATCH_VARIANT[batch.status.value]
                                        }
                                        dot
                                    >
                                        {batch.status.label}
                                    </StatusBadge>
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
            </Card>
        </AppLayout>
    );
}

function Row({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div className="flex justify-between gap-4">
            <dt className="text-slate-500">{label}</dt>
            <dd className="text-right text-slate-800 tabular-nums">
                {children}
            </dd>
        </div>
    );
}
