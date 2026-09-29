import { Head, router } from '@inertiajs/react';
import {
    BarChart3,
    Download,
    FileSpreadsheet,
    FileText,
    Filter,
    History,
    RefreshCw,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import type { FormEvent } from 'react';
import { toast } from 'sonner';
import InputError from '@/Components/input-error';
import { EmptyState } from '@/Components/shared/EmptyState';
import { PageHeader } from '@/Components/shared/PageHeader';
import { ListSkeleton } from '@/Components/shared/Skeletons';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Spinner } from '@/Components/ui/spinner';
import { useInertiaLoading } from '@/hooks/use-inertia-loading';
import AppLayout from '@/Layouts/AppLayout';
import { formatDate, formatNumber } from '@/lib/format';
import { HttpError, postForDownload } from '@/lib/http';
import { cn } from '@/lib/utils';
import { NativeSelect } from '@/Modules/Academic/Components/NativeSelect';
import type {
    ExportRow,
    ReportField,
    ReportPreview,
} from '@/Modules/Report/types';
import type { StatusVariant } from '@/types';

type Props = {
    base: string;
    reports: { key: string; label: string; description: string }[];
    selected: string;
    fields: ReportField[];
    values: Record<string, string | null>;
    preview: ReportPreview | null;
    previewErrors: Record<string, string>;
    directLimit: number;
    exports: ExportRow[];
};

const EXPORT_VARIANT: Record<ExportRow['status']['value'], StatusVariant> = {
    PENDING: 'neutral',
    PROCESSING: 'info',
    DONE: 'success',
    FAILED: 'danger',
};

type Format = 'pdf' | 'xlsx';

export default function ReportCenter({
    base,
    reports,
    selected,
    fields,
    values,
    preview,
    previewErrors,
    directLimit,
    exports,
}: Props) {
    const [form, setForm] = useState<Record<string, string>>(() =>
        Object.fromEntries(fields.map((f) => [f.name, values[f.name] ?? ''])),
    );
    const [exporting, setExporting] = useState<Format | null>(null);
    const [exportErrors, setExportErrors] = useState<Record<string, string>>(
        {},
    );
    const loading = useInertiaLoading();
    const current = reports.find((r) => r.key === selected);
    const errors = { ...previewErrors, ...exportErrors };
    const hasPending = exports.some(
        (e) => e.status.value === 'PENDING' || e.status.value === 'PROCESSING',
    );

    // Ekspor antrean diproses scheduler (±1 menit): segarkan daftar selama masih ada yang berjalan.
    useEffect(() => {
        if (!hasPending) {
            return;
        }

        const timer = setInterval(
            () => router.reload({ only: ['exports'] }),
            10_000,
        );

        return () => clearInterval(timer);
    }, [hasPending]);

    const query = (extra: Record<string, string> = {}) =>
        Object.fromEntries(
            Object.entries({ laporan: selected, ...form, ...extra }).filter(
                ([, v]) => v !== '',
            ),
        );

    const choose = (key: string) => router.get(base, { laporan: key });

    const apply = (event: FormEvent) => {
        event.preventDefault();
        setExportErrors({});
        router.get(base, query(), {
            preserveScroll: true,
            preserveState: true,
        });
    };

    const download = async (format: Format) => {
        setExporting(format);
        setExportErrors({});

        try {
            const result = await postForDownload(
                `${base}/ekspor`,
                { ...query(), format },
                `laporan.${format}`,
            );

            if (result.queued) {
                toast.success(result.message);
                router.reload({ only: ['exports'] });
            } else {
                toast.success('Laporan diunduh.');
            }
        } catch (error) {
            if (error instanceof HttpError) {
                setExportErrors(error.errors);
            }

            toast.error(
                error instanceof Error ? error.message : 'Ekspor gagal.',
            );
        } finally {
            setExporting(null);
        }
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Laporan', href: base }]}>
            <Head title="Laporan" />
            <PageHeader
                title="Pusat laporan"
                description="Pilih laporan, atur filter, periksa pratinjau, lalu unduh sebagai PDF atau Excel."
            />

            <div className="grid gap-6 lg:grid-cols-4">
                <nav
                    aria-label="Jenis laporan"
                    className="min-w-0 lg:col-span-1"
                >
                    <div className="lg:hidden">
                        <Label htmlFor="report-picker" className="mb-2 block">
                            Jenis laporan
                        </Label>
                        <NativeSelect
                            id="report-picker"
                            value={selected}
                            onChange={(e) => choose(e.target.value)}
                        >
                            {reports.map((r) => (
                                <option key={r.key} value={r.key}>
                                    {r.label}
                                </option>
                            ))}
                        </NativeSelect>
                    </div>
                    <ul className="hidden space-y-1 lg:block">
                        {reports.map((r) => (
                            <li key={r.key}>
                                <button
                                    type="button"
                                    onClick={() => choose(r.key)}
                                    aria-current={
                                        r.key === selected ? 'page' : undefined
                                    }
                                    className={cn(
                                        'w-full rounded-xl px-3 py-2.5 text-left transition-colors duration-150',
                                        r.key === selected
                                            ? 'bg-brand-50 text-brand-800 ring-1 ring-brand-200'
                                            : 'text-slate-700 hover:bg-slate-100',
                                    )}
                                >
                                    <span className="block text-sm font-medium">
                                        {r.label}
                                    </span>
                                    <span className="mt-0.5 block text-xs text-slate-500">
                                        {r.description}
                                    </span>
                                </button>
                            </li>
                        ))}
                    </ul>
                </nav>

                <div className="min-w-0 space-y-6 lg:col-span-3">
                    <form
                        onSubmit={apply}
                        className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                    >
                        <h2 className="mb-1 flex items-center gap-2 text-sm font-semibold text-slate-900">
                            <Filter className="size-4 text-brand-600" />
                            {current?.label}
                        </h2>
                        <p className="mb-4 text-xs text-slate-500 lg:hidden">
                            {current?.description}
                        </p>
                        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            {fields.map((field) => {
                                const id = `filter-${field.name}`;

                                return (
                                    <div
                                        key={field.name}
                                        className="grid min-w-0 gap-2"
                                    >
                                        <Label htmlFor={id}>
                                            {field.label}
                                            {!field.required && (
                                                <span className="font-normal text-slate-400">
                                                    {' '}
                                                    (opsional)
                                                </span>
                                            )}
                                        </Label>
                                        {field.type === 'date' ? (
                                            <Input
                                                id={id}
                                                type="date"
                                                value={form[field.name] ?? ''}
                                                onChange={(e) =>
                                                    setForm((f) => ({
                                                        ...f,
                                                        [field.name]:
                                                            e.target.value,
                                                    }))
                                                }
                                                aria-invalid={
                                                    !!errors[field.name]
                                                }
                                            />
                                        ) : (
                                            <NativeSelect
                                                id={id}
                                                value={form[field.name] ?? ''}
                                                onChange={(e) =>
                                                    setForm((f) => ({
                                                        ...f,
                                                        [field.name]:
                                                            e.target.value,
                                                    }))
                                                }
                                                aria-invalid={
                                                    !!errors[field.name]
                                                }
                                            >
                                                <option value="">
                                                    {field.placeholder ??
                                                        'Pilih'}
                                                </option>
                                                {field.options?.map((o) => (
                                                    <option
                                                        key={o.value}
                                                        value={o.value}
                                                    >
                                                        {o.label}
                                                    </option>
                                                ))}
                                            </NativeSelect>
                                        )}
                                        <InputError
                                            message={errors[field.name]}
                                        />
                                    </div>
                                );
                            })}
                        </div>
                        <div className="mt-5 flex flex-wrap items-center gap-2">
                            <Button
                                type="submit"
                                variant="outline"
                                disabled={loading}
                            >
                                {loading ? <Spinner /> : <RefreshCw />}
                                Tampilkan
                            </Button>
                            <Button
                                type="button"
                                onClick={() => download('pdf')}
                                disabled={exporting !== null}
                            >
                                {exporting === 'pdf' ? (
                                    <Spinner />
                                ) : (
                                    <FileText />
                                )}
                                Unduh PDF
                            </Button>
                            <Button
                                type="button"
                                onClick={() => download('xlsx')}
                                disabled={exporting !== null}
                            >
                                {exporting === 'xlsx' ? (
                                    <Spinner />
                                ) : (
                                    <FileSpreadsheet />
                                )}
                                Unduh Excel
                            </Button>
                            {preview && preview.total > directLimit && (
                                <p className="text-xs text-slate-500">
                                    Lebih dari {formatNumber(directLimit)}{' '}
                                    baris: file disiapkan di latar belakang dan
                                    Anda diberi tahu saat siap.
                                </p>
                            )}
                        </div>
                    </form>

                    <section className="min-w-0 rounded-2xl border border-slate-200 bg-white shadow-sm">
                        {loading ? (
                            <ListSkeleton rows={6} />
                        ) : !preview ? (
                            <EmptyState
                                icon={BarChart3}
                                title="Lengkapi filter"
                                description={
                                    Object.values(previewErrors)[0] ??
                                    'Atur filter lalu klik Tampilkan.'
                                }
                            />
                        ) : (
                            <>
                                <header className="border-b border-slate-200 p-5">
                                    <h2 className="font-semibold text-slate-900">
                                        {preview.title}
                                    </h2>
                                    <p className="mt-1 text-xs text-slate-500">
                                        {preview.meta.join(' · ')}
                                    </p>
                                </header>
                                {preview.total === 0 ? (
                                    <EmptyState
                                        icon={BarChart3}
                                        title="Tidak ada data"
                                        description="Tidak ada data untuk filter ini."
                                    />
                                ) : (
                                    <>
                                        <div className="max-h-[32rem] overflow-auto">
                                            <table className="w-full text-left text-sm">
                                                <thead className="sticky top-0 z-10 bg-slate-50 text-xs text-slate-500">
                                                    <tr>
                                                        {preview.columns.map(
                                                            (c, i) => (
                                                                <th
                                                                    key={c}
                                                                    scope="col"
                                                                    className={cn(
                                                                        'px-3 py-2 font-medium whitespace-nowrap',
                                                                        preview.numeric.includes(
                                                                            i,
                                                                        ) &&
                                                                            'text-right',
                                                                    )}
                                                                >
                                                                    {c}
                                                                </th>
                                                            ),
                                                        )}
                                                    </tr>
                                                </thead>
                                                <tbody className="divide-y divide-slate-100 text-slate-700">
                                                    {preview.rows.map(
                                                        (row, r) => (
                                                            <tr
                                                                key={r}
                                                                className="hover:bg-slate-50"
                                                            >
                                                                {row.map(
                                                                    (
                                                                        cell,
                                                                        i,
                                                                    ) => (
                                                                        <td
                                                                            key={
                                                                                i
                                                                            }
                                                                            className={cn(
                                                                                'px-3 py-2 whitespace-nowrap',
                                                                                preview.numeric.includes(
                                                                                    i,
                                                                                ) &&
                                                                                    'text-right tabular-nums',
                                                                            )}
                                                                        >
                                                                            {
                                                                                cell
                                                                            }
                                                                        </td>
                                                                    ),
                                                                )}
                                                            </tr>
                                                        ),
                                                    )}
                                                </tbody>
                                                {preview.footer && (
                                                    <tfoot className="bg-brand-50 font-semibold text-slate-900">
                                                        <tr>
                                                            {preview.footer.map(
                                                                (cell, i) => (
                                                                    <td
                                                                        key={i}
                                                                        className={cn(
                                                                            'px-3 py-2 whitespace-nowrap',
                                                                            preview.numeric.includes(
                                                                                i,
                                                                            ) &&
                                                                                'text-right tabular-nums',
                                                                        )}
                                                                    >
                                                                        {cell}
                                                                    </td>
                                                                ),
                                                            )}
                                                        </tr>
                                                    </tfoot>
                                                )}
                                            </table>
                                        </div>
                                        <p className="border-t border-slate-200 px-5 py-3 text-xs text-slate-500">
                                            Pratinjau{' '}
                                            {formatNumber(preview.rows.length)}{' '}
                                            dari {formatNumber(preview.total)}{' '}
                                            baris
                                            {preview.total >
                                                preview.rows.length &&
                                                ' — unduh PDF/Excel untuk data lengkap'}
                                            .
                                        </p>
                                    </>
                                )}
                            </>
                        )}
                    </section>

                    {exports.length > 0 && (
                        <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <h2 className="mb-3 flex items-center gap-2 text-sm font-semibold text-slate-900">
                                <History className="size-4 text-brand-600" />
                                Ekspor terbaru (berlaku 24 jam)
                            </h2>
                            <ul className="divide-y divide-slate-100">
                                {exports.map((item) => (
                                    <li
                                        key={item.public_id}
                                        className="flex flex-wrap items-center gap-3 py-2.5"
                                    >
                                        <span className="min-w-0 flex-1">
                                            <span className="block truncate text-sm font-medium text-slate-800">
                                                {item.title} · {item.format}
                                            </span>
                                            <span className="text-xs text-slate-500">
                                                {item.rows !== null &&
                                                    `${formatNumber(item.rows)} baris · `}
                                                diminta{' '}
                                                {formatDate(
                                                    item.created_at,
                                                    'd MMM, HH.mm',
                                                )}{' '}
                                                · berlaku sampai{' '}
                                                {formatDate(
                                                    item.expires_at,
                                                    'd MMM, HH.mm',
                                                )}
                                            </span>
                                        </span>
                                        <StatusBadge
                                            variant={
                                                EXPORT_VARIANT[
                                                    item.status.value
                                                ]
                                            }
                                            dot
                                        >
                                            {item.status.label}
                                        </StatusBadge>
                                        {item.download_url && (
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                asChild
                                            >
                                                <a href={item.download_url}>
                                                    <Download />
                                                    Unduh
                                                </a>
                                            </Button>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        </section>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
